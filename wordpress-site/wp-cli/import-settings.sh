#!/usr/bin/env bash
#
# import-settings.sh - Configure WordPress site settings
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONTENT_DIR="$SCRIPT_DIR/../content"

echo "=== Importing Site Settings ==="

# Site identity
wp option update blogname "West Island Conservatory of Music"
wp option update blogdescription "Making music magical"

# Timezone and locale
wp option update timezone_string "America/Toronto"
wp option update date_format "F j, Y"
wp option update time_format "g:i A"

# Permalink structure (pretty URLs)
wp rewrite structure '/%postname%/'
wp rewrite flush

# Reading settings - will be updated after pages are created
# (static front page set in import-content.sh)

# Disable comments on pages by default
wp option update default_comment_status "closed"

# Set uploads year/month organization
wp option update uploads_use_yearmonth_folders 1

echo "=== Site Settings Imported ==="
