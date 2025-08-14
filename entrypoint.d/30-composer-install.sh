#!/bin/bash
set -e

echo "Install Composer Dependencies"

if [ -n "$COMPOSER_AUTH_JSON_BASE64" ]; then
    mkdir -p /root/.composer
    echo "$COMPOSER_AUTH_JSON_BASE64" | base64 -d > /root/.composer/auth.json
    chmod 600 /root/.composer/auth.json
fi

cd /var/www/html
composer install --no-interaction --prefer-dist --optimize-autoloader
