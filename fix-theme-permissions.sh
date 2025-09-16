#!/bin/bash

echo "🔧 Fixing FlexPress theme permissions for editing..."

# Stop the containers
echo "Stopping containers..."
docker-compose down

# Fix permissions for theme editing
echo "Setting theme files for local editing..."
docker run --rm -v "$(pwd)/wp-content:/wp-content" alpine:latest sh -c "
    # Set theme files to your user ownership for editing
    chown -R 1000:1000 /wp-content/themes/flexpress/
    chmod -R 755 /wp-content/themes/flexpress/
    find /wp-content/themes/flexpress/ -name '*.php' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.css' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.js' -exec chmod 644 {} \;
    find /wp-content/themes/flexpress/ -name '*.md' -exec chmod 644 {} \;
    
    # Ensure other wp-content directories remain www-data owned for WordPress
    chown -R 33:33 /wp-content/uploads/
    chown -R 33:33 /wp-content/plugins/
    chown -R 33:33 /wp-content/languages/
    chown -R 33:33 /wp-content/upgrade/
    chown -R 33:33 /wp-content/upgrade-temp-backup/
    chown -R 33:33 /wp-content/ai1wm-backups/
    
    echo '✅ Theme files editable, WordPress directories writable'
"

# Start the containers
echo "Starting containers..."
docker-compose up -d

echo "✅ Theme permissions fixed!"
echo "📝 You can now edit theme files in your IDE"
echo "🌐 WordPress: http://localhost:8085"
echo "🗄️ phpMyAdmin: http://localhost:8086"
