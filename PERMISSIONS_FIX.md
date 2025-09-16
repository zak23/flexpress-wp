# FlexPress Permission Fix Guide

## The Problem
When using Docker bind mounts, files created inside the container are owned by the container's user (`www-data`), but you're trying to edit them as your local user (`zak`). This causes "Insufficient permissions" errors when trying to save files.

## Quick Solution
Run the permission fix script:

```bash
./fix-permissions-docker.sh
```

This script will:
1. Stop the Docker containers
2. Change ownership of theme files to your user (`zak:zak`)
3. Change ownership of `debug.log` to your user for editing
4. Set proper file permissions
5. Restart the containers

## Manual Solution
If you prefer to do it manually:

```bash
# Stop containers
docker-compose down

# Fix ownership
sudo chown -R zak:zak wp-content/

# Set proper permissions
chmod -R 755 wp-content/
find wp-content/themes/flexpress/ -name "*.php" -exec chmod 644 {} \;
find wp-content/themes/flexpress/ -name "*.css" -exec chmod 644 {} \;
find wp-content/themes/flexpress/ -name "*.js" -exec chmod 644 {} \;

# Restart containers
docker-compose up -d
```

## Why This Happens
Docker bind mounts preserve the host file ownership. When the container creates files as `www-data` (UID 33), they appear on your host system as owned by UID 33, which doesn't match your user (UID 1000).

## Alternative Solutions
1. **Use VS Code with Remote Containers**: Edit files directly inside the container
2. **Use a different editor**: Some editors handle permission issues better
3. **Use Docker volumes**: Instead of bind mounts, use Docker volumes (but you lose direct file access)

## When to Run the Fix
Run the permission fix script whenever you:
- Get "Insufficient permissions" errors
- Create new files through WordPress admin
- Install new plugins or themes
- Update existing files through the web interface

## Access URLs
- WordPress: http://localhost:8085
- phpMyAdmin: http://localhost:8086
