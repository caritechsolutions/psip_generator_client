#!/bin/bash

# Update database configuration for EPG PSIP Client

EPG_PATH="/var/www/html/epg"

echo "EPG PSIP Database Configuration Updater"
echo "======================================="
echo ""

# Get current values
if [ -f "$EPG_PATH/epg_gen.php" ]; then
    CURRENT_HOST=$(grep -oP "\\\$dbHost = '\K[^']+" "$EPG_PATH/epg_gen.php" 2>/dev/null)
    CURRENT_NAME=$(grep -oP "\\\$dbName = '\K[^']+" "$EPG_PATH/epg_gen.php" 2>/dev/null)
    CURRENT_USER=$(grep -oP "\\\$dbUsername = '\K[^']+" "$EPG_PATH/epg_gen.php" 2>/dev/null)
fi

echo "Current configuration:"
echo "Host: ${CURRENT_HOST:-not set}"
echo "Database: ${CURRENT_NAME:-not set}"
echo "User: ${CURRENT_USER:-not set}"
echo ""

read -p "Database Host [${CURRENT_HOST}]: " DB_HOST
DB_HOST=${DB_HOST:-$CURRENT_HOST}

read -p "Database Name [${CURRENT_NAME}]: " DB_NAME
DB_NAME=${DB_NAME:-$CURRENT_NAME}

read -p "Database User [${CURRENT_USER}]: " DB_USER
DB_USER=${DB_USER:-$CURRENT_USER}

read -sp "Database Password: " DB_PASS
echo ""

# Update epg_gen.php
sed -i "s/\$dbHost = '.*'/\$dbHost = '$DB_HOST'/" "$EPG_PATH/epg_gen.php"
sed -i "s/\$dbName = '.*'/\$dbName = '$DB_NAME'/" "$EPG_PATH/epg_gen.php"
sed -i "s/\$dbUsername = '.*'/\$dbUsername = '$DB_USER'/" "$EPG_PATH/epg_gen.php"
sed -i "s/\$dbPassword = '.*'/\$dbPassword = '$DB_PASS'/" "$EPG_PATH/epg_gen.php"

# Test connection
echo ""
echo "Testing database connection..."
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" > /dev/null 2>&1; then
    echo "✓ Database configuration updated and tested successfully!"
else
    echo "✗ Could not connect to database with new settings"
    echo "Please check your configuration"
fi
