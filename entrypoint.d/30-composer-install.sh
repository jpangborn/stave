#!/bin/bash
set -e

echo "Install Composer Dependencies"

cd /var/www/html
composer install
