#!/bin/bash
set -e

echo "Decrypting Env File"

cd /var/www/html
php artisan env:decrypt --env=production --force
mv .env.production .env
