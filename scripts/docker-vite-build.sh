#!/usr/bin/env bash
# Production Vite build inside Docker (same Node as CI / equipos sin npm local).
# Same as: ./scripts/docker-npm.sh run build
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
exec "$SCRIPT_DIR/docker-npm.sh" run build
