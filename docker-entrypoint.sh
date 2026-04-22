#!/bin/bash

cd /var/www/html

# El bind mount .:/var/www/html oculta el vendor de la imagen; si falta dependencias, instalar.
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/thecodingmachine/safe/lib/special_cases.php ]; then
    echo ">>> vendor incompleto o ausente — ejecutando composer install…"
    composer install --no-interaction --no-progress --prefer-dist
fi

# Crear directorios necesarios
mkdir -p storage/framework/{sessions,cache,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Render: preferir SIEMPRE las variables del servicio (Dashboard) y no un `.env` generado por plantilla.
# Esto evita el síntoma clásico: Laravel intenta `127.0.0.1:3306` aunque el dashboard tenga Aiven configurado.
if [ -n "${RENDER:-}" ] && [ -f .env ]; then
    rm -f .env
    echo ">>> Render: se eliminó .env para forzar configuración vía Environment Variables del servicio."
fi

# Crear .env si no existe
#
# En Render (y en producción en general) las variables deben venir del Environment del servicio.
# Si copiamos `.env.example` a `.env`, suele quedar `DB_HOST` vacío y Laravel cae a 127.0.0.1:3306,
# ignorando lo que creés haber configurado en el dashboard.
if [ ! -f .env ]; then
    if [ -n "${RENDER:-}" ] || [ "${APP_ENV:-}" = "production" ]; then
        echo ">>> Render/producción: no se creará .env desde .env.example (usar env vars del servicio)."
    else
        cp .env.example .env
        echo ">>> .env creado desde .env.example"
    fi
fi

# Generar APP_KEY si no existe
if [ -f .env ]; then
    if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
        php artisan key:generate --force
        echo ">>> APP_KEY generada"
    fi
else
    if [ -z "${APP_KEY:-}" ]; then
        echo ">>> ADVERTENCIA: falta APP_KEY en el entorno y no existe .env; define APP_KEY en Render (o crea .env con APP_KEY)."
    fi
fi

# Laravel cache
php artisan config:clear
php artisan view:clear

# Storage link
php artisan storage:link --force 2>/dev/null || true

# Permisos
chown -R www-data:www-data storage bootstrap/cache
mkdir -p public/images
chown -R www-data:www-data public/images

echo ">>> Iniciando Apache..."
exec apache2-foreground