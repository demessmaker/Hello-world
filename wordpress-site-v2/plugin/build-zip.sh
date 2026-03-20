#!/bin/bash
# Build script for WICM Developer Importer plugin
# Creates a distributable ZIP file

PLUGIN_DIR="wicm-developer-importer"
ZIP_NAME="wicm-developer-importer.zip"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cd "$SCRIPT_DIR" || exit 1

# Remove old zip if it exists
if [ -f "$ZIP_NAME" ]; then
    rm "$ZIP_NAME"
    echo "Removed existing $ZIP_NAME"
fi

# Check plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Error: Plugin directory '$PLUGIN_DIR' not found."
    echo "Run this script from the plugin/ directory."
    exit 1
fi

# Create zip excluding unnecessary files
zip -r "$ZIP_NAME" "$PLUGIN_DIR/" \
    -x "$PLUGIN_DIR/.git/*" \
    -x "$PLUGIN_DIR/.gitignore" \
    -x "$PLUGIN_DIR/.DS_Store" \
    -x "$PLUGIN_DIR/node_modules/*" \
    -x "$PLUGIN_DIR/*.log" \
    -x "**/.DS_Store"

if [ $? -eq 0 ]; then
    echo ""
    echo "Successfully created: $SCRIPT_DIR/$ZIP_NAME"
    echo "File size: $(du -h "$ZIP_NAME" | cut -f1)"
    echo ""
    echo "Upload this zip via WordPress Admin > Plugins > Add New > Upload Plugin"
else
    echo "Error: Failed to create zip file."
    exit 1
fi
