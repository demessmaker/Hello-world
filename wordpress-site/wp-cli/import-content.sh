#!/usr/bin/env bash
#
# import-content.sh - Create all pages with content from JSON files
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTENT_DIR="$SCRIPT_DIR/../content/pages"

echo "=== Importing Page Content ==="

# Helper: create a page and return its ID
create_page() {
  local title="$1"
  local slug="$2"
  local content_file="$3"

  # Extract html_content from JSON
  local html_content
  html_content=$(python3 -c "
import json, sys
with open('$content_file') as f:
    data = json.load(f)
print(data.get('html_content', ''))
")

  # Create the page
  local page_id
  page_id=$(wp post create \
    --post_type=page \
    --post_title="$title" \
    --post_name="$slug" \
    --post_status=publish \
    --post_content="$html_content" \
    --porcelain)

  echo "  Created page: $title (ID: $page_id)"
  echo "$page_id"
}

# Create all pages
echo "--- Creating Home page ---"
HOME_ID=$(create_page "Home" "home" "$CONTENT_DIR/home.json")

echo "--- Creating Courses page ---"
COURSES_ID=$(create_page "Courses" "courses" "$CONTENT_DIR/courses.json")

echo "--- Creating About Us page ---"
ABOUT_ID=$(create_page "About Us" "about-us" "$CONTENT_DIR/about.json")

echo "--- Creating Contact Us page ---"
CONTACT_ID=$(create_page "Contact Us" "contact-us" "$CONTENT_DIR/contact.json")

echo "--- Creating Our Store page ---"
STORE_ID=$(create_page "Our Store" "our-store" "$CONTENT_DIR/our-store.json")

echo "--- Creating Testimonials page ---"
TESTIMONIALS_ID=$(create_page "Testimonials" "testimonials" "$CONTENT_DIR/testimonials.json")

# Set static front page
echo "--- Setting Home as static front page ---"
wp option update show_on_front "page"
wp option update page_on_front "$HOME_ID"

echo ""
echo "=== All Pages Created ==="
echo "Page IDs:"
echo "  Home:         $HOME_ID"
echo "  Courses:      $COURSES_ID"
echo "  About Us:     $ABOUT_ID"
echo "  Contact Us:   $CONTACT_ID"
echo "  Our Store:    $STORE_ID"
echo "  Testimonials: $TESTIMONIALS_ID"

# Save page IDs for menu import
cat > "$SCRIPT_DIR/.page-ids.env" <<EOF
HOME_ID=$HOME_ID
COURSES_ID=$COURSES_ID
ABOUT_ID=$ABOUT_ID
CONTACT_ID=$CONTACT_ID
STORE_ID=$STORE_ID
TESTIMONIALS_ID=$TESTIMONIALS_ID
EOF

echo ""
echo "Page IDs saved to $SCRIPT_DIR/.page-ids.env"
echo "Next: Run import-menus.sh"
