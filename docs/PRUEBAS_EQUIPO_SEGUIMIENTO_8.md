# Pruebas del equipo — Seguimiento 8 (rúbrica EIF-406)

Asignación de **3 pruebas automatizadas por integrante** (12 en total, sin repetir). Cumple la rúbrica: ejecución **sin intervención manual** e integración al **pipeline CI** (GitHub Actions).

Complementa [SEGUIMIENTO_8.md](./SEGUIMIENTO_8.md) · Evidencias en [evidencia/README.md](./evidencia/README.md).

**Integrantes:** Aaron · Arturo · Darwin · Dilan

---

## Ejecutar todo automáticamente

```bash
docker compose up -d app_ciclo db_ciclo selenium_ciclo
./scripts/run-seguimiento-8-evidence.sh
```

Genera logs en `docs/evidencia/YYYY-MM-DD/<integrante>/`. Con paridad CI: `FULL=1 ./scripts/run-seguimiento-8-evidence.sh`.

**Solo PHPUnit del seguimiento (7 tests Feature):**

```bash
docker compose exec app_ciclo composer run test:seguimiento8
```

**Grupo por integrante:**

```bash
./scripts/test-mysql-docker.sh --group seguimiento8-aaron
./scripts/run-dusk-docker.sh --filter=AdminLoginTest
```

---

## Matriz resumen

| Integrante | Prueba 1 | Prueba 2 | Prueba 3 | Área DevOps |
|------------|----------|----------|----------|-------------|
| **Aaron** | Health API `/up` (PHPUnit) | Suggestions API (PHPUnit) | Admin login → dashboard (Dusk) | API + Postman/Newman + UI |
| **Arturo** | Catalog heartbeat API (PHPUnit) | Home guest CTA (PHPUnit) | Client login → catálogo (Dusk) | API + storefront + UI |
| **Darwin** | Search trending API (PHPUnit) | Catálogo hero (Dusk) | Registro + enlace login (Dusk) | API + UI |
| **Dilan** | Páginas legales (PHPUnit) | Pulse monitoreo (PHPUnit) | Términos legales (Dusk) | Legal + Grafana/Pulse + UI |

**Pipeline CI (equipo):** GitHub Actions ejecuta Pint, PHPUnit completo, PHPStan, build, **Newman** y **Dusk** (`--group=seguimiento8`). Deploy a Render solo si todo pasa.

---

## Aaron

### Prueba 1 — Health API (`GET /up`)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/Api/StorefrontApiTest.php` |
| Método | `test_health_endpoint_returns_ok` |
| Grupo | `seguimiento8-aaron` |
| CI | Job `PHP — lint, tests, static analysis` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_health_endpoint_returns_ok
```

**Cómo explicarla:**

> «Verifico automáticamente que el endpoint de salud responde 200. Es la misma ruta que usa Render tras el deploy. Si falla, el pipeline bloquea el avance a producción.»

---

### Prueba 2 — Suggestions API (`GET /api/products/suggestions`)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/Api/StorefrontApiTest.php` |
| Método | `test_product_suggestions_returns_empty_for_short_search` |
| Grupo | `seguimiento8-aaron` |
| CI | Job `PHP` + job `API — Newman` (misma regla vía Postman) |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_product_suggestions_returns_empty_for_short_search
npm run test:api   # Newman — colección Postman en CLI
```

**Cómo explicarla:**

> «Valido la API de autocompletado del buscador: con búsqueda corta debe devolver un arreglo vacío. Lo cubro en PHPUnit (CI) y en Newman (herramienta Postman automatizada en el pipeline).»

---

### Prueba 3 — Admin login UI (Selenium / Dusk)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Browser/AdminLoginTest.php` |
| Método | `test_admin_can_log_in_and_reach_dashboard` |
| Grupo | `seguimiento8-aaron` |
| CI | Job `UI — Laravel Dusk` |

**Comando:**

