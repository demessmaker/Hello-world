#!/usr/bin/env bash
#
# build-zip.sh - Package the WICM Content Importer plugin as a ZIP file
# The ZIP can be uploaded directly via WordPress admin: Plugins > Add New > Upload Plugin
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ZIP_NAME="wicm-content-importer.zip"

# Remove old zip if exists
rm -f "$ZIP_NAME"

# Create zip (WordPress expects the plugin folder inside the zip)
zip -r "$ZIP_NAME" wicm-content-importer/

echo ""
echo "Plugin packaged: $SCRIPT_DIR/$ZIP_NAME"
echo ""
echo "To install:"
echo "  1. Log in to your WordPress admin (wp-admin)"
echo "  2. Go to Plugins > Add New > Upload Plugin"
echo "  3. Choose $ZIP_NAME and click Install Now"
echo "  4. Activate the plugin"
echo "  5. Go to Tools > WICM Importer"
echo "  6. Click 'Import Content Now'"
