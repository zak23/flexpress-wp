#!/bin/bash

echo "🔧 Fixing WordPress file permissions for plugin updates..."

# Stop the containers
echo "Stopping containers..."
docker-compose down

# Fix permissions for wp-content directory
echo "Setting proper permissions for wp-content..."
docker run --rm -v "$(pwd)/wp-content:/wp-content" alpine:latest sh -c "
    # Set ownership to www-data (UID 33:GID 33)
    chown -R 33:33 /wp-content/
    
    # Set proper permissions
    find /wp-content/ -type d -exec chmod 755 {} \;
    find /wp-content/ -type f -exec chmod 644 {} \;
    
    # Ensure plugin directories are writable
    chmod 755 /wp-content/plugins/
    chmod 755 /wp-content/themes/
    chmod 755 /wp-content/uploads/
    
    # Make sure upgrade directories exist and are writable
    mkdir -p /wp-content/upgrade/
    mkdir -p /wp-content/upgrade-temp-backup/
    chown -R 33:33 /wp-content/upgrade/
    chown -R 33:33 /wp-content/upgrade-temp-backup/
    chmod 755 /wp-content/upgrade/
    chmod 755 /wp-content/upgrade-temp-backup/
    
    echo '✅ Permissions set for WordPress to write files'
"

# Start the containers
echo "Starting containers..."
docker-compose up -d

echo "✅ WordPress permissions fixed!"
echo "🌐 WordPress: http://localhost:8085"
echo "🗄️ phpMyAdmin: http://localhost:8086"
echo ""
echo "You should now be able to update plugins without FTP credentials."
