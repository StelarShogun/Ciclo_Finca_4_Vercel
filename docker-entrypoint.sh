#!/bin/bash
set -e

cd /var/www/html

mkdir -p storage/framework/{sessions,cache,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
