#!/usr/bin/env bash
# Run the full PHPUnit suite against MySQL (Docker Compose).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found." >&2
  exit 1
fi

if ! docker compose ps --status running --services 2>/dev/null | grep -q db_ciclo; then
  echo ">>> Starting Docker (app + MySQL)..."
  docker compose up -d
  echo ">>> Waiting for MySQL..."
  sleep 15
fi

if [ ! -f .env ]; then
  echo "Missing .env — copy .env.example and set DB_DATABASE, DB_USERNAME, DB_PASSWORD." >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a
source .env
set +a

DB_HOST="${DB_HOST:-db_ciclo}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-laravel}"
DB_PASSWORD="${DB_PASSWORD:-password}"
DB_DATABASE="${DB_DATABASE:-laravel}"
DB_TEST_DATABASE="${DB_TEST_DATABASE:-${DB_DATABASE}_test}"

echo ">>> Ensuring test database exists: ${DB_TEST_DATABASE}"
docker compose exec -T db_ciclo mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS \`${DB_TEST_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON \`${DB_TEST_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
   FLUSH PRIVILEGES;" 2>/dev/null || \
docker compose exec -T db_ciclo mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS \`${DB_TEST_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo ">>> Running PHPUnit (MySQL) on ${DB_TEST_DATABASE}..."
docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=db_ciclo \
  -e DB_PORT=3306 \
  -e DB_DATABASE="${DB_TEST_DATABASE}" \
  -e DB_USERNAME="${DB_USERNAME}" \
  -e DB_PASSWORD="${DB_PASSWORD}" \
  app_ciclo ./vendor/bin/phpunit -c phpunit.mysql.xml "$@"
