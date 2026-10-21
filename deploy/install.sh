#!/usr/bin/env bash
#
#   Puts dreamhomes-realty.com live: Astro rendered per request, content from
#   the shared Payload CMS. Replaces the holding page.
#
#   Expects the code already on the server:
#     /srv/src/dreamhomes        this repository (branch astro-payload or main)
#     /srv/src/medinasmaterial   the CMS repository, updated to include the
#                                Dream Homes collections
#
#   Run:  sudo bash /srv/src/dreamhomes/deploy/install.sh
#
#   Safe to re-run: every step checks before it changes anything. The CMS
#   database is backed up first; the seed only creates what is missing; and
#   the Caddy change is rolled back automatically if Caddy rejects it.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

say()  { printf '\n\033[1;36m== %s\033[0m\n' "$*"; }
ok()   { printf '   \033[32mok\033[0m  %s\n' "$*"; }
warn() { printf '   \033[33m!!\033[0m  %s\n' "$*"; }
die()  { printf '   \033[31mFAIL\033[0m %s\n' "$*"; exit 1; }

SITE=/srv/src/dreamhomes
WEB=$SITE/web
CMS=/srv/src/medinasmaterial/cms
PAYLOAD_ENV=/etc/payload-cms/payload.env
WEB_ENV=/etc/dreamhomes/web.env
CADDYFILE=/etc/caddy/Caddyfile
SITE_URL=https://www.dreamhomes-realty.com

export NODE_OPTIONS=--max-old-space-size=900   # builds beside MongoDB on a 2 GB box

# ---------------------------------------------------------------------------
say "Preflight"
# ---------------------------------------------------------------------------
[[ -f $WEB/package.json ]] || die "no site at $WEB — clone the dream-homes-realty repository there first"
[[ -f $CMS/src/collections/realty/Listings.ts ]] || die "$CMS has no Dream Homes collections — update the CMS repository first"
grep -q 'dreamhomes' "$CMS/src/payload.config.ts" || die "payload.config.ts does not register the Dream Homes collections"
systemctl is-active --quiet mongod || die "mongod is not running"
ok "sources present, MongoDB running"
echo "   available RAM: $(free -m | awk '/^Mem:/{print $7}') MB"

# ---------------------------------------------------------------------------
say "1. Back up the CMS database before changing its schema"
# ---------------------------------------------------------------------------
systemctl start cms-backup.service && ok "backup taken (see /var/backups/cms)" || die "backup failed — not continuing"

# ---------------------------------------------------------------------------
say "2. Shared secret between the CMS and the site"
# ---------------------------------------------------------------------------
install -d -m 0750 /etc/dreamhomes
getent passwd dreamhomes >/dev/null || useradd --system --no-create-home --shell /usr/sbin/nologin dreamhomes
chgrp dreamhomes /etc/dreamhomes

if grep -q '^REVALIDATE_SECRET=' "$PAYLOAD_ENV"; then
  SECRET=$(grep '^REVALIDATE_SECRET=' "$PAYLOAD_ENV" | cut -d= -f2-)
  ok "reusing REVALIDATE_SECRET from $PAYLOAD_ENV"
else
  SECRET=$(openssl rand -hex 32)
  printf '\n# Live sites: cleared after every save (lib/sites.ts)\nREVALIDATE_SECRET=%s\n' "$SECRET" >> "$PAYLOAD_ENV"
  ok "REVALIDATE_SECRET added to $PAYLOAD_ENV"
fi
if ! grep -q '^SITE_REVALIDATE_URLS=' "$PAYLOAD_ENV"; then
  echo 'SITE_REVALIDATE_URLS=dreamhomes=http://127.0.0.1:4330/api/revalidate/' >> "$PAYLOAD_ENV"
  ok "SITE_REVALIDATE_URLS added"
fi