```bash
./scripts/run-dusk-docker.sh --filter=AdminLoginTest
```

**Cómo explicarla:**

> «Selenium abre un navegador real, completa el formulario admin y confirma la redirección a `/dashboard`. Es un flujo funcional completo sin clicks manuales.»

---

## Arturo

### Prueba 1 — Catalog heartbeat API

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/Api/StorefrontApiTest.php` |
| Método | `test_catalog_heartbeat_returns_version_key` |
| Grupo | `seguimiento8-arturo` |
| CI | Job `PHP` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_catalog_heartbeat_returns_version_key
```

**Cómo explicarla:**

> «El heartbeat del catálogo devuelve una versión en JSON. El frontend la usa para detectar cambios; el test asegura que el contrato no se rompe.»

---

### Prueba 2 — Home invitado (CTA registro)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/CF4ClientHomeGuestCtaTest.php` |
| Método | `test_guest_sees_create_account_in_final_cta` |
| Grupo | `seguimiento8-arturo` |
| CI | Job `PHP` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_guest_sees_create_account_in_final_cta
```

**Cómo explicarla:**

> «Compruebo que un visitante sin sesión ve el CTA de crear cuenta en el home Inertia. Valida la capa de presentación y props del servidor sin abrir navegador.»

---

### Prueba 3 — Client login UI (Selenium / Dusk)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Browser/ClientLoginTest.php` |
| Método | `test_client_can_log_in_from_storefront` |
| Grupo | `seguimiento8-arturo` |
| CI | Job `UI — Laravel Dusk` |

**Comando:**

```bash
./scripts/run-dusk-docker.sh --filter=ClientLoginTest
```

**Cómo explicarla:**

> «Automatizo el login del cliente en el storefront y verifico que llega al catálogo. Cubre el flujo Inertia con redirección real en navegador.»

**Responsabilidad CI/CD (video grupal):** Arturo demuestra commit → Actions verde/rojo → deploy Render → cambio en producción.

---

## Darwin

### Prueba 1 — Search trending API

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/Api/StorefrontApiTest.php` |
| Método | `test_search_trending_returns_expected_json_shape` |
| Grupo | `seguimiento8-darwin` |
| CI | Job `PHP` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_search_trending_returns_expected_json_shape
```

**Cómo explicarla:**

> «Valido la forma del JSON de tendencias de búsqueda: periodo, productos y términos. Es funcionalidad real del catálogo usada en la UI.»

---

### Prueba 2 — Catálogo público (Dusk)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Browser/ClientCatalogTest.php` |
| Método | `test_guest_can_open_catalog_and_see_hero` |
| Grupo | `seguimiento8-darwin` |
| CI | Job `UI — Laravel Dusk` |

**Comando:**

```bash
./scripts/run-dusk-docker.sh --filter=ClientCatalogTest
```

**Cómo explicarla:**

> «Un invitado abre `/catalog` y el test confirma el shell del catálogo y el hero. Interacción UI automatizada con Selenium.»

---

### Prueba 3 — Registro y enlace a login (Dusk)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Browser/ClientRegisterBrowserTest.php` |
| Método | `test_register_page_shows_signup_form` |
| Grupo | `seguimiento8-darwin` |
| CI | Job `UI — Laravel Dusk` |

**Comando:**

```bash
./scripts/run-dusk-docker.sh --filter=ClientRegisterBrowserTest
```

**Cómo explicarla:**

> «Verifico el formulario de registro y la navegación al login. Es un mini-flujo completo: pantalla A → clic → pantalla B con contenido esperado.»

---

## Dilan

### Prueba 1 — Páginas legales (PHPUnit)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/CF4ClientLegalPagesTest.php` |
| Método | `test_legal_pages_are_accessible` |
| Grupo | `seguimiento8-dilan` |
| CI | Job `PHP` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_legal_pages_are_accessible
```

**Cómo explicarla:**

> «Compruebo que términos, privacidad, devoluciones y contacto responden 200 con el componente Inertia correcto.»

---

### Prueba 2 — Monitoreo Pulse (PHPUnit)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Feature/Monitoring/PulseDashboardTest.php` |
| Método | `test_pulse_monitoring_is_available_for_admin` |
| Grupo | `seguimiento8-dilan` |
| CI | Job `PHP` |

