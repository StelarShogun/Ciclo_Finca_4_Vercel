#!/usr/bin/env bash
# Pobla categorías, inventario, pedidos a proveedor (si la tabla está vacía),
# ventas demo y líneas de venta (sale_items) enlazadas a productos.
#
# IMPORTANTE — MySQL y PHP del proyecto viven en Docker. No uses `php artisan` en el host
# si tu PHP local es < 8.5 (composer bloqueará). Ejecutá artisan dentro de app_ciclo:
#
#   docker compose exec app_ciclo bash -lc 'cd /var/www/html && php artisan db:seed --class=Database\\Seeders\\FullDemoDatasetSeeder'
#
# O este script montado en el contenedor:
#   docker compose exec app_ciclo bash -lc '/var/www/html/scripts/seed-full-demo.sh'
#
# BD limpia (recomendado la primera vez o para evitar proveedores duplicados):
#   docker compose exec app_ciclo bash -lc 'cd /var/www/html && php artisan migrate:fresh --seeder=FullDemoDatasetSeeder'
#
# Vite (puerto 5173): si "Port already in use", ya hay un `npm run dev` en marcha o el
# contenedor mapea 5173; no levantes otro, o pará el proceso: `docker compose stop app_ciclo`
# y volvé a subir solo si necesitás reiniciar.

set -euo pipefail
cd "$(dirname "$0")/.."

php artisan db:seed --class="Database\\Seeders\\FullDemoDatasetSeeder"
