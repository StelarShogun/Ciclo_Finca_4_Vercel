#!/usr/bin/env bash
# Seguimiento 8 — ejecuta las 12 pruebas del equipo (3 por integrante), Newman y Dusk.
# Genera logs en docs/evidencia/YYYY-MM-DD/<integrante>/ para el documento de evidencias.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DATE_STAMP="$(date +%Y-%m-%d)"
EVIDENCE_ROOT="${ROOT}/docs/evidencia/${DATE_STAMP}"

MEMBERS=(aaron arturo darwin dilan)

run_logged() {
  local log_file="$1"
  shift
  mkdir -p "$(dirname "$log_file")"
  echo ">>> $*" | tee -a "$log_file"
  if "$@" >>"$log_file" 2>&1; then
    echo ">>> PASSED — log: $log_file"
    return 0
  fi
  echo ">>> FAILED — log: $log_file" >&2
  return 1
}

if ! docker compose ps --status running --services 2>/dev/null | grep -q app_ciclo; then
  echo ">>> Starting Docker (app, db, selenium)..."
  docker compose up -d app_ciclo db_ciclo selenium_ciclo
  sleep 12
fi

for member in "${MEMBERS[@]}"; do
  mkdir -p "${EVIDENCE_ROOT}/${member}"
  touch "${EVIDENCE_ROOT}/${member}/.gitkeep"
done

mkdir -p "${EVIDENCE_ROOT}/pipeline"
FAILED=0

echo ">>> Seguimiento 8 evidence run — ${DATE_STAMP}"
echo ">>> Output: ${EVIDENCE_ROOT}"

# --- Aaron: PHPUnit API (2) + grupo completo Newman en pipeline ---
if ! run_logged "${EVIDENCE_ROOT}/aaron/01-phpunit-api.log" \
  ./scripts/test-mysql-docker.sh --group seguimiento8-aaron; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/aaron/02-newman-api.log" \
  npm run test:api; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/aaron/03-dusk-admin-login.log" \
  ./scripts/run-dusk-docker.sh --filter=AdminLoginTest; then
  FAILED=1
fi

# --- Arturo: PHPUnit (2) + Dusk cliente ---
if ! run_logged "${EVIDENCE_ROOT}/arturo/01-phpunit-catalog-heartbeat.log" \
  ./scripts/test-mysql-docker.sh --filter test_catalog_heartbeat_returns_version_key; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/arturo/02-phpunit-home-guest-cta.log" \
  ./scripts/test-mysql-docker.sh --filter test_guest_sees_create_account_in_final_cta; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/arturo/03-dusk-client-login.log" \
  ./scripts/run-dusk-docker.sh --filter=ClientLoginTest; then
  FAILED=1
fi

# --- Darwin: PHPUnit trending + 2 Dusk ---
if ! run_logged "${EVIDENCE_ROOT}/darwin/01-phpunit-search-trending.log" \
  ./scripts/test-mysql-docker.sh --filter test_search_trending_returns_expected_json_shape; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/darwin/02-dusk-catalog.log" \
  ./scripts/run-dusk-docker.sh --filter=ClientCatalogTest; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/darwin/03-dusk-register.log" \
  ./scripts/run-dusk-docker.sh --filter=ClientRegisterBrowserTest; then
  FAILED=1
fi

# --- Dilan: PHPUnit legal + Pulse + Dusk legal ---
if ! run_logged "${EVIDENCE_ROOT}/dilan/01-phpunit-legal-pages.log" \
  ./scripts/test-mysql-docker.sh --filter test_legal_pages_are_accessible; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/dilan/02-phpunit-pulse-monitoring.log" \
  ./scripts/test-mysql-docker.sh --filter test_pulse_monitoring_is_available_for_admin; then
  FAILED=1
fi

if ! run_logged "${EVIDENCE_ROOT}/dilan/03-dusk-legal-terms.log" \
  ./scripts/run-dusk-docker.sh --filter=ClientLegalTermsTest; then
  FAILED=1
fi

# --- Pipeline: paridad CI opcional + health producción ---
if [ "${FULL:-0}" = "1" ]; then
  if ! run_logged "${EVIDENCE_ROOT}/pipeline/01-ci-parity.log" \
    ./scripts/ci-check-docker.sh; then
    FAILED=1
  fi
else
  echo ">>> Skipping ci-check-docker (set FULL=1 to include CI parity)." \
    >"${EVIDENCE_ROOT}/pipeline/01-ci-parity.log"
fi

PROD_URL="${PRODUCTION_URL:-https://ciclo-finca-4-app-g0j4.onrender.com}"
if curl -fsS "${PROD_URL}/up" -o "${EVIDENCE_ROOT}/pipeline/02-production-up.body" \
  -w "http_code=%{http_code}\n" >"${EVIDENCE_ROOT}/pipeline/02-production-up.log" 2>&1; then
  echo ">>> Production /up OK — ${PROD_URL}"
else
  echo ">>> Production /up check failed (optional if Render is down)" | tee -a "${EVIDENCE_ROOT}/pipeline/02-production-up.log"
fi

cat >"${EVIDENCE_ROOT}/RESUMEN.txt" <<EOF
Seguimiento 8 — ejecución automática
Fecha: ${DATE_STAMP}
Estado: $(if [ "$FAILED" -eq 0 ]; then echo "PASSED"; else echo "FAILED"; fi)

Integrantes y pruebas (3 c/u, sin repetir):
- aaron:   PHPUnit health+suggestions, Newman Postman, Dusk AdminLogin
- arturo:  PHPUnit heartbeat+home CTA, Dusk ClientLogin
- darwin:  PHPUnit search-trending, Dusk Catalog+Register
- dilan:   PHPUnit legal+Pulse, Dusk LegalTerms

Logs: docs/evidencia/${DATE_STAMP}/<integrante>/
Pipeline CI local: docs/evidencia/${DATE_STAMP}/pipeline/

Comando único: ./scripts/run-seguimiento-8-evidence.sh
Grupo PHPUnit:  --group seguimiento8
EOF

if [ "$FAILED" -ne 0 ]; then
  echo ">>> Seguimiento 8 evidence run FAILED. See ${EVIDENCE_ROOT}" >&2
  exit 1
fi

echo ">>> Seguimiento 8 evidence run PASSED."
echo ">>> Resumen: ${EVIDENCE_ROOT}/RESUMEN.txt"
