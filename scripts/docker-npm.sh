#!/usr/bin/env bash
# Run arbitrary npm commands inside app_ciclo (as root so installs work on bind mounts).
# Afterward, resets ownership of node_modules and public/build to your host user.
#
# Usage:
#   ./scripts/docker-npm.sh ci
#   ./scripts/docker-npm.sh run build
#   ./scripts/docker-npm.sh run dev
#   ./scripts/docker-vite-build.sh   # shortcut → npm run build
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

# Args after _ become $1, $2, … inside -c (reliable npm "$@" forwarding).
docker compose exec -u root -T app_ciclo \
  bash -lc 'set -e
    cd /var/www/html
    npm "$@"
    chown -R '"$UID_HOST"':'"$GID_HOST"' node_modules 2>/dev/null || true
    chown -R '"$UID_HOST"':'"$GID_HOST"' public/build 2>/dev/null || true
  ' _ "$@"
