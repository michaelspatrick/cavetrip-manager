#!/usr/bin/env bash
set -euo pipefail
TARGET="${1:-/opt/cavetrip-manager}"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ ! -d "$TARGET" ]]; then
  echo "Target directory does not exist: $TARGET" >&2
  exit 1
fi
rsync -av \
  --exclude='RELEASE.md' \
  --exclude='scripts/apply-release.sh' \
  "$HERE/" "$TARGET/"
echo "Applied v0.17.5 patch to $TARGET"
