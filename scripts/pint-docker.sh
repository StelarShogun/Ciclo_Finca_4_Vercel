#!/usr/bin/env bash
# Run Laravel Pint via Docker (PHP 8.5) when PHP is not installed on the host.
# Usage:
#   ./scripts/pint-docker.sh           # fix style (same as ./vendor/bin/pint)
#   ./scripts/pint-docker.sh --test    # check only (same as composer run lint)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found. Install Docker Engine first." >&2
  exit 1
fi
exec docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$ROOT":/app \
  -w /app \
  php:8.5-cli \
  ./vendor/bin/pint "$@"
