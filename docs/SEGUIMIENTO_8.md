# Seguimiento 8 — CI/CD, pruebas API/UI y Pulse

Documento de referencia del equipo **Ciclo Finca 4** para el entregable de DevOps (Seguimiento 8). Resume qué se implementó, qué ya se probó en local, qué falta para cerrar la entrega y los comandos paso a paso.

**Producción:** [https://ciclo-finca-4-app-g0j4.onrender.com](https://ciclo-finca-4-app-g0j4.onrender.com)

**Rama de trabajo:** `feature/ci-cd-follow-up` → merge a `Dev`

**Documentación técnica CI/CD:** [CI.md](./CI.md)

---

## 1. Resumen ejecutivo

| Área | Herramienta | Estado implementación | Estado prueba local |
|------|-------------|----------------------|---------------------|
| CI/CD | GitHub Actions + Render | Workflow `ci-cd-dev.yml` en repo | Pendiente push/merge a `Dev` |
| API automatizada | PHPUnit | `StorefrontApiTest.php` (4 tests) | OK |
| API colección | Newman / Postman | `postman/CF4-Storefront-API.postman_collection.json` | OK |
| UI navegador | Laravel Dusk + Selenium | 5 tests en `tests/Browser/` | OK (`run-dusk-docker.sh`) |
| Monitoreo | Laravel Pulse | Migración `0016_pulse`, `/pulse` admin | OK (dashboard local) |
| Calidad (paridad CI) | Pint + PHPUnit + PHPStan + build | `ci-check-docker.sh` | OK |

---

## 2. Qué se implementó (detalle técnico)

### 2.1 CI/CD (GitHub Actions + Render)

- Workflow: [`.github/workflows/ci-cd-dev.yml`](../.github/workflows/ci-cd-dev.yml)
- Jobs:
  - **PHP** — Pint, PHPUnit con MySQL 8, PHPStan
  - **Frontend** — `npm ci` + `npm run build`
  - **CD — Deploy to Render** — solo en **push directo a `Dev`** si los jobs anteriores pasan (los PR ejecutan CI pero **no** despliegan)
- Deploy vía **Render Deploy Hook** (no auto-deploy de Render)

### 2.2 Pruebas de API — PHPUnit

- Archivo: [`tests/Feature/Api/StorefrontApiTest.php`](../tests/Feature/Api/StorefrontApiTest.php)
- Endpoints cubiertos:
  - `GET /up`
  - `GET /api/products/suggestions`
  - `GET /api/catalog/heartbeat`
  - `GET /api/catalog/search-trending`
- Corre en CI con MySQL (igual que el resto de la suite)

### 2.3 Pruebas de API — Newman (Postman CLI)

- Colección: [`postman/CF4-Storefront-API.postman_collection.json`](../postman/CF4-Storefront-API.postman_collection.json)
- Dependencia: `newman` en `package.json` + `package-lock.json` sincronizado
- Scripts npm:
  - `npm run test:api` — desde el **host** (`http://localhost:8080`)
  - `npm run test:api:docker` — **dentro** del contenedor (`http://127.0.0.1`)

### 2.4 Pruebas de UI — Laravel Dusk

- Tests principales (Seguimiento 8):
  - `tests/Browser/AdminLoginTest.php`
  - `tests/Browser/ClientLoginTest.php`
  - `tests/Browser/ClientCatalogTest.php`
  - `tests/Browser/ClientLegalTermsTest.php`
  - `tests/Browser/ClientRegisterBrowserTest.php` (registro + enlace a login)
- Config: [`phpunit.dusk.xml`](../phpunit.dusk.xml), [`tests/DuskTestCase.php`](../tests/DuskTestCase.php)
- Entorno Dusk: [`.env.dusk.example`](../.env.dusk.example) (plantilla; el script genera `.env.dusk.local`)
- **No corre en GitHub Actions** por defecto (requiere Chrome/Selenium + app levantada)

#### Infraestructura Docker para Dusk

- Servicio **`selenium_ciclo`** en [`docker-compose.yml`](../docker-compose.yml) (`selenium/standalone-chromium`)
- Script todo-en-uno: [`scripts/run-dusk-docker.sh`](../scripts/run-dusk-docker.sh)
  - Levanta Selenium si falta
  - Genera `.env.dusk.local` con red Docker (`APP_URL=http://app_ciclo`, `DB_HOST=db_ciclo`, `LARAVEL_SAIL=1`)
  - Crea BD `laravel_dusk`
  - Compila assets y ejecuta `composer run dusk`

#### Fixes de aplicación para que Dusk pase

- **Admin login:** reCAPTCHA solo si `RECAPTCHA_SITE_KEY` está configurado ([`AdminUserController`](../app/Http/Controllers/Admin/Auth/AdminUserController.php), vista admin login)
- **Cliente login (Inertia):** redirect correcto en peticiones con header `X-Inertia` ([`AttemptClientLogin`](../app/Actions/Client/Auth/AttemptClientLogin.php))
- **DuskTestCase:** Chromium en Docker, flags `--no-sandbox`, URL del driver sin `env()` (PHPStan)

### 2.5 Laravel Pulse

- Migración: `database/migrations/0016_pulse.php`
- Dashboard: `/pulse` (solo administradores autenticados)
- Scheduler: `pulse:check` cada 5 minutos en [`routes/console.php`](../routes/console.php)
- En PHPUnit: `PULSE_ENABLED=false` en `phpunit.xml` (no interfiere con tests)

### 2.6 Commit de referencia

```
feat(CF4-8): add Seguimiento 8 API, Dusk, and Newman test tooling
```

Rama: `feature/ci-cd-follow-up` (commit `6d14b9cb` o posterior).

---

## 3. Qué ya está probado en local

Checklist verificado por el equipo en Docker:

- [x] `./scripts/ci-check-docker.sh` → `CI parity checks passed`
  - Pint (406 archivos)
  - PHPUnit MySQL (434 tests, 2429 assertions)
  - PHPStan (sin errores)
  - `npm ci` + `npm run build`
- [x] `./scripts/test-mysql-docker.sh tests/Feature/Api/StorefrontApiTest.php` → 4 tests OK
- [x] `npm run test:api` → 4 requests, 7 assertions, 0 failed
- [x] `./scripts/run-dusk-docker.sh` → 5 passed, 10 assertions
- [x] Pulse en `http://localhost:8080/pulse` (admin) con métricas visibles
- [x] Migraciones Pulse/cola: `0016_pulse`, `0015_queue`, `0017_app_seeds` (por ruta, no `migrate` completo)

---

## 4. Qué falta para cerrar el Seguimiento 8

### 4.1 Repositorio y CI en la nube

- [ ] `git push` de `feature/ci-cd-follow-up` (si aún no está en remoto)
- [ ] Pull Request → merge a **`Dev`**
- [ ] Verificar **GitHub Actions** verde en `Dev` (PHP + Frontend + Deploy)
- [ ] Confirmar deploy en Render tras CI verde

### 4.2 Configuración única (equipo)

| Tarea | Dónde |
|-------|--------|
| Secret `RENDER_DEPLOY_HOOK_URL` | GitHub → Settings → Secrets → Actions |
| `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_TO` | Igual (correos de éxito/fallo CI) |
| **Auto-Deploy = Off** | Render → servicio web → Settings |
| Branch protection en `Dev` | GitHub → Branches (checks obligatorios) |

### 4.3 Video obligatorio

Grabar **dos escenarios**:

**A) Camino feliz**

