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

# Aiven MySQL typically requires SSL. Render "Secret Files" are mounted under /etc/secrets/<filename>,
# but teams often set MYSQL_ATTR_SSL_CA=/etc/secrets/ca.pem even when the file is not present at that path.
# Copy a usable CA bundle into storage (writable) and default MYSQL_ATTR_SSL_CA if unset.
if [ -n "${RENDER:-}" ]; then
    mkdir -p storage/certs
    if [ -f /etc/secrets/ca.pem ]; then
        cp -f /etc/secrets/ca.pem storage/certs/ca.pem
        chmod 644 storage/certs/ca.pem || true
    elif [ -f ca.pem ]; then
        # Some setups place the secret file in the app root instead of /etc/secrets
        cp -f ca.pem storage/certs/ca.pem
        chmod 644 storage/certs/ca.pem || true
    fi

    if [ -f storage/certs/ca.pem ]; then
        resolved_ca="/var/www/html/storage/certs/ca.pem"
        if [ -z "${MYSQL_ATTR_SSL_CA:-}" ]; then
            export MYSQL_ATTR_SSL_CA="${resolved_ca}"
            echo ">>> Render: MYSQL_ATTR_SSL_CA no estaba definido; usando ${MYSQL_ATTR_SSL_CA}"
        elif [ ! -f "${MYSQL_ATTR_SSL_CA}" ]; then
            echo ">>> Render: MYSQL_ATTR_SSL_CA apunta a un archivo inexistente (${MYSQL_ATTR_SSL_CA}); usando ${resolved_ca}"
            export MYSQL_ATTR_SSL_CA="${resolved_ca}"
        fi
    fi
fi

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

# Laravel cache / production bootstrap
if [ -n "${RENDER:-}" ]; then
    # Single-instance Render web: file cache avoids ~130ms round-trips to remote MySQL per key.
    export CACHE_STORE="${CACHE_STORE:-file}"
    export SESSION_DRIVER="${SESSION_DRIVER:-file}"

    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo ">>> Render: Laravel caches warmed (config, routes, views, events)"
else
    php artisan config:clear
    php artisan view:clear
fi

# Storage link
php artisan storage:link --force 2>/dev/null || true

# Permisos
chown -R www-data:www-data storage bootstrap/cache
mkdir -p public/images
chown -R www-data:www-data public/images

# Prepara los logs de los procesos supervisados (supervisord los escribe como root,
# pero los dejamos creados y con dueño correcto para que Laravel también pueda usarlos).
touch storage/logs/scheduler.log storage/logs/worker.log storage/logs/supervisord.log
chown www-data:www-data storage/logs/scheduler.log storage/logs/worker.log

# Apache, worker de cola y scheduler quedan bajo supervisord (PID 1), que reinicia
# automáticamente cualquier proceso que muera. Antes corrían como loops `while true`
# sueltos: si el worker se caía, nada lo reiniciaba y las importaciones se quedaban
# "en cola" indefinidamente mientras Apache seguía sano.
echo ">>> Iniciando supervisord (apache + queue:work + scheduler)…"
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf -n