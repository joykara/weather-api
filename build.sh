#!/usr/bin/env bash
set -o errexit

# PHP dependencies
composer install --no-dev --optimize-autoloader

# Laravel setup
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional: run migrations
php artisan migrate --force
