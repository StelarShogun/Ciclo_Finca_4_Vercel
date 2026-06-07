# CI/CD — Ciclo Finca 4 App

Integración continua y despliegue continuo con **GitHub Actions** en la rama `Dev`.

**Producción:** [https://ciclo-finca-4-app-g0j4.onrender.com](https://ciclo-finca-4-app-g0j4.onrender.com)

## Flujo completo

```
push a Dev  →  php-checks + frontend-build  →  ¿ambos verdes?
                                              │
                               ┌──────────────┴──────────────┐
                               │ SÍ (solo push, no PR)        │ NO
                               ▼                              ▼
                    deploy → Render hook              NO deploy + correo fallo
                    health /up + smoke home
                    correo éxito
```

Los **pull requests** ejecutan CI pero **no despliegan**.

## Qué ejecuta CI/CD

Archivo: [`.github/workflows/ci-cd-dev.yml`](../.github/workflows/ci-cd-dev.yml)

| Job | Cuándo | Qué hace |
|-----|--------|----------|
| **PHP — lint, tests, static analysis** | push + PR | Pint, PHPUnit con **MySQL 8**, PHPStan |
| **Frontend — Vite production build** | push + PR | `npm ci` + `npm run build` |
| **CD — Deploy to Render** | solo push a `Dev` si los jobs anteriores pasan | Deploy Hook → `/up` → smoke `/` → correo |

## Configuración obligatoria (una vez)

### 1. Render — desactivar auto-deploy

En Render → servicio web → **Settings** → **Auto-Deploy: No**.

Así **solo GitHub Actions** dispara producción cuando CI pasa.

### 2. Render — Deploy Hook

En el mismo servicio → **Settings** → **Deploy Hook** → copiar la URL (`https://api.render.com/deploy/srv-...?key=...`).

### 3. GitHub — Secrets

Repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secreto | Valor |
|---------|--------|
| `RENDER_DEPLOY_HOOK_URL` | URL del Deploy Hook de Render |
| `MAIL_USERNAME` | Gmail del grupo (ej. `grupo@gmail.com`) |
| `MAIL_PASSWORD` | [Contraseña de aplicación](https://myaccount.google.com/apppasswords) de Gmail (requiere 2FA) |
| `MAIL_TO` | Destinatarios (varios separados por coma) |

Sin `RENDER_DEPLOY_HOOK_URL`, el job **deploy** fallará tras CI verde (deploy no autorizado).

Sin secretos de correo, CI/deploy pueden pasar pero los pasos de email fallarán; configurarlos para la demo del seguimiento 8.

## Cómo correr la suite completa (recomendado antes de push)

```bash
docker compose up -d
./scripts/ci-check-docker.sh
```

O por partes:

```bash
docker compose exec app_ciclo composer run lint
./scripts/test-mysql-docker.sh          # PHPUnit + MySQL
docker compose exec app_ciclo composer run phpstan
docker compose exec app_ciclo npm ci && npm run build
```

### Composer

| Comando | Uso |
|---------|-----|
| `composer run test` | Suite PHPUnit (MySQL; igual que CI) |
| `composer run test:mysql` | Alias de `composer run test` |
| `composer run check` | lint + test + phpstan |
| `composer run check:full` | Igual que `check` |

### Base de datos de pruebas

Por defecto: `{DB_DATABASE}_test` (ej. `laravel_test`). Opcional en `.env`:

```env
DB_TEST_DATABASE=ciclo_finca_test
```

El script `scripts/test-mysql-docker.sh` crea la BD si no existe.

## Entorno CI (GitHub Actions)

| Variable | Valor |
|----------|--------|
| PHP | 8.5 |
| MySQL | 8.0 (servicio `mysql`) |
| `DB_DATABASE` | `ciclo_finca_test` |
| Node | 20 |
| `PRODUCTION_URL` | `https://ciclo-finca-4-app-g0j4.onrender.com` |

## Troubleshooting

| Síntoma | Acción |
|---------|--------|
| CI verde pero deploy falla | Configurar `RENDER_DEPLOY_HOOK_URL`; verificar Auto-Deploy **Off** en Render |
| Health check timeout tras deploy | Render Free tarda en build/cold start; reintentar o revisar logs en Render |
| Correo no llega | Revisar `MAIL_*` secrets y contraseña de aplicación Gmail |
| `Connection refused` MySQL (local) | `docker compose up -d` y esperar ~15 s |
| Pint falla | `docker compose exec app_ciclo ./vendor/bin/pint` |

## Guía para el equipo (antes de mergear a `Dev`)

### Regla práctica

| Si… | Entonces… |
|-----|-----------|
| No pasa **CI en GitHub** (checks rojos en el PR) | **No mergear** a `Dev` hasta corregir |
| Solo probaste en el navegador | **No basta** — hay que correr los mismos checks que CI |
| Quieres ir rápido local | Mínimo: `./scripts/ci-check-docker.sh` |

GitHub ejecuta [`.github/workflows/ci-cd-dev.yml`](../.github/workflows/ci-cd-dev.yml): **Pint + PHPUnit (MySQL) + PHPStan + `npm run build`**. Tras merge/push a `Dev`, si todo pasa, **CD despliega en Render** (Deploy Hook).

### Checklist antes de abrir PR o pedir merge

```bash
cd ~/inge/Ciclo_Finca_4_App
git checkout tu-rama
git pull origin Dev    # traer último Dev
docker compose up -d   # primera vez o si estaba apagado
./scripts/ci-check-docker.sh
```

Si todo termina con `CI parity checks passed` y en GitHub los checks están verdes → OK para merge.

### Un solo comando (= lo mismo que CI local)

```bash
./scripts/ci-check-docker.sh
```

Hace en orden: **Pint** → **tests MySQL completos** → **PHPStan** → **npm ci + build**.

### Comandos por paso (si quieren depurar)

Desde la raíz del proyecto, con Docker levantado:

```bash
# 1) Estilo de código (debe pasar sin cambios pendientes)
docker compose exec app_ciclo composer run lint
# Arreglar automático:
docker compose exec app_ciclo ./vendor/bin/pint

# 2) Tests completos con MySQL (obligatorio antes de merge — no uses solo composer run test)
./scripts/test-mysql-docker.sh

# 3) Análisis estático
docker compose exec app_ciclo composer run phpstan

# 4) Frontend compila
docker compose exec app_ciclo npm ci
docker compose exec app_ciclo npm run build
```

Atajos Composer (dentro del contenedor):

```bash
docker compose exec app_ciclo composer run check       # lint + test SQLite + phpstan (rápido, muchos skipped)
docker compose exec app_ciclo composer run check:full # lint + test:mysql + phpstan (sin npm build)
```

### Tests específicos (un archivo, clase o método)

Siempre con **MySQL** (misma config que CI):

```bash
# Un archivo de test
./scripts/test-mysql-docker.sh tests/Feature/SupplierOrderCreateTest.php

# Un método concreto
./scripts/test-mysql-docker.sh --filter=test_cp03_01_create_supplier_order_success

# Una carpeta
./scripts/test-mysql-docker.sh tests/Feature/

# Suite Unit solamente
./scripts/test-mysql-docker.sh tests/Unit/
```

Equivalente manual (si ya exportaste variables de `.env`):

```bash
docker compose exec \
  -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=db_ciclo \
  -e DB_DATABASE="${DB_DATABASE}_test" \
  -e DB_USERNAME="${DB_USERNAME}" -e DB_PASSWORD="${DB_PASSWORD}" \
  app_ciclo ./vendor/bin/phpunit --filter=NombreDelTest
```

### Qué significa el resultado de tests

| Salida | Significado | ¿Merge? |
|--------|-------------|---------|
| `OK` / verde en GitHub | Todo pasó | Sí (si review OK); push a `Dev` dispara CD |
| `FAILURES` | Test roto — corregir código o test | **No** — no hay deploy |

### Errores frecuentes

| Problema | Solución |
|----------|----------|
| Pint falla | `docker compose exec app_ciclo ./vendor/bin/pint` y commitear |
| Muchos skipped local | Usar `./scripts/test-mysql-docker.sh` (MySQL en Docker) |
| MySQL connection refused | `docker compose up -d` y esperar ~15 s |
| `dubious ownership` en Docker | Ignorar; no afecta tests |
| CI rojo en GitHub pero local OK | `git pull origin Dev`, `./scripts/ci-check-docker.sh`, push de nuevo |

### Protección en GitHub (recomendado para admins del repo)

En **Settings → Branches → rule for `Dev`**:

- Require status checks: **PHP — lint, tests, static analysis**, **Frontend — Vite production build**, y opcionalmente **CD — Deploy to Render** si queréis bloquear merge cuando falte el hook
- Require pull request before merging

Así nadie mergea si los checks no pasan.

## Cambios recientes (CI + tests)

- CI/CD en GitHub: **MySQL** para PHPUnit, job **deploy** a Render solo si CI pasa (rama `Dev`).
- `phpunit.xml` usa MySQL; `./scripts/test-mysql-docker.sh` y `composer run test` son equivalentes.
- `ci-check-docker.sh` ejecuta la suite MySQL completa antes de push.

## Seguimiento 8 — Dusk, API y Pulse

### Pruebas de API (automáticas, sin Postman manual)

| Herramienta | Comando | Cuándo usar |
|-------------|---------|-------------|
| **PHPUnit** (corre en CI) | `./scripts/test-mysql-docker.sh tests/Feature/Api/StorefrontApiTest.php` | Misma puerta de calidad que el resto de la suite |
| **Newman** (CLI de Postman) | `npm ci && npm run test:api` | Demo/evidencia con la colección exportada |
| **Postman GUI** | Importar `postman/CF4-Storefront-API.postman_collection.json` | Exploración manual |

Newman ejecuta la misma colección que Postman, con aserciones en cada request.

### Laravel Dusk (4 pruebas de interfaz)

Archivos en `tests/Browser/` (login, registro, catálogo, términos legales).

**Requisitos:** Google Chrome en el host, app accesible en `APP_URL`, assets compilados (`npm run build`).

```bash
cp .env.dusk.example .env.dusk.local   # ajustar DB_* si hace falta
docker compose up -d
docker compose exec app_ciclo npm run build
composer run dusk                        # desde el host, con Chrome
# o: php artisan dusk
```

Dusk **no** corre en GitHub Actions por defecto (Chrome + app levantada); ejecutarlo localmente para el video del seguimiento 8.

Plantilla de entorno: [`.env.dusk.example`](../.env.dusk.example) · config PHPUnit: [`phpunit.dusk.xml`](../phpunit.dusk.xml).

### Laravel Pulse (monitoreo)

- Dashboard: `https://ciclo-finca-4-app-g0j4.onrender.com/pulse` (solo admin autenticado).
- Migraciones publicadas en `database/migrations/*pulse*`.
- En producción, el scheduler ejecuta `pulse:check` cada 5 minutos (`routes/console.php`).
- En tests PHPUnit: `PULSE_ENABLED=false` en `phpunit.xml`.

Local:

```bash
docker compose exec app_ciclo php artisan migrate
# Generar tráfico y abrir /pulse tras login admin
```

## Render — Pre-Deploy (migraciones unificadas)

Tras el squash de migraciones (`0001`–`0017`), producción que ya corrió migraciones fechadas (`2024_*`, `2026_*`) necesita reconciliar la tabla `migrations` antes de `migrate`.

**Dashboard → Web Service → Settings → Pre-Deploy Command:**

```bash
bash scripts/render-pre-deploy.sh
```

El script (idempotente):

1. `php artisan cf4:reconcile-squashed-migrations --force` — registra `0010`–`0015` y `0017` como ejecutadas si el esquema legacy ya existe; elimina filas `2024_*` / `2026_*`.
2. `php artisan migrate --force` — crea lo pendiente real (p. ej. `0016_pulse` si aún no hay tablas Pulse).

Manual (Render Shell):

```bash
cd /var/www/html
php artisan cf4:reconcile-squashed-migrations --force
php artisan migrate --force
php artisan migrate:status
```

Hacer **snapshot de la BD** antes del primer deploy con squash + pre-deploy.
