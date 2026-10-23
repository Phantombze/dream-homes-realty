#!/usr/bin/env bash
#
#   Moved. Every site now has its own contact-form keys, and one script in the
#   CMS repository sets them all:
#
#     sudo bash /srv/src/cms/ops/contact-forms/set-keys.sh
#
#   This forwards there so older notes that name this file still work.
#
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "Run with sudo."; exit 1; }
exec bash /srv/src/cms/ops/contact-forms/set-keys.sh "$@"