1. Commit / push a `Dev`
2. CI verde en GitHub Actions
3. Deploy automático a Render
4. Cambio visible en producción (URL Render)

**B) CI rojo (sin deploy)**

1. Introducir un fallo a propósito (test o lint)
2. Push
3. CI rojo en GitHub
4. Confirmar que **no** hubo deploy en Render

> Dusk y Newman se demuestran **en local** en el video (no corren en Actions por defecto).

### 4.4 Evidencias y documento del curso

- [ ] Tabla: **3 pruebas por integrante** (quién hizo qué)
- [ ] Capturas: GitHub Actions, Render, Newman, Dusk, Pulse
- [ ] Carpeta sugerida: `docs/evidencia/` (screenshots organizados por fecha)

### 4.5 Producción post-deploy

- [ ] `https://ciclo-finca-4-app-g0j4.onrender.com/up` → 200
- [ ] Home y flujo crítico en Render
- [ ] `/pulse` en Render (login admin + migración Pulse en producción)

---

## 5. Guía paso a paso — comandos (local)

> **Shell:** los comandos funcionan en bash/fish salvo donde se indica alternativa.
> **Requisitos:** Docker, `.env` configurado, proyecto en la raíz del repo.

### Paso 0 — Ir al proyecto y levantar Docker

```bash
cd "/home/aarons_aa/Documentos/UNA/UNA Cuarto año I Ciclo/Ciclo_Finca_4_App"

docker compose up -d app_ciclo db_ciclo selenium_ciclo
docker compose ps
```

