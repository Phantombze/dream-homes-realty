#!/usr/bin/env bash
#
#   Applies new code to an installed Dream Homes site. Rebuilds only what
#   changed, so a typical update takes about a minute.
#
#   Run:  sudo bash /srv/src/dreamhomes/deploy/update.sh
#
#   1. Site: pulls github.com/Phantombze/dream-homes-realty. Rebuilds and
#      restarts the site only if web/ changed.
#   2. CMS: runs the CMS's own update (/srv/src/cms/deploy/update.sh), which
#      applies /tmp/belize-sites-cms.bundle if present and rebuilds Payload only
#      if its code changed.
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
CMS=/srv/src/cms
as_owner() { sudo -u "$OWNER" -H "$@"; }
export NODE_OPTIONS=--max-old-space-size=900

grep -q "WorkingDirectory=$CMS\$" /etc/systemd/system/payload-cms.service \
  || die "the CMS does not run from $CMS yet — move it first: deploy/migrate.sh in the belize-sites-cms repository"

# ---------------------------------------------------------------------------
say "Site code"
# ---------------------------------------------------------------------------
as_owner git -C "$SITE" pull -q --ff-only origin main
after=$(as_owner git -C "$SITE" rev-parse HEAD)
ok "$(as_owner git -C "$SITE" log --oneline -1)"
# Compare with the commit the running build was made from (recorded at build
# time), not with what was checked out before this pull: a manual `git pull`
# beforehand would otherwise hide the change and skip the rebuild.
built=$(cat "$WEB/dist/.built-from" 2>/dev/null || echo none)
site_changed=0
if [[ $built == none ]] || ! as_owner git -C "$SITE" cat-file -e "$built^{commit}" 2>/dev/null; then
  site_changed=1
elif [[ $built != "$after" ]] && ! as_owner git -C "$SITE" diff --quiet "$built" "$after" -- web/ ':!web/src/data' ':!web/public/images/seed' ':!web/scripts'; then
  site_changed=1
fi

# ---------------------------------------------------------------------------
say "CMS code"
# ---------------------------------------------------------------------------
# The CMS is its own repository (github.com/Phantombze/belize-sites-cms) with
# its own update script; it applies /tmp/belize-sites-cms.bundle if there is
# one and rebuilds only if its code changed.
bash "$CMS/deploy/update.sh"

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
  echo "$after" > "$WEB/dist/.built-from"
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
