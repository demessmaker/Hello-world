#!/usr/bin/env bash
#
# setup-all.sh - Master script to set up the complete WordPress site
#
# Usage:
#   ./setup-all.sh <site_url> <db_name> <db_user> <db_pass> [db_host]
#
# Environment variables (optional):
#   ADMIN_USER  - WordPress admin username (default: admin)
#   ADMIN_PASS  - WordPress admin password (default: changeme)
#   ADMIN_EMAIL - WordPress admin email (default: westislandmusic@gmail.com)
#
# Example:
#   ./setup-all.sh http://localhost/wordpress wpdb root rootpass
#   ADMIN_PASS=securepass ./setup-all.sh https://mysite.com wpdb user pass db.host.com
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "============================================"
echo "  West Island Conservatory of Music"
echo "  WordPress Site Setup"
echo "============================================"
echo ""

# Step 1: Install WordPress
echo ">>> Step 1/4: Installing WordPress..."
bash "$SCRIPT_DIR/install.sh" "$@"
echo ""

# Step 2: Import settings
echo ">>> Step 2/4: Importing site settings..."
bash "$SCRIPT_DIR/import-settings.sh"
echo ""

# Step 3: Import content
echo ">>> Step 3/4: Importing page content..."
bash "$SCRIPT_DIR/import-content.sh"
echo ""

# Step 4: Import menus
echo ">>> Step 4/4: Creating navigation menus..."
bash "$SCRIPT_DIR/import-menus.sh"
echo ""

# Cleanup
rm -f "$SCRIPT_DIR/.page-ids.env"

echo "============================================"
echo "  Setup Complete!"
echo "============================================"
echo ""
echo "Your WordPress site is ready at: ${1:-<site_url>}"
echo ""
echo "Pages created:"
echo "  - Home (static front page)"
echo "  - Courses"
echo "  - About Us"
echo "  - Contact Us"
echo "  - Our Store"
echo "  - Testimonials"
echo ""
echo "Next steps:"
echo "  1. Log in to wp-admin and choose a theme"
echo "  2. Customize colors and typography"
echo "  3. Add images to pages"
echo "  4. Configure Contact Form 7 on the Contact page"
echo "  5. Update admin password if using default"
