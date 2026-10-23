#!/usr/bin/env bash
#
#   Sets the contact-form keys on the server, for every site at once:
#   dreamhomes-realty.com, medinasmaterial.com and parkingsolutions.bz all use
#   the one shared contact service, so one Brevo key and one Turnstile widget
#   serve them all. Asks for each key (typing is hidden), checks it looks
#   right, writes it where only root can read it, and restarts what uses it.
#
#   Run:  sudo bash /srv/src/dreamhomes/deploy/set-contact-keys.sh
#
#   Where the keys go, and why there:
#     /etc/site-contact/contact.env   root 600   Brevo API key, Turnstile SECRET
#                                     Read by systemd for the contact service
#                                     before it drops to a throwaway user.
#     /srv/deploy/turnstile.env       644        Turnstile SITE key (public: it is
#                                     in every page's HTML anyway)
#
#   Not in git, not in the CMS: anyone with a CMS login, and every database
#   backup, would otherwise carry the keys.
#
#   Press Enter at a prompt to keep the value already set.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

SECRETS=/etc/site-contact/contact.env
SITEKEY=/srv/deploy/turnstile.env

install -d -m 700 /etc/site-contact
[[ -f $SECRETS ]] || install -m 600 /dev/null "$SECRETS"
[[ -f $SITEKEY ]] || install -m 644 /dev/null "$SITEKEY"

current() { grep -E "^$1=" "$2" 2>/dev/null | cut -d= -f2- || true; }

# Replace or add KEY=value in a file without ever printing the value.
put() {
  local key=$1 value=$2 file=$3 tmp
  tmp=$(mktemp)
  grep -vE "^$key=" "$file" > "$tmp" || true
  printf '%s=%s\n' "$key" "$value" >> "$tmp"
  cat "$tmp" > "$file"
  rm -f "$tmp"
}

ask() {
  local label=$1 pattern=$2 hint=$3 existing=$4 value
  while true; do
    if [[ -n $existing ]]; then
      read -rsp "  $label [set — Enter to keep]: " value; echo >&2
      [[ -z $value ]] && { printf '%s' "$existing"; return; }
    else
      read -rsp "  $label: " value; echo >&2
    fi
    value=$(printf '%s' "$value" | tr -d '[:space:]')
    [[ $value =~ $pattern ]] && { printf '%s' "$value"; return; }
    echo "    that does not look right — $hint" >&2
  done
}

echo
echo "Brevo: brevo.com → SMTP & API → API Keys → Generate (starts xkeysib-)"
BREVO=$(ask "Brevo API key" '^xkeysib-[A-Za-z0-9-]{20,}$' 'it starts with xkeysib-' "$(current BREVO_API_KEY "$SECRETS")")
echo
echo "Turnstile: Cloudflare → Turnstile → the widget used by the sites. Its hostnames"
echo "           must include all three: dreamhomes-realty.com, medinasmaterial.com and"
echo "           parkingsolutions.bz (the www. hosts are covered by the bare domain)."
TS_SECRET=$(ask "Turnstile SECRET key" '^0x[A-Za-z0-9_-]{20,}$' 'it starts with 0x' "$(current TURNSTILE_SECRET_KEY "$SECRETS")")
TS_SITE=$(ask "Turnstile SITE key  " '^0x[A-Za-z0-9_-]{10,}$' 'it starts with 0x' "$(current PUBLIC_TURNSTILE_SITE_KEY "$SITEKEY")")
[[ $TS_SITE != "$TS_SECRET" ]] || { echo "The site key and the secret key are the same — one of them is wrong."; exit 1; }

put BREVO_API_KEY "$BREVO" "$SECRETS"
put TURNSTILE_SECRET_KEY "$TS_SECRET" "$SECRETS"
chown root:root "$SECRETS"; chmod 600 "$SECRETS"
put PUBLIC_TURNSTILE_SITE_KEY "$TS_SITE" "$SITEKEY"
chmod 644 "$SITEKEY"
echo
echo "  saved: $SECRETS (root only), $SITEKEY"

systemctl restart site-contact
sleep 2
systemctl is-active --quiet site-contact || { journalctl -u site-contact -n 20 --no-pager; exit 1; }

# The two rendered sites read the site key when they start; restart them so
# their contact pages show the widget. Parking Solutions is static: its key
# is built into the page, so it needs publishing again (see the end).
check_site() {
  local name=$1 unit=$2 port=$3 path=$4 origin=$5
  if ! systemctl list-unit-files "$unit.service" --no-legend 2>/dev/null | grep -q .; then
    echo "  --  $name: not installed here"
    return
  fi
  systemctl restart "$unit"
  for i in $(seq 1 20); do curl -fsS -o /dev/null "http://127.0.0.1:$port/" && break; sleep 1; done
  curl -fsS "http://127.0.0.1:$port$path" | grep -q 'data-sitekey="0x' \
    && echo "  ok  $name: contact page shows the Turnstile widget" \
    || echo "  !!  $name: contact page has no Turnstile widget — check $SITEKEY"
  code=$(curl -s -o /dev/null -w '%{http_code}' -X POST -H "Origin: $origin" \
    -H 'Content-Type: application/json' -d '{"name":"x"}' http://127.0.0.1:8787/api/contact)
  [[ $code == 400 ]] && echo "  ok  $name: contact service accepts it (empty test message refused, as it should be)" \
    || echo "  !!  $name: contact service answered $code (expected 400)"
}
check_site "Dream Homes" dreamhomes-web 4330 /contact/    https://www.dreamhomes-realty.com
check_site "Medina's"    medinas-web    4331 /contact-us/ https://www.medinasmaterial.com

cat <<'EOF'

  Parking Solutions: publish it again so its contact page is built with the
  site key (its SERVER-GUIDE.md, section 3b).

  Then send a real message from each site's contact page and watch it go out:
    sudo journalctl -u site-contact -f

  Brevo must allow each site's sender, or it rejects the send:
    no-reply@dreamhomes-realty.com, no-reply@medinasmaterial.com,
    no-reply@parkingsolutions.bz
  Brevo → Senders, Domains & Dedicated IPs → Domains → add each domain, and
  put the DNS records it shows into Cloudflare.
  Enquiries go to the inboxes listed per site in /opt/site-contact/sites.json.
EOF
