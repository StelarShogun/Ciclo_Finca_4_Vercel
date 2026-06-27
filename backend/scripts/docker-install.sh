#!/usr/bin/env bash
# Install PHP and Node dependencies and build Vite assets inside app_ciclo.
# Uses root in the container so composer/npm work on bind mounts, then chowns
# vendor, node_modules, and public/build to your host user.
#
# For assets only after deps are installed, use ./scripts/docker-vite-build.sh
#
# Usage:
#   ./scripts/docker-install.sh
#
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found. Install Docker first." >&2
  exit 1
fi

UID_HOST="$(id -u)"
GID_HOST="$(id -g)"

docker compose up -d app_ciclo db_ciclo

echo ">>> composer install + npm ci + npm run build (container)…"
docker compose exec -u root -T app_ciclo bash -lc "
  set -e
  cd /var/www/html
  composer install --no-interaction
  npm ci
  npm run build
  chown -R ${UID_HOST}:${GID_HOST} vendor node_modules public/build
"

echo ">>> Done."
