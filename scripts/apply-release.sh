#!/usr/bin/env bash
set -euo pipefail
SRC="$(cd "$(dirname "$0")/.." && pwd)"
DEST="${1:-/opt/cavetrip-manager}"
rsync -av "$SRC/" "$DEST/" --exclude='.git/' --exclude='scripts/apply-release.sh' --exclude='RELEASE.md'
echo "Release files applied to $DEST"
echo "Review with: git -C $DEST diff"
