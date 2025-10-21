#!/bin/bash

# Fix permissions for service worker file to be writable by WordPress container
# This script ensures the service worker can be updated by the web server

echo "Fixing service worker permissions..."

# Make the service worker file writable by all users
chmod 666 /home/zak/projects/flexpress/wp-content/themes/flexpress/sw.js

# Also ensure the theme directory is writable by the web server
chmod -R 755 /home/zak/projects/flexpress/wp-content/themes/flexpress/

echo "Service worker permissions fixed!"
echo "File permissions:"
ls -la /home/zak/projects/flexpress/wp-content/themes/flexpress/sw.js