cat > "$WEB_ENV" <<EOF
# Written by deploy/install.sh. Read by dreamhomes-web.service.
PAYLOAD_API_URL=http://127.0.0.1:3100
PAYLOAD_TENANT=dreamhomes
PUBLIC_SITE_URL=$SITE_URL
REVALIDATE_SECRET=$SECRET
EOF
chown root:dreamhomes "$WEB_ENV"
chmod 0640 "$WEB_ENV"
ok "$WEB_ENV written (0640 root:dreamhomes)"

# ---------------------------------------------------------------------------
say "3. Rebuild the CMS with the Dream Homes collections"
# ---------------------------------------------------------------------------
cd "$CMS"
set -a; . "$PAYLOAD_ENV"; set +a
npm install --include=dev --no-audit --no-fund 2>&1 | tail -2
npm run generate:types 2>&1 | tail -1
# Must run before the build, or the admin renders as a blank white page.
npm run generate:importmap 2>&1 | tail -1
NODE_ENV=production npm run build 2>&1 | tail -6 || die "CMS build failed — the running CMS is untouched"
chown -R medmat:payload "$CMS"; chmod -R g+rX "$CMS"
chown -R payload:payload "$CMS/media" "$CMS/.next"; chmod -R g+rwX "$CMS/media" "$CMS/.next"
systemctl restart payload-cms
for i in $(seq 1 30); do
  curl -fsS -o /dev/null "http://127.0.0.1:3100/api/tenants?limit=1" && break
  sleep 2
done
curl -fsS -o /dev/null "http://127.0.0.1:3100/api/listings?limit=1" || { journalctl -u payload-cms -n 30 --no-pager; die "CMS did not come back with the listings collection"; }
ok "payload-cms restarted with the Dream Homes collections"

# ---------------------------------------------------------------------------
say "4. Load Dream Homes content (creates only what is missing)"
# ---------------------------------------------------------------------------
DH_WEB_DIR=$WEB npm run seed:dreamhomes 2>&1 | grep -vE '^\s+media: ' | tail -25
chown -R payload:payload "$CMS/media"

# ---------------------------------------------------------------------------
say "5. Build the site"
# ---------------------------------------------------------------------------
cd "$WEB"
# Build-time values only. The secret is NOT written here: it is read at run
# time from $WEB_ENV, so it never ends up inside the built files.
{
  echo "# Written by deploy/install.sh"
  echo "PAYLOAD_API_URL=http://127.0.0.1:3100"
  echo "PAYLOAD_TENANT=dreamhomes"
  echo "PUBLIC_SITE_URL=$SITE_URL"
} > .env
npm ci --include=dev --no-audit --no-fund 2>&1 | tail -2
npm run build 2>&1 | tail -4 || die "site build failed — nothing changed on the live site"
chown -R medmat:dreamhomes "$SITE"; chmod -R g+rX "$SITE"
# Caddy serves /_astro/* straight from disk, so those files must be readable to it.
chmod -R o+rX "$WEB/dist/client"
ok "built to $WEB/dist"

