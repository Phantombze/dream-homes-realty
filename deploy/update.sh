#!/usr/bin/env bash
#
#   Applies new code to an installed Dream Homes site. Rebuilds only what
#   changed, so a typical update takes about a minute.
#
#   Run:  sudo bash /srv/src/dreamhomes/deploy/update.sh
#
#   1. Site: pulls github.com/Phantombze/dream-homes-realty. Rebuilds and
#      restarts the site only if web/ changed.
#   2. CMS: if /tmp/medinasmaterial.bundle is newer than the CMS checkout,
#      updates it. Rebuilds and restarts Payload only if code the running CMS
#      uses changed (seed scripts run straight from source and need nothing).
#   3. Content: loads anything in web/src/data that the CMS does not have yet —
#      new listings, locations, photos. Never overwrites edits made in the CMS.
#
#   Content edits made in the CMS itself never need this: they are live on save.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

say()  { printf '\n\033[1;36m== %s\033[0m\n' "$*"; }
ok()   { printf '   \033[32mok\033[0m  %s\n' "$*"; }
warn() { printf '   \033[33m!!\033[0m  %s\n' "$*"; }
die()  { printf '   \033[31mFAIL\033[0m %s\n' "$*"; exit 1; }

OWNER=medmat
SITE=/srv/src/dreamhomes
WEB=$SITE/web
CMS_ROOT=/srv/src/medinasmaterial
CMS=$CMS_ROOT/cms
BUNDLE=/tmp/medinasmaterial.bundle
as_owner() { sudo -u "$OWNER" -H "$@"; }
export NODE_OPTIONS=--max-old-space-size=900

# ---------------------------------------------------------------------------
say "Site code"
# ---------------------------------------------------------------------------
before=$(as_owner git -C "$SITE" rev-parse HEAD)
as_owner git -C "$SITE" pull -q --ff-only origin main
after=$(as_owner git -C "$SITE" rev-parse HEAD)
ok "$(as_owner git -C "$SITE" log --oneline -1)"
site_changed=0
[[ $before != "$after" ]] && ! as_owner git -C "$SITE" diff --quiet "$before" "$after" -- web/ ':!web/src/data' ':!web/public/images/seed' ':!web/scripts' && site_changed=1

# ---------------------------------------------------------------------------
say "CMS code"
# ---------------------------------------------------------------------------
cms_changed=0
if [[ -f $BUNDLE && $BUNDLE -nt $CMS_ROOT/.git/HEAD ]]; then
  chmod 644 "$BUNDLE"
  as_owner git -C "$CMS_ROOT" bundle verify -q "$BUNDLE" >/dev/null 2>&1 || die "$BUNDLE is damaged — copy it again"
  as_owner git -C "$CMS_ROOT" diff --quiet HEAD || die "uncommitted changes in $CMS_ROOT — see: git -C $CMS_ROOT status"
  cbefore=$(as_owner git -C "$CMS_ROOT" rev-parse HEAD)
  as_owner git -C "$CMS_ROOT" fetch -q "$BUNDLE" main:refs/remotes/bundle/main
  as_owner git -C "$CMS_ROOT" merge -q --ff-only refs/remotes/bundle/main || die "the bundle is not a newer version of the CMS repository"
  cafter=$(as_owner git -C "$CMS_ROOT" rev-parse HEAD)
  [[ $cbefore != "$cafter" ]] && ! as_owner git -C "$CMS_ROOT" diff --quiet "$cbefore" "$cafter" -- cms/ ':!cms/src/seed' && cms_changed=1
  ok "$(as_owner git -C "$CMS_ROOT" log --oneline -1)"
else
  ok "no newer bundle in /tmp — CMS code unchanged"
fi

# ---------------------------------------------------------------------------
if (( cms_changed )); then
  say "Rebuild the CMS (its code changed — a few minutes)"
  systemctl start cms-backup.service && ok "database backed up"
  cd "$CMS"
  set -a; . /etc/payload-cms/payload.env; set +a
  npm install --include=dev --no-audit --no-fund 2>&1 | tail -1
  npm run generate:types 2>&1 | tail -1
  npm run generate:importmap 2>&1 | tail -1
  NODE_ENV=production npm run build 2>&1 | tail -4 || die "CMS build failed — the running CMS is untouched"
  chown -R medmat:payload "$CMS"; chmod -R g+rX "$CMS"
  chown -R payload:payload "$CMS/media" "$CMS/.next"; chmod -R g+rwX "$CMS/media" "$CMS/.next"
  systemctl restart payload-cms
  for i in $(seq 1 30); do curl -fsS -o /dev/null "http://127.0.0.1:3100/api/tenants?limit=1" && break; sleep 2; done
  ok "payload-cms restarted"
fi

# ---------------------------------------------------------------------------
say "New content (creates only what the CMS does not have)"
# ---------------------------------------------------------------------------
cd "$CMS"
set -a; . /etc/payload-cms/payload.env; set +a
DH_WEB_DIR=$WEB npm run seed:dreamhomes 2>&1 | grep -E '^\s+(created|updated)|Done|rror' | grep -v 'media:' || true
chown -R payload:payload "$CMS/media"

# ---------------------------------------------------------------------------
if (( site_changed )); then
  say "Rebuild the site (its code changed)"
  cd "$WEB"
  npm ci --include=dev --no-audit --no-fund 2>&1 | tail -1
  npm run build 2>&1 | tail -2 || die "site build failed — the running site is untouched"
  chown -R medmat:dreamhomes "$SITE"; chmod -R g+rX "$SITE"; chmod -R o+rX "$WEB/dist/client"
  systemctl restart dreamhomes-web
  for i in $(seq 1 20); do curl -fsS -o /dev/null http://127.0.0.1:4330/ && break; sleep 1; done
  ok "site rebuilt and restarted"
else
  ok "site code unchanged — no rebuild"
fi

# ---------------------------------------------------------------------------
say "Service and routing"
# ---------------------------------------------------------------------------
if ! cmp -s "$SITE/deploy/dreamhomes-web.service" /etc/systemd/system/dreamhomes-web.service; then
  install -m 0644 "$SITE/deploy/dreamhomes-web.service" /etc/systemd/system/dreamhomes-web.service
  systemctl daemon-reload
  systemctl restart dreamhomes-web
  for i in $(seq 1 20); do curl -fsS -o /dev/null http://127.0.0.1:4330/ && break; sleep 1; done
  ok "service unit updated and restarted"
fi
SITE=$SITE bash "$SITE/deploy/caddy-switch.sh"

say "Done"
curl -s -o /dev/null -w "   https://www.dreamhomes-realty.com/  →  %{http_code}\n" https://www.dreamhomes-realty.com/
