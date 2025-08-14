#!/bin/bash
set -e

cd /var/www/html

echo "Setup Composer Private Repo Authentication"
echo "$COMPOSER_AUTH_JSON_BASE64" | base64 -d > auth.json

echo "Install Composer Dependencies"
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
