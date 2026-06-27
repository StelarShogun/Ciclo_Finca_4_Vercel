#!/usr/bin/env bash
#
# CF4-163 — Alternativa de arranque SIN Docker (referencia / desarrollo local).
#
# NO usar como Start Command en Render si el servicio ya despliega con Dockerfile
# y docker-entrypoint.sh (producción actual: Apache + loop del scheduler en entrypoint).
#
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan config:clear
php artisan view:clear

(
  while true; do
    php artisan schedule:run --no-interaction >> storage/logs/scheduler.log 2>&1
    sleep 60
  done
) &

echo ">>> Scheduler loop iniciado (schedule:run cada 60s)"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
