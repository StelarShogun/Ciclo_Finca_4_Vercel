#!/usr/bin/env bash
# Run homepage load test: native k6 if available, else Docker (grafana/k6).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
IMAGE="${K6_DOCKER_IMAGE:-grafana/k6:latest}"

if command -v k6 >/dev/null 2>&1; then
  exec k6 run scripts/k6/homepage.js "$@"
fi

if command -v docker >/dev/null 2>&1; then
  case "$(uname -s)" in
    Linux)
      exec docker run --rm --network host \
        -e BASE_URL="${BASE_URL:-}" \
        -v "$ROOT:/work" -w /work "$IMAGE" \
        run scripts/k6/homepage.js "$@"
      ;;
    *)
      # macOS / Windows Docker: host is not container localhost
      exec docker run --rm --add-host=host.docker.internal:host-gateway \
        -e BASE_URL="${BASE_URL:-http://host.docker.internal:8080}" \
        -v "$ROOT:/work" -w /work "$IMAGE" \
        run scripts/k6/homepage.js "$@"
      ;;
  esac
fi

echo "k6 no está en el PATH. Opciones:" >&2
echo "  • Arch: sudo pacman -S k6" >&2
echo "  • Otras: https://grafana.com/docs/k6/latest/set-up/install-k6/" >&2
echo "  • O instala Docker y vuelve a ejecutar npm run k6:home" >&2
exit 1
