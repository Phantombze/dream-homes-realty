#!/usr/bin/env bash
#
#   Changes the CMS superadmin's sign-in email and password and clears any
#   lockout — for when the password is forgotten (the CMS cannot send reset
#   emails). The CMS is shared, so this is the login for every site in it.
#
#   Run:  sudo bash /srv/src/dreamhomes/deploy/set-admin-login.sh new@email.com
#
#   The password is typed at a hidden prompt, twice. It is passed to Payload
#   through the environment of one process and never written to disk or shown.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

NEW_EMAIL=${1:-}
[[ $NEW_EMAIL =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || { echo "Usage: sudo bash $0 new@email.com"; exit 1; }

CMS=/srv/src/medinasmaterial/cms
[[ -f $CMS/src/payload.config.ts ]] || { echo "No CMS at $CMS"; exit 1; }

read -rsp "New password (12+ characters): " P1; echo
read -rsp "Same password again:           " P2; echo
[[ $P1 == "$P2" ]] || { echo "The two passwords differ — nothing changed."; exit 1; }
[[ ${#P1} -ge 12 ]] || { echo "Too short — use at least 12 characters. Nothing changed."; exit 1; }

# The script has to sit inside the CMS folder for its imports to resolve.
TMP=$CMS/scripts/.set-admin-login.tmp.ts
trap 'rm -f "$TMP"' EXIT
cat > "$TMP" <<'TS'
import { getPayload } from 'payload'
import config from '../src/payload.config'

const NEW_EMAIL = (process.env.NEW_EMAIL || '').trim().toLowerCase()
const NEW_PASSWORD = process.env.NEW_PASSWORD || ''

async function main() {
  const payload = await getPayload({ config })
  const { docs } = await payload.find({ collection: 'users', where: { role: { equals: 'superadmin' } }, limit: 5, depth: 0 })
  if (docs.length === 0) throw new Error('no superadmin found')
  if (docs.length > 1) throw new Error(`more than one superadmin (${docs.map((d: any) => d.email).join(', ')}) — change it in the admin panel instead`)
  const user: any = docs[0]
  const clash = await payload.find({ collection: 'users', where: { email: { equals: NEW_EMAIL } }, limit: 1, depth: 0 })
  if (clash.docs[0] && String(clash.docs[0].id) !== String(user.id)) throw new Error(`${NEW_EMAIL} already belongs to another user`)
  await payload.update({ collection: 'users', id: user.id, data: { email: NEW_EMAIL, password: NEW_PASSWORD } as any })
  await payload.unlock({ collection: 'users', data: { email: NEW_EMAIL } as any, overrideAccess: true })
  console.log(`\n  Sign-in changed: ${user.email} → ${NEW_EMAIL}. Password updated, lockout cleared.\n`)
}

try {
  await main()
  process.exit(0)
} catch (err) {
  console.error(`\n  ${err instanceof Error ? err.message : err}\n`)
  process.exit(1)
}
TS

cd "$CMS"
set -a; . /etc/payload-cms/payload.env; set +a
NEW_EMAIL=$NEW_EMAIL NEW_PASSWORD=$P1 npx payload run scripts/.set-admin-login.tmp.ts 2>&1 | grep -vE '^\[[0-9:]+\] (INFO|WARN)'
unset P1 P2
echo "  Sign in at https://cp.panelbz.cloud/admin with $NEW_EMAIL"
