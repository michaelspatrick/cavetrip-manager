#!/usr/bin/env bash
set -euo pipefail
TARGET="${1:-/opt/cavetrip-manager}"
SOURCE="$(cd "$(dirname "$0")/.." && pwd)"
rsync -av --exclude='RELEASE.md' --exclude='docs/' --exclude='scripts/' "$SOURCE/" "$TARGET/"
mkdir -p "$TARGET/docs/Releases"
cp "$SOURCE/docs/Releases/v0.17.3.md" "$TARGET/docs/Releases/v0.17.3.md"
echo "Applied v0.17.3 files to $TARGET"
