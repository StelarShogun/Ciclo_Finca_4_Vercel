#!/usr/bin/env bash
# Run Unlighthouse with optional .env.unlighthouse.local (not committed).
# Usage: npm run unlighthouse:guest|client|admin

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

PROFILE="${1:-guest}"

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

case "$PROFILE" in
  guest)
    CONFIG="unlighthouse.config.ts"
    ;;
  client)
    CONFIG="unlighthouse.client.config.ts"
    if [[ -z "${UNLIGHTHOUSE_CLIENT_COOKIE:-}" && ( -z "${UNLIGHTHOUSE_CLIENT_EMAIL:-}" || -z "${UNLIGHTHOUSE_CLIENT_PASSWORD:-}" ) ]]; then
      echo "[unlighthouse:client] Set UNLIGHTHOUSE_CLIENT_COOKIE or UNLIGHTHOUSE_CLIENT_EMAIL + UNLIGHTHOUSE_CLIENT_PASSWORD." >&2
      echo "  Cookie: docker exec laravel_app_ciclo php artisan unlighthouse:client-cookie" >&2
      exit 1
    fi
    ;;
  admin)
    CONFIG="unlighthouse.admin.config.ts"
    if [[ -z "${UNLIGHTHOUSE_ADMIN_COOKIE:-}" ]]; then
      echo "[unlighthouse:admin] UNLIGHTHOUSE_ADMIN_COOKIE is not set." >&2
      echo "  1. Log in at http://localhost:8080/admin/login and copy the session cookie from DevTools, or" >&2
      echo "  2. Run: docker exec laravel_app_ciclo php artisan unlighthouse:admin-cookie" >&2
      echo "     Then add the line to .env.unlighthouse.local and re-run npm run unlighthouse:admin" >&2
      exit 1
    fi
    ;;
  *)
    echo "Unknown profile: $PROFILE (guest|client|admin)" >&2
    exit 1
    ;;
esac

EXTRA_ARGS=()
if [[ "${UNLIGHTHOUSE_NO_CACHE:-}" == "1" ]]; then
  EXTRA_ARGS+=(--no-cache)
fi

exec ./node_modules/.bin/unlighthouse --config-file "$CONFIG" "${EXTRA_ARGS[@]}"
