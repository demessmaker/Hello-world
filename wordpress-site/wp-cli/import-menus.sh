#!/usr/bin/env bash
#
# import-menus.sh - Create navigation menus and assign to theme locations
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Load page IDs from content import
if [[ -f "$SCRIPT_DIR/.page-ids.env" ]]; then
  source "$SCRIPT_DIR/.page-ids.env"
else
  echo "ERROR: .page-ids.env not found. Run import-content.sh first."
  exit 1
fi

echo "=== Creating Navigation Menus ==="

# Delete existing primary menu if it exists
wp menu delete "Primary Menu" 2>/dev/null || true

# Create the primary menu
wp menu create "Primary Menu"

echo "--- Adding menu items ---"

# Add pages to menu in order
wp menu item add-post "Primary Menu" "$HOME_ID" --title="Home" --position=1
wp menu item add-post "Primary Menu" "$COURSES_ID" --title="Courses" --position=2
wp menu item add-post "Primary Menu" "$STORE_ID" --title="Our Store" --position=3
wp menu item add-post "Primary Menu" "$TESTIMONIALS_ID" --title="Testimonials" --position=4
wp menu item add-post "Primary Menu" "$ABOUT_ID" --title="About Us" --position=5
wp menu item add-post "Primary Menu" "$CONTACT_ID" --title="Contact Us" --position=6

# Assign menu to primary theme location
# Note: The location name varies by theme. Common names: primary, main-menu, header-menu
echo "--- Assigning menu to theme locations ---"
wp menu location assign "Primary Menu" primary 2>/dev/null || \
wp menu location assign "Primary Menu" main-menu 2>/dev/null || \
wp menu location assign "Primary Menu" header-menu 2>/dev/null || \
echo "WARNING: Could not auto-assign menu location. Assign manually in Appearance > Menus."

echo ""
echo "=== Navigation Menu Created ==="
echo "Menu: Primary Menu (6 items)"