**Comando:**

```bash
./scripts/test-mysql-docker.sh --filter test_pulse_monitoring_is_available_for_admin
```

**Qué valida:** `pulse:check --once` termina OK; invitado recibe 403 en `/pulse`; admin accede al dashboard.

**Cómo explicarla:**

> «Automatizo el monitoreo con Laravel Pulse: el comando de captura de métricas funciona y solo el admin puede ver `/pulse`. Complemento con captura del dashboard en el documento de evidencias.»

---

### Prueba 3 — Términos legales UI (Dusk)

| Campo | Valor |
|-------|--------|
| Archivo | `tests/Browser/ClientLegalTermsTest.php` |
| Método | `test_guest_can_read_terms_and_conditions_page` |
| Grupo | `seguimiento8-dilan` |
| CI | Job `UI — Laravel Dusk` |

**Comando:**

```bash
./scripts/run-dusk-docker.sh --filter=ClientLegalTermsTest
```

**Ver el navegador en vivo (video):** abre http://localhost:7900 (noVNC) y corre:

```bash
DUSK_VISIBLE=1 ./scripts/run-dusk-docker.sh --filter=ClientLegalTermsTest
```

**Antes de Dusk:** detén `npm run dev` y borra `public/hot` (`rm -f public/hot`), si no la página queda en blanco.

**Complemento visual Pulse (evidencias):**

```bash
docker compose exec app_ciclo php artisan pulse:check --once
# Luego en navegador (admin): http://localhost:8080/pulse
```

**Cómo explicarla:**

> «Selenium abre `/legal/terminos` y valida título y contenido visible. Cierra el requisito de UI automatizada con funcionalidad legal del proyecto.»

---

## Notas Dusk (todos los integrantes con prueba UI)

| Situación | Solución |
|-----------|----------|
| `The "--filter" option does not exist` | Usar `./scripts/run-dusk-docker.sh` (no `composer run dusk --filter`) |
| `TTY mode requires /dev/tty` | El script ya usa `--without-tty`; es solo un aviso si aparece |
| Pantalla en blanco / timeout | Detener `npm run dev` + `rm -f public/hot` |
| Ver Chrome al grabar | `DUSK_VISIBLE=1` + http://localhost:7900 |

---

## Checklist rúbrica

| Requisito rúbrica | Cómo lo cumple el equipo |
|-------------------|--------------------------|
| 3 pruebas por integrante, sin repetir | Matriz anterior (12 casos únicos) |
| Ejecución automática | `./scripts/run-seguimiento-8-evidence.sh` |
| Integradas al CI | `.github/workflows/ci-cd-dev.yml` |
| UI con Selenium | 5 tests Dusk con `--group=seguimiento8` |
| API con Postman | Job Newman + colección `postman/` |
| Monitoreo | Pulse (`PulseDashboardTest` + dashboard en evidencias) |
| CI verde → deploy / CI rojo → sin deploy | Video grupal + Render con Auto-Deploy Off |
| Evidencias documentadas | `docs/evidencia/YYYY-MM-DD/` + capturas Actions/Render |

---

## Orden sugerido para demo en clase

1. `./scripts/run-seguimiento-8-evidence.sh` (logs automáticos).
2. Mostrar GitHub Actions: jobs PHP, Newman, Dusk, Deploy.
3. Escenario CI rojo (fallo intencional) → sin deploy.
4. Dilan: captura de `/pulse` en navegador (complemento visual del test automatizado).

---

*Rama: `feature/ci-cd-follow-up` → `Dev` · Workflow: `.github/workflows/ci-cd-dev.yml`*
