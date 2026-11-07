#!/usr/bin/env bash
# Usage: ./build-theme-zip.sh [--output /private/path/flexpress.zip]
# Versions are changed explicitly in Git, never as a side effect of packaging.
set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
exec python3 "${SCRIPT_DIR}/scripts/build-theme.py" "$@"
