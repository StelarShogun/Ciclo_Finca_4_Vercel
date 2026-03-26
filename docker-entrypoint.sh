#!/bin/bash

cd /var/www/html

# Crear directorios necesarios
mkdir -p storage/framework/{sessions,cache,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Crear .env si no existe
if [ ! -f .env ]; then
    cp .env.example .env
    echo ">>> .env creado desde .env.example"
fi

# Generar APP_KEY si no existe
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    php artisan key:generate --force
    echo ">>> APP_KEY generada"
fi

# Instalar dependencias (Node + Composer)
echo ">>> Instalando dependencias..."
npm install
composer install --no-interaction

# 🔥 INICIAR VITE (IMPORTANTE)
echo ">>> Iniciando Vite..."
npm run dev &

# Laravel cache (opcional en dev, pero lo dejo)do
php artisan config:clear
php artisan view:clear

# Storage link
php artisan storage:link --force 2>/dev/null || true

# Permisos
chown -R www-data:www-data storage bootstrap/cache

echo ">>> Iniciando Apache..."
exec apache2-foreground