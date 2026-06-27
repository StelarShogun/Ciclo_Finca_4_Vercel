#!/usr/bin/env bash
# Run the same checks as GitHub Actions CI — Dev (PHP + frontend).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! docker compose ps --status running --services 2>/dev/null | grep -q app_ciclo; then
  echo ">>> Starting Docker..."
  docker compose up -d
  sleep 12
fi

echo ">>> PHP: Pint (code style)"
docker compose exec app_ciclo composer run lint

echo ">>> PHP: PHPUnit (MySQL — full schema)"
./scripts/test-mysql-docker.sh

echo ">>> PHP: PHPStan"
docker compose exec app_ciclo composer run phpstan

echo ">>> Frontend: npm ci && npm run build"
docker compose exec app_ciclo npm ci
docker compose exec app_ciclo npm run build

echo ">>> CI parity checks passed."
