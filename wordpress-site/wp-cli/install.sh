#!/usr/bin/env bash
#
# install.sh - WordPress core installation and plugin setup
# Usage: ./install.sh <site_url> <db_name> <db_user> <db_pass> [db_host]
#

set -euo pipefail

SITE_URL="${1:?Usage: $0 <site_url> <db_name> <db_user> <db_pass> [db_host]}"
DB_NAME="${2:?Database name required}"
DB_USER="${3:?Database user required}"
DB_PASS="${4:?Database password required}"
DB_HOST="${5:-localhost}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-changeme}"
ADMIN_EMAIL="${ADMIN_EMAIL:-westislandmusic@gmail.com}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Downloading WordPress Core ==="
wp core download --force

echo "=== Creating wp-config.php ==="
wp config create \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --dbhost="$DB_HOST" \
  --force

echo "=== Creating Database ==="
wp db create 2>/dev/null || echo "Database already exists, continuing..."

echo "=== Installing WordPress ==="
wp core install \
  --url="$SITE_URL" \
  --title="West Island Conservatory of Music" \
  --admin_user="$ADMIN_USER" \
  --admin_password="$ADMIN_PASS" \
  --admin_email="$ADMIN_EMAIL" \
  --skip-email

echo "=== Installing Plugins ==="
wp plugin install classic-editor --activate
wp plugin install contact-form-7 --activate
wp plugin install wordpress-seo --activate

echo "=== Installing SEO Sitemap Plugin ==="
wp plugin install google-sitemap-generator --activate 2>/dev/null || echo "Sitemap plugin skipped (Yoast includes sitemap functionality)"

echo "=== Removing Default Content ==="
wp post delete 1 --force 2>/dev/null || true   # Hello World post
wp post delete 2 --force 2>/dev/null || true   # Sample Page

echo "=== WordPress Installation Complete ==="
echo "Site URL: $SITE_URL"
echo "Admin: $ADMIN_USER / $ADMIN_PASS"
echo ""
echo "Next: Run import-settings.sh, then import-content.sh, then import-menus.sh"
