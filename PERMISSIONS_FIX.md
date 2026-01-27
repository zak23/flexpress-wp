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

---

## Theme Update Failed

If you see this error when updating the theme from WordPress admin:

> **The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.**

This happens because:
- The theme directory is owned by your local user (UID 1000) so you can edit files
- WordPress runs as `www-data` (UID 33) inside the container
- WordPress cannot delete/overwrite files it doesn't own when performing the update

### Solution

**Step 1: Prepare for update**

Run the prepare script to make the theme writable by WordPress:

```bash
./prepare-theme-for-update.sh
```

**Step 2: Run the theme update**

Go to WordPress admin → Appearance → Themes and upload/update the theme. It should succeed now.

**Step 3: Restore editing permissions**

Run the fix script to restore local editing:

```bash
./fix-permissions-docker.sh
```

### Manual Alternative

If you prefer to do it manually:

```bash
# Stop containers
docker-compose down

# Make theme writable by WordPress
docker run --rm -v "$(pwd)/wp-content:/wp-content" alpine:latest sh -c "chown -R 33:33 /wp-content/themes/flexpress/"

# Start containers
docker-compose up -d

# Now run the theme update in WordPress admin...

# After update, restore editing permissions
./fix-permissions-docker.sh
```

---

## Access URLs
- WordPress: http://localhost:8085
- phpMyAdmin: http://localhost:8086
