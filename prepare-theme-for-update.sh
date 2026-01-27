#!/bin/bash

echo "🔧 Preparing FlexPress theme for WordPress dashboard update..."
echo ""
echo "This script makes the theme writable by WordPress (www-data) so you can"
echo "run in-dashboard theme updates. After updating, run ./fix-permissions-docker.sh"
echo "to restore local editing permissions."
echo ""

# Stop the containers
echo "Stopping containers..."
docker-compose down

# Create a temporary container to fix permissions for update
echo "Setting theme ownership for WordPress updates..."
docker run --rm -v "$(pwd)/wp-content:/wp-content" alpine:latest sh -c "
    # Set theme files to www-data ownership for WordPress updates
    chown -R 33:33 /wp-content/themes/flexpress/
    chmod -R 755 /wp-content/themes/flexpress/
    find /wp-content/themes/flexpress/ -name '*.php' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.css' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.js' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.md' -exec chmod 644 {} \;
    
    # Ensure other wp-content directories remain www-data owned
    chown -R 33:33 /wp-content/uploads/
    chown -R 33:33 /wp-content/plugins/
    chown -R 33:33 /wp-content/languages/
    chown -R 33:33 /wp-content/upgrade/
    chown -R 33:33 /wp-content/upgrade-temp-backup/
    chown -R 33:33 /wp-content/ai1wm-backups/
    
    echo '✅ Theme ownership set to www-data (33:33)'
"

# Start the containers
echo "Starting containers..."
docker-compose up -d

echo ""
echo "✅ Theme ready for WordPress update!"
echo ""
echo "Next steps:"
echo "  1. Go to WordPress admin → Appearance → Themes"
echo "  2. Upload/update the theme"
echo "  3. Run ./fix-permissions-docker.sh to restore local editing"
echo ""
echo "🌐 WordPress: http://localhost:8085"
echo "🗄️ phpMyAdmin: http://localhost:8086"
