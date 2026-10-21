#!/usr/bin/env bash
#
#   Points www.dreamhomes-realty.com at the site: replaces the holding-page
#   block in the Caddyfile with deploy/dreamhomes.caddy. Does nothing if that
#   is already done. If Caddy rejects the change, the previous Caddyfile is put
#   back and the holding page stays up.
#
#   Called by install.sh and update.sh; can be run on its own:
#     sudo bash /srv/src/dreamhomes/deploy/caddy-switch.sh
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

SITE=${SITE:-/srv/src/dreamhomes}
CADDYFILE=/etc/caddy/Caddyfile
LOG=/var/log/caddy/dreamhomes.log

ok()  { printf '   \033[32mok\033[0m  %s\n' "$*"; }
die() { printf '   \033[31mFAIL\033[0m %s\n' "$*"; exit 1; }

# The site's log file must belong to caddy. `caddy validate` runs as root and
# would otherwise create it root-owned, and the real reload — as the caddy
# user — then fails with "permission denied" (it did, on the first install).
[[ -f $LOG ]] || touch "$LOG"
chown caddy:caddy "$LOG"; chmod 640 "$LOG"

if grep -q 'reverse_proxy 127.0.0.1:4330' "$CADDYFILE"; then
  ok "Caddy already routes www.dreamhomes-realty.com to the site"
  exit 0
fi

backup="$CADDYFILE.bak-$(date +%Y%m%d-%H%M%S)"
cp "$CADDYFILE" "$backup"
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

if caddy validate --config "$CADDYFILE" --adapter caddyfile >/tmp/caddy-validate.log 2>&1 \
  && chown caddy:caddy "$LOG" && systemctl reload caddy; then
  ok "Caddy reloaded — www.dreamhomes-realty.com now serves the new site (backup: $backup)"
else
  tail -3 /tmp/caddy-validate.log
  journalctl -u caddy -n 3 --no-pager | grep -i error || true
  cp "$backup" "$CADDYFILE"
  systemctl reload caddy || true
  die "Caddy rejected the new config — restored the previous Caddyfile, holding page still up"
fi