Esperar ~10–15 s. Verificar en navegador: http://localhost:8080

### Paso 1 — Dependencias (solo tras `git pull` o si fallan tests)

```bash
docker compose exec app_ciclo composer install
docker compose exec app_ciclo npm ci
```

### Paso 2 — Migraciones Pulse / cola (una vez; no usar `migrate` a ciegas)

En bases de datos **ya usadas** con migraciones antiguas, `php artisan migrate` puede fallar en `0010_price_histories` (tabla ya existe). Usar rutas concretas:

```bash
docker compose exec app_ciclo php artisan migrate --path=database/migrations/0016_pulse.php
docker compose exec app_ciclo php artisan migrate --path=database/migrations/0015_queue.php
docker compose exec app_ciclo php artisan migrate --path=database/migrations/0017_app_seeds.php
```

Si responde `Nothing to migrate`, ya está aplicado.

### Paso 3 — Chequeo completo “como CI” (recomendado antes de push)

```bash
./scripts/ci-check-docker.sh
```

**Esperado al final:** `>>> CI parity checks passed.`

Equivalente manual por partes:

```bash
docker compose exec app_ciclo composer run lint
./scripts/test-mysql-docker.sh
docker compose exec app_ciclo composer run phpstan
docker compose exec app_ciclo npm ci
docker compose exec app_ciclo npm run build
```

### Paso 4 — API PHPUnit (Seguimiento 8)

```bash
./scripts/test-mysql-docker.sh tests/Feature/Api/StorefrontApiTest.php
```

**Esperado:** `OK (4 tests, … assertions)`

### Paso 5 — API Newman (Postman CLI)

Desde el **host** (con Docker levantado y puerto 8080 publicado):

```bash
npm run test:api
```

Alternativa **dentro** del contenedor:

```bash
docker compose exec app_ciclo npm run test:api:docker
```

**Esperado:** 4 requests, 7 assertions, **0 failed**.

> Si `npm ci` falla por lock desincronizado: `docker compose exec app_ciclo npm install` y commitear `package-lock.json`.

### Paso 6 — UI Laravel Dusk

Un solo comando (recomendado; no requiere Composer ni Chrome en el host):

```bash
./scripts/run-dusk-docker.sh
```

**Esperado:** `5 passed (10 assertions)`

#### Dusk manual (avanzado, host con Chrome + Composer)

```bash
cp .env.dusk.example .env.dusk.local
# Editar: APP_KEY (copiar de .env), RECAPTCHA vacío, DB_* según host

docker compose up -d
docker compose exec app_ciclo npm run build
composer run dusk
```

### Paso 7 — Pulse

```bash
# Una captura de métricas (no queda colgado)
docker compose exec app_ciclo php artisan pulse:check --once
```

Luego en navegador (como **admin**):

1. Navegar home, catálogo, dashboard (genera tráfico)
2. Abrir http://localhost:8080/pulse
3. Confirmar CPU/memoria, Application Usage, Cache, etc.

---

## 6. Pruebas manuales en navegador (opcional pero útil)

| # | Qué probar | URL | Qué validar |
|---|------------|-----|-------------|
| 1 | Health | http://localhost:8080/up | Respuesta 200 |
| 2 | Home | http://localhost:8080/ | Carga sin error |
| 3 | Catálogo | http://localhost:8080/catalog | Listado / hero visible |
| 4 | Login cliente | http://localhost:8080/login | Formulario Inertia |
| 5 | Login admin | http://localhost:8080/admin/login | Formulario admin |
| 6 | Dashboard admin | http://localhost:8080/dashboard | Tras login admin |
| 7 | Pulse | http://localhost:8080/pulse | Dashboard Pulse (solo admin) |
| 8 | Términos | http://localhost:8080/legal/terminos | Página legal |

