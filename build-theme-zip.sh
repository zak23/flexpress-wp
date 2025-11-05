#!/bin/bash
# Build a clean WordPress theme zip file for deployment
# Excludes: vendor/, test files, git files, documentation, etc.

set -e

# Get the script directory (project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="${SCRIPT_DIR}/wp-content/themes/flexpress"
OUTPUT_DIR="${SCRIPT_DIR}"
ZIP_NAME="flexpress-theme.zip"
TEMP_DIR=$(mktemp -d)

echo "📦 Building clean WordPress theme zip..."

# Clean up any existing zip
rm -f "${OUTPUT_DIR}/${ZIP_NAME}"

# Copy theme to temp directory
echo "📋 Copying theme files..."
cp -r "${THEME_DIR}" "${TEMP_DIR}/flexpress"

# Remove files that shouldn't be in the theme zip
echo "🧹 Cleaning up unnecessary files..."

cd "${TEMP_DIR}/flexpress"

# Remove vendor directory (Composer dependencies - too large and not needed)
rm -rf vendor/

# Remove test files
rm -f test-*.php
rm -f debug-*.php

# Remove git files
rm -rf .git/
rm -f .gitignore
rm -f .gitattributes

# Remove IDE files
rm -rf .vscode/
rm -rf .idea/
rm -f *.code-workspace
rm -f *.iml

# Remove documentation files (optional - uncomment if you want to exclude them)
# rm -f *.md
# rm -rf docs/

# Remove OS files
find . -name ".DS_Store" -delete
find . -name "Thumbs.db" -delete
find . -name "*.swp" -delete
find . -name "*~" -delete

# Remove build artifacts
rm -rf assets/dist/
rm -rf assets/build/

# Remove composer files (not needed in deployed theme)
rm -f composer.json
rm -f composer.lock

# Remove linting configs (not needed in deployed theme)
rm -f phpcs.xml
rm -f phpstan.neon

# Remove .cursorrules (not needed in deployed theme)
rm -f .cursorrules

# Remove .wakatime-project (not needed in deployed theme)
rm -f .wakatime-project

# Remove wordpress stubs (development only)
rm -f wordpress-stubs.php
rm -f stubs.php

# Create the zip file (from parent directory so zip contains flexpress/ folder)
cd "${TEMP_DIR}"
zip -r "${OUTPUT_DIR}/${ZIP_NAME}" flexpress/ -x "*.git/*" "*.DS_Store" "*.swp" > /dev/null

# Clean up temp directory
rm -rf "${TEMP_DIR}"

# Get final size
ZIP_SIZE=$(du -h "${OUTPUT_DIR}/${ZIP_NAME}" | cut -f1)

echo "✅ Theme zip created: ${OUTPUT_DIR}/${ZIP_NAME}"
echo "📊 Size: ${ZIP_SIZE}"
echo ""
echo "📤 Ready to upload to WordPress!"

