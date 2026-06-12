#!/usr/bin/env bash
# Render Pre-Deploy Command: reconcile squashed migrations, then migrate.
# Dashboard → Settings → Pre-Deploy Command:
#   bash scripts/render-pre-deploy.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${RENDER_DIR:-$ROOT}"

echo ">>> CF4 pre-deploy: reconcile squashed migrations"
php artisan cf4:reconcile-squashed-migrations --force --no-interaction

echo ">>> CF4 pre-deploy: migrate"
php artisan migrate --force --no-interaction

echo ">>> CF4 pre-deploy: done"
