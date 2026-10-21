#!/usr/bin/env bash
#
#   First deployment of Dream Homes on the server: puts both code bases in
#   place, then runs deploy/install.sh.
#
#     /srv/src/dreamhomes        ← github.com/Phantombze/dream-homes-realty (public)
#     /srv/src/medinasmaterial   ← /tmp/medinasmaterial.bundle (the private CMS
#                                  repository, copied up with scp — no GitHub
#                                  credentials ever need to live on the server)
#
#   Run:  sudo bash /tmp/bootstrap.sh
#
#   The CMS folder was not a git checkout before this. Its repository starts
#   with a snapshot of that folder taken on 2026-10-21; if anything tracked in
#   the folder has changed since, this stops and lists it rather than
#   overwriting someone's edit.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }

say() { printf '\n\033[1;36m== %s\033[0m\n' "$*"; }
ok()  { printf '   \033[32mok\033[0m  %s\n' "$*"; }
die() { printf '   \033[31mFAIL\033[0m %s\n' "$*"; exit 1; }

OWNER=medmat
SITE=/srv/src/dreamhomes
CMS_ROOT=/srv/src/medinasmaterial
BUNDLE=${BUNDLE:-/tmp/medinasmaterial.bundle}
SNAPSHOT=ccb1d981035af780b90b4b5f67070e46146185a7   # "Snapshot of /srv/src/medinasmaterial from the server"
as_owner() { sudo -u "$OWNER" -H "$@"; }

# ---------------------------------------------------------------------------
say "Website code → $SITE"
# ---------------------------------------------------------------------------
install -d -o "$OWNER" -g "$OWNER" -m 0775 "$SITE"
if [[ -d $SITE/.git ]]; then
  as_owner git -C "$SITE" pull --ff-only origin main
else
  # The folder already holds legacy/ (the old PHP site copy); it is ignored by
  # the repository and stays where it is.
  as_owner git -C "$SITE" init -q -b main
  as_owner git -C "$SITE" remote add origin https://github.com/Phantombze/dream-homes-realty.git
  as_owner git -C "$SITE" fetch -q origin main
  as_owner git -C "$SITE" checkout -q -f -B main origin/main
fi
ok "$(as_owner git -C "$SITE" log --oneline -1)"

# ---------------------------------------------------------------------------
say "CMS code → $CMS_ROOT"
# ---------------------------------------------------------------------------
[[ -f $BUNDLE ]] || die "no $BUNDLE — copy medinasmaterial.bundle up with scp first"
chmod 644 "$BUNDLE"

FIRST=0
if [[ ! -d $CMS_ROOT/.git ]]; then
  FIRST=1
  as_owner git -C "$CMS_ROOT" init -q -b main
  # The repository was made on Windows, which records no execute bits; compare
  # content only, or every executable script would count as "changed".
  as_owner git -C "$CMS_ROOT" config core.fileMode false
  # A few files on the server have Windows line endings (CRLF) where the
  # repository stores LF; same content, so compare with CRs stripped.
  as_owner git -C "$CMS_ROOT" config core.autocrlf input
fi
# `git bundle verify` needs a repository to run in, hence after the init.
as_owner git -C "$CMS_ROOT" bundle verify -q "$BUNDLE" >/dev/null 2>&1 || die "$BUNDLE is damaged — copy it again"
as_owner git -C "$CMS_ROOT" fetch -q "$BUNDLE" main:refs/remotes/bundle/main

if (( FIRST )); then
  # Compare the folder as it is now with the snapshot it was taken from.
  as_owner git -C "$CMS_ROOT" read-tree "$SNAPSHOT"
  if ! as_owner git -C "$CMS_ROOT" diff --quiet; then
    echo "   These files changed on the server since the snapshot:"
    as_owner git -C "$CMS_ROOT" diff --stat | sed 's/^/     /'
    die "not overwriting them — copy them into the CMS repository on the laptop, make a new bundle, and run this again"
  fi
  ok "folder matches the snapshot — safe to update"
else
  as_owner git -C "$CMS_ROOT" diff --quiet HEAD || die "uncommitted changes in $CMS_ROOT — see: git -C $CMS_ROOT status"
fi
# Untracked and ignored files — node_modules, .next, uploaded media, the
# original images, .env — are left exactly as they are.
as_owner git -C "$CMS_ROOT" reset -q --hard refs/remotes/bundle/main
ok "$(as_owner git -C "$CMS_ROOT" log --oneline -1)"

# ---------------------------------------------------------------------------
say "Install"
# ---------------------------------------------------------------------------
exec bash "$SITE/deploy/install.sh"
