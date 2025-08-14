#!/bin/bash
set -e

echo "Setup Composer Private Repo Authentication"
echo "$COMPOSER_AUTH_JSON_BASE64" | base64 -d > /var/www/html/auth.json

echo "Install Composer Dependencies"
cd /var/www/html
composer install --no-interaction --prefer-dist --optimize-autoloader
