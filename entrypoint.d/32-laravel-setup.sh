#!/bin/bash
set -e

echo "Running Migrations"

cd /var/www/html
php artisan migrate --force

echo "Building Assets"

echo "Creating Caches"
php artisan optimize
