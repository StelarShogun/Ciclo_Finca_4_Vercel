#!/usr/bin/env bash
# Run admin + client Unlighthouse scans in parallel (local).
# Usage: npm run unlighthouse:auth

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if [[ -f .env.unlighthouse.local ]]; then
  set -a
  # shellcheck source=/dev/null
  source .env.unlighthouse.local
  set +a
fi

if [[ -z "${UNLIGHTHOUSE_CHROME_PATH:-}" ]]; then
  CANDIDATE="$(find "${HOME}/.cache/puppeteer" -name chrome -type f 2>/dev/null | head -1 || true)"
  if [[ -n "$CANDIDATE" && -x "$CANDIDATE" ]]; then
    export UNLIGHTHOUSE_CHROME_PATH="$CANDIDATE"
  fi
fi

if [[ -z "${UNLIGHTHOUSE_ADMIN_COOKIE:-}" ]]; then
  echo "[unlighthouse:auth] Generating admin session cookie via Artisan..."
  UNLIGHTHOUSE_ADMIN_COOKIE="$(docker exec laravel_app_ciclo php artisan unlighthouse:admin-cookie 2>/dev/null | head -1 || true)"
  export UNLIGHTHOUSE_ADMIN_COOKIE
fi

if [[ -z "${UNLIGHTHOUSE_CLIENT_COOKIE:-}" ]]; then
  echo "[unlighthouse:auth] Generating client session cookie via Artisan..."
  UNLIGHTHOUSE_CLIENT_COOKIE="$(docker exec laravel_app_ciclo php artisan unlighthouse:client-cookie 2>/dev/null | head -1 || true)"
  export UNLIGHTHOUSE_CLIENT_COOKIE
fi

if [[ -z "${UNLIGHTHOUSE_ADMIN_COOKIE:-}" || -z "${UNLIGHTHOUSE_CLIENT_COOKIE:-}" ]]; then
  echo "[unlighthouse:auth] Missing cookies. Ensure Docker app is up and APP_ENV=local." >&2
  exit 1
fi

export UNLIGHTHOUSE_NO_CACHE="${UNLIGHTHOUSE_NO_CACHE:-1}"

echo "[unlighthouse:auth] Admin → ./lighthouse-admin/ then Client → ./lighthouse-client/"
echo "[unlighthouse:auth] (sequential — avoids Puppeteer cookie race when both run at once)"

bash scripts/unlighthouse/run.sh admin
bash scripts/unlighthouse/run.sh client
