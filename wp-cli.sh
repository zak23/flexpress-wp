#!/bin/bash

# WP-CLI Docker wrapper script for FlexPress
# Usage: ./wp-cli.sh [wp-cli-command]
# Example: ./wp-cli.sh plugin list
# Example: ./wp-cli.sh db export backup.sql

CONTAINER_NAME="flexpress_wordpress"

# Check if container is running
if ! docker ps | grep -q $CONTAINER_NAME; then
    echo "❌ WordPress container '$CONTAINER_NAME' is not running."
    echo "💡 Start it with: docker-compose up -d"
    exit 1
fi

# Run wp-cli command in the container
if [ $# -eq 0 ]; then
    echo "🔧 WP-CLI Docker Wrapper for FlexPress"
    echo ""
    echo "Usage: $0 [wp-cli-command]"
    echo ""
    echo "Examples:"
    echo "  $0 plugin list"
    echo "  $0 db export backup.sql"
    echo "  $0 user list"
    echo "  $0 theme status"
    echo "  $0 core version"
    echo ""
    echo "💡 Run '$0 help' to see all available commands"
    exit 0
fi

# Execute wp-cli command in the WordPress container as root with --allow-root flag
docker exec -u root $CONTAINER_NAME /root/.composer/vendor/bin/wp --allow-root "$@"
