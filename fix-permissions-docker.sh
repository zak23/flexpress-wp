#!/bin/bash

echo "🔧 Fixing FlexPress file permissions using Docker..."

# Stop the containers
echo "Stopping containers..."
docker-compose down

# Create a temporary container to fix permissions
echo "Creating temporary container to fix permissions..."
docker run --rm -v "$(pwd)/wp-content:/wp-content" alpine:latest sh -c "
    # Only change ownership of theme files, not system files
    chown -R 1000:1000 /wp-content/themes/flexpress/
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
    # Fix debug.log file ownership for editing
    chown 1000:1000 /wp-content/debug.log 2>/dev/null || true
    chmod 644 /wp-content/debug.log 2>/dev/null || true
"

# Start the containers
echo "Starting containers..."
docker-compose up -d

echo "✅ Permissions fixed! You should now be able to edit files."
echo "🌐 WordPress: http://localhost:8085"
echo "🗄️ phpMyAdmin: http://localhost:8086"
