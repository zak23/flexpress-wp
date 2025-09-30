#!/bin/bash
# FlexPress Development URL Switcher
# This script helps switch between development and production URLs

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Database credentials
DB_USER="flexpress_user"
DB_PASS="flexpress_password_2024"
DB_NAME="flexpress_db"

echo -e "${YELLOW}FlexPress URL Configuration Tool${NC}"
echo "=================================="

if [ "$1" = "dev" ]; then
    echo -e "${GREEN}Switching to development URLs...${NC}"
    docker-compose exec -T db mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "UPDATE wp_options SET option_value = 'http://localhost:8085' WHERE option_name IN ('siteurl', 'home');"
    echo -e "${GREEN}✓ URLs updated to: http://localhost:8085${NC}"
    
elif [ "$1" = "prod" ]; then
    echo -e "${GREEN}Switching to production URLs...${NC}"
    docker-compose exec -T db mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "UPDATE wp_options SET option_value = 'https://zakspov.com' WHERE option_name IN ('siteurl', 'home');"
    echo -e "${GREEN}✓ URLs updated to: https://zakspov.com${NC}"
    
elif [ "$1" = "status" ]; then
    echo -e "${YELLOW}Current URL configuration:${NC}"
    docker-compose exec -T db mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT option_name, option_value FROM wp_options WHERE option_name IN ('siteurl', 'home');"
    
else
    echo -e "${RED}Usage: $0 [dev|prod|status]${NC}"
    echo ""
    echo "Commands:"
    echo "  dev     - Switch to development URLs (localhost:8085)"
    echo "  prod    - Switch to production URLs (zakspov.com)"
    echo "  status  - Show current URL configuration"
    echo ""
    echo "Examples:"
    echo "  $0 dev    # Switch to development"
    echo "  $0 prod   # Switch to production"
    echo "  $0 status # Check current URLs"
fi
