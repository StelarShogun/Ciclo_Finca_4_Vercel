#!/usr/bin/env bash
# CF4-163 — Insert scheduler monitoring rows into app_settings (production-safe).
#
# Inserts keys only if missing; never overwrites values from a running scheduler.
#
# Local Docker:
#   ./scripts/seed-scheduler-app-settings.sh
#   ./scripts/seed-scheduler-app-settings.sh --dry-run
#
# Production (Render shell / one-off with production env):
#   php artisan cf4:seed-scheduler-app-settings
#
# DBeaver / Aiven (no Artisan):
#   Run database/scripts/seed_scheduler_app_settings.sql
#
set -euo pipefail

cd "$(dirname "$0")/.."

ARGS=()
if [[ "${1:-}" == "--dry-run" ]]; then
  ARGS+=(--dry-run)
fi

if docker compose ps app_ciclo 2>/dev/null | grep -q 'Up'; then
  exec docker compose exec app_ciclo bash -lc "cd /var/www/html && php artisan cf4:seed-scheduler-app-settings ${ARGS[*]:-}"
fi

exec php artisan cf4:seed-scheduler-app-settings "${ARGS[@]}"
