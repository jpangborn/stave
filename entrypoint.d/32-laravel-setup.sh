#!/bin/bash
set -e

echo "Running Migrations"

cd /var/www/html
php artisan migrate --force

echo "Building Assets"
npm ci
npm run build

echo "Creating Caches"
php artisan optimize