### Postman GUI (demo visual, opcional)

1. Abrir Postman
2. Import → `postman/CF4-Storefront-API.postman_collection.json`
3. Variable `baseUrl` = `http://localhost:8080`
4. Run collection → todas las peticiones en verde

---

## 7. Flujo Git recomendado (después de probar local)

```bash
# Verificar limpio
git status

# Push de la rama feature
git push origin feature/ci-cd-follow-up

# Crear PR hacia Dev (GitHub UI o gh CLI)
# Tras merge a Dev → observar Actions + Render
```

### Commit en Fish (si necesitás otro commit)

```fish
git commit -m "feat(CF4-8): descripción corta" -m "Cuerpo del commit en una línea o párrafo."
```

---

## 8. Problemas frecuentes y soluciones

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `npm ci` — Missing `newman@…` | `package-lock.json` desincronizado | `docker compose exec app_ciclo npm install` + commit lock |
| Newman `ECONNREFUSED :8080` desde Docker | Puerto 8080 no existe dentro del contenedor | `npm run test:api:docker` o `test:api` desde host |
| `composer: orden no encontrada` | Composer no instalado en el host | Usar `docker compose exec app_ciclo composer …` o `run-dusk-docker.sh` |
| Dusk — Chromedriver / Chrome | Falta Selenium o Chrome | `./scripts/run-dusk-docker.sh` (usa `selenium_ciclo`) |
| Dusk login timeout | reCAPTCHA o JSON Inertia | `.env.dusk.local` sin `RECAPTCHA_*`; fix ya en código |
| `migrate` falla en `0010_price_histories` | BD antigua + migraciones consolidadas | Migrar por `--path` (Pulse, queue, seeds) |
| `pulse:check` no termina | Comando en bucle continuo | Usar `pulse:check --once` o Ctrl+C |
| Pint falla en `DuskTestCase.php` | Estilo PHP (import `Kernel`) | `docker compose exec app_ciclo ./vendor/bin/pint tests/DuskTestCase.php` |
| PHPStan `noEnvCallsOutsideOfConfig` | `env()` en DuskTestCase | Ya corregido: `$_ENV` / `$_SERVER` |
| `git dubious ownership` en Docker | Permisos del volumen | Ruido; no bloquea tests si el exit code es 0 |

---

## 9. Matriz de pruebas sugerida (tabla del equipo)

Completar una fila por integrante (ejemplo de herramientas):

| Integrante | Prueba 1 | Prueba 2 | Prueba 3 |
|------------|----------|----------|----------|
| Nombre A | PHPUnit API (`StorefrontApiTest`) | Newman (`npm run test:api`) | Dusk (`run-dusk-docker.sh`) |
| Nombre B | `ci-check-docker.sh` | Pulse manual `/pulse` | Postman GUI colección |
| Nombre C | … | … | … |

---

## 10. Referencias rápidas de archivos

| Recurso | Ruta |
|---------|------|
| Workflow CI/CD | `.github/workflows/ci-cd-dev.yml` |
| Doc CI técnica | `docs/CI.md` |
| Chequeo local = CI | `scripts/ci-check-docker.sh` |
| PHPUnit MySQL | `scripts/test-mysql-docker.sh` |
| Dusk Docker | `scripts/run-dusk-docker.sh` |
| Colección Postman | `postman/CF4-Storefront-API.postman_collection.json` |
| Env Dusk plantilla | `.env.dusk.example` |
| Tests API | `tests/Feature/Api/StorefrontApiTest.php` |
| Tests UI | `tests/Browser/*Test.php` |

---

## 11. Orden sugerido el día de la demo

1. `docker compose up -d app_ciclo db_ciclo selenium_ciclo`
2. `./scripts/ci-check-docker.sh`
3. `./scripts/test-mysql-docker.sh tests/Feature/Api/StorefrontApiTest.php`
4. `npm run test:api`
5. `./scripts/run-dusk-docker.sh`
6. Navegar sitio + abrir `/pulse` (captura)
7. (Si aplica) Mostrar GitHub Actions + Render en producción

---

*Última actualización: mayo 2026 — rama `feature/ci-cd-follow-up`, entregable Seguimiento 8 Ciclo Finca 4.*
