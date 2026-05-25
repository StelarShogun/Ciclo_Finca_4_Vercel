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
    # Render exposes RENDER_EXTERNAL_URL automatically for web services.
    # Use it as a reliable APP_URL fallback so email templates never show http://localhost.
    if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
        if [ -z "${APP_URL:-}" ] || [ "${APP_URL:-}" = "http://localhost" ]; then
            export APP_URL="${RENDER_EXTERNAL_URL}"
            echo ">>> Render: APP_URL no estaba definido o era localhost; usando RENDER_EXTERNAL_URL=${APP_URL}"
        fi
        # Keep FRONTEND_URL in sync so mail templates that read config('app.frontend_url') also get the correct URL.
        if [ -z "${FRONTEND_URL:-}" ] || [ "${FRONTEND_URL:-}" = "http://localhost" ]; then
            export FRONTEND_URL="${APP_URL}"
        fi
    else
        if [ -z "${APP_URL:-}" ] || [ "${APP_URL:-}" = "http://localhost" ]; then
            echo ">>> ADVERTENCIA: APP_URL no está definido en las variables de entorno de Render."
            echo ">>>   Los correos mostrarán http://localhost en los enlaces."
            echo ">>>   Agrega APP_URL=https://ciclo-finca-4-app-4ccw.onrender.com en el Dashboard de Render."
        fi
    fi

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

# Run pending migrations automatically on every deploy.
# This is a no-op when schema is already up to date and safely handles the
# jobs/failed_jobs tables required by QUEUE_CONNECTION=database.
if [ -n "${RENDER:-}" ] || [ "${APP_ENV:-}" = "production" ]; then
    echo ">>> Ejecutando migraciones pendientes…"
    php artisan migrate --force 2>&1 | tail -30 || echo ">>> ADVERTENCIA: migrate devolvió un error; revisa los logs."
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

touch storage/logs/scheduler.log
chown www-data:www-data storage/logs/scheduler.log
(
  su -s /bin/bash www-data -c '
    cd /var/www/html
    while true; do
      php artisan schedule:run --no-interaction >> storage/logs/scheduler.log 2>&1
      sleep 60
    done
  '
) &

echo ">>> Scheduler loop iniciado (schedule:run cada 60s)"

# Queue worker — processes DB-queued jobs (email notifications, etc.) in the background.
# This prevents SMTP calls from blocking HTTP responses (which caused ~15 s delays on Render).
# The worker restarts automatically on exit so transient failures don't leave the queue stalled.
touch storage/logs/queue-worker.log
chown www-data:www-data storage/logs/queue-worker.log
(
  su -s /bin/bash www-data -c '
    cd /var/www/html
    while true; do
      php artisan queue:work \
        --sleep=3 \
        --tries=3 \
        --max-time=3600 \
        --no-interaction \
        >> storage/logs/queue-worker.log 2>&1
      echo "[queue-worker] Reiniciando worker tras salida inesperada..." >> storage/logs/queue-worker.log
      sleep 5
    done
  '
) &

echo ">>> Queue worker iniciado en background (QUEUE_CONNECTION=${QUEUE_CONNECTION:-database})"
echo ">>> Iniciando Apache..."
exec apache2-foreground