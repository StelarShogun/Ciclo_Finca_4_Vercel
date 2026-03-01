#!/bin/bash

cd /var/www/html

mkdir -p storage/framework/{sessions,cache,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

if [ ! -f .env ]; then
    cp .env.example .env
    echo ">>> .env creado desde .env.example"
fi

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
    echo ">>> APP_KEY generada"
fi

php artisan config:cache  || echo "WARN: config:cache falló"
php artisan view:cache    || echo "WARN: view:cache falló"
php artisan storage:link --force 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache

echo ">>> Iniciando Apache..."
exec apache2-foreground
