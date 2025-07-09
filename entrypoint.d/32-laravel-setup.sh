#!/bin/bash
set -e

echo "Running Migrations"

cd /var/www/html
php artisan migrate --force

echo "Creating Caches"
php artisan optimize
