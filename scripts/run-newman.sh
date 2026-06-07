#!/usr/bin/env bash
# Run the Postman collection via Newman (CLI). Requires: npm ci && app on APP_URL.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${BASE_URL:-http://localhost:8080}"

cd "$ROOT"

if ! command -v npx >/dev/null 2>&1; then
  echo "npx not found. Run: npm ci" >&2
  exit 1
fi

npx newman run postman/CF4-Storefront-API.postman_collection.json \
  --env-var "baseUrl=${BASE_URL}" \
  "$@"