# ---------------------------------------------------------------------------
say "6. Run it"
# ---------------------------------------------------------------------------
install -m 0644 "$SITE/deploy/dreamhomes-web.service" /etc/systemd/system/dreamhomes-web.service
systemctl daemon-reload
systemctl enable --now dreamhomes-web >/dev/null 2>&1
systemctl restart dreamhomes-web
for i in $(seq 1 20); do curl -fsS -o /dev/null http://127.0.0.1:4330/ && break; sleep 1; done
curl -fsS -o /dev/null http://127.0.0.1:4330/ || { journalctl -u dreamhomes-web -n 30 --no-pager; die "site did not start"; }
journalctl -u dreamhomes-web --since "-2min" --no-pager | grep -q '\[cms\]' && warn "the site logged [cms] warnings — check: journalctl -u dreamhomes-web" || ok "site up on 127.0.0.1:4330, reading the CMS"
resp=$(curl -fsS -X POST -H "x-revalidate-secret: $SECRET" http://127.0.0.1:4330/api/revalidate/ || true)
[[ $resp == *'"ok":true'* ]] && ok "CMS → site revalidation works" || warn "revalidate check failed: $resp"

# ---------------------------------------------------------------------------
say "7. Contact form service (Brevo + Turnstile)"
# ---------------------------------------------------------------------------
# Only the shared service is installed here. The full kit in
# ops/contact-forms/install.sh also changes Medina's and Parking Solutions'
# forms and rebuilds those sites; run that separately when they are ready.
KIT=/srv/src/medinasmaterial/ops/contact-forms/site-contact
[[ -f $KIT/contact-server.mjs ]] || die "no contact service at $KIT — update the CMS repository"
install -d -m 755 /opt/site-contact
install -m 644 "$KIT/contact-core.mjs" "$KIT/contact-server.mjs" /opt/site-contact/
if [[ ! -f /opt/site-contact/sites.json ]]; then
  install -m 644 "$KIT/sites.json" /opt/site-contact/
elif ! grep -q '"dreamhomes"' /opt/site-contact/sites.json; then
  warn "/opt/site-contact/sites.json exists without Dream Homes — add the dreamhomes entry from $KIT/sites.json"
fi
(cd "$KIT" && node test.mjs 2>/dev/null | tail -1)
install -d -m 700 /etc/site-contact
[[ -f /etc/site-contact/contact.env ]] || install -m 600 /dev/null /etc/site-contact/contact.env
install -m 644 "$KIT/site-contact.service" /etc/systemd/system/site-contact.service
systemctl daemon-reload
systemctl enable --now site-contact >/dev/null 2>&1
systemctl restart site-contact
sleep 1
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST -H 'Origin: https://evil.example' -H 'Content-Type: application/json' -d '{}' http://127.0.0.1:8787/api/contact || true)
[[ $code == 403 ]] && ok "contact service running, refuses unknown sites" || warn "contact service answered $code (expected 403)"
grep -q '^BREVO_API_KEY=xkeysib-' /etc/site-contact/contact.env \
  && ok "contact keys present" \
  || warn "contact keys not set yet: sudo bash $SITE/deploy/set-contact-keys.sh"

# ---------------------------------------------------------------------------
say "8. Point Caddy at it (replaces the holding page)"
# ---------------------------------------------------------------------------
if grep -q 'reverse_proxy 127.0.0.1:4330' "$CADDYFILE"; then
  ok "Caddy already routes the site"
else
  cp "$CADDYFILE" "$CADDYFILE.bak-$(date +%Y%m%d-%H%M%S)"
  python3 - "$CADDYFILE" "$SITE/deploy/dreamhomes.caddy" <<'PY'
import re, sys
path, block = sys.argv[1], open(sys.argv[2]).read()
text = open(path).read()
# The holding-page section: from its comment banner up to the next banner.
pattern = re.compile(r"# -+\n# dreamhomes-realty\.com.*?(?=\n# -+\n# cp\.panelbz\.cloud)", re.S)
if not pattern.search(text):
    sys.exit("holding-page block not found; edit the Caddyfile by hand")
open(path, "w").write(pattern.sub(block.rstrip() + "\n", text, count=1))
PY
  if caddy validate --config "$CADDYFILE" --adapter caddyfile >/dev/null 2>&1 && systemctl reload caddy; then
    ok "Caddy reloaded — www.dreamhomes-realty.com now serves the new site"
  else
    cp "$(ls -1t "$CADDYFILE".bak-* | head -1)" "$CADDYFILE"
    systemctl reload caddy || true
    die "Caddy rejected the new config — restored the previous Caddyfile, holding page still up"
  fi
fi

# ---------------------------------------------------------------------------
say "Done"
# ---------------------------------------------------------------------------
cat <<'EOF'
   Check:   curl -sI https://www.dreamhomes-realty.com/ | head -3
   Logs:    journalctl -u dreamhomes-web -f
   Edit:    https://cp.panelbz.cloud/admin  → Dream Homes

   Still to do once (see README → Deploying):
   - contact form keys:  sudo bash /srv/src/dreamhomes/deploy/set-contact-keys.sh
   - create Dean's editor account in the CMS, tenant "Dream Homes Realty"
EOF
