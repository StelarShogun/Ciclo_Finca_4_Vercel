# CI/CD — Ciclo Finca 4 App

Integración continua con **GitHub Actions** en la rama `Dev`. No hay deploy automático en este repo (Docker / Render / manual).

## Qué ejecuta CI

Archivo: [`.github/workflows/ci-dev.yml`](../.github/workflows/ci-dev.yml)

| Job | Qué hace |
|-----|----------|
| **PHP — lint, tests, static analysis** | Pint, PHPUnit con **MySQL 8**, PHPStan |
| **Frontend — Vite production build** | `npm ci` + `npm run build` |

## Por qué algunos tests se saltaban (skipped)

Hay **dos modos** de ejecutar tests:

| Modo | Comando | Base de datos | Resultado típico |
|------|---------|---------------|------------------|
| Rápido | `composer run test` | SQLite en memoria | ~112 pasan, **~142 skipped** |
| Completo | `composer run test:mysql` o `./scripts/test-mysql-docker.sh` | MySQL (Docker) | **248 pasan**, 6 skipped (datos de catálogo) |

**Motivo técnico:** muchas migraciones usan funciones solo de MySQL (`ENUM`, `JSON_TABLE`, `FULLTEXT`). En SQLite esas migraciones no aplican el esquema completo. Los tests detectan `driver !== mysql` o tablas faltantes y llaman a `markTestSkipped()` — **no es que no se puedan probar**, sino que hace falta MySQL.

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
| `composer run test` | Smoke rápido (SQLite) |
| `composer run test:mysql` | Suite completa (requiere MySQL; en Docker usa el script) |
| `composer run check` | lint + test (SQLite) + phpstan |
| `composer run check:full` | lint + test:mysql + phpstan |

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

## Tests que aún pueden fallar en MySQL

Tras habilitar MySQL, la mayoría de los skips desaparecen. Si algún test **falla** (no skip), suele ser assertion desactualizada o datos de prueba — revisar el mensaje en la salida de PHPUnit.

Ejemplo corregido: `SupplierOrderCreateTest` ya no exige `estimated_delivery_date` (campo eliminado en CF4-143).

## Troubleshooting

| Síntoma | Acción |
|---------|--------|
| Muchos `skipped` | Usar `./scripts/test-mysql-docker.sh`, no solo `composer run test` |
| CI falla con “248 passed” y exit 1 | `php artisan test` trata skips como fallo; `composer run test:mysql` usa PHPUnit directo (igual que el script Docker) |
| `Connection refused` MySQL | `docker compose up -d` y esperar ~15 s |
| Pint falla | `docker compose exec app_ciclo ./vendor/bin/pint` |
| Aviso git `dubious ownership` en Docker | Cosmético; no afecta PHPUnit |

## Guía para el equipo (antes de mergear a `Dev`)

### Regla práctica

| Si… | Entonces… |
|-----|-----------|
| No pasa **CI en GitHub** (checks rojos en el PR) | **No mergear** a `Dev` hasta corregir |
| Solo probaste en el navegador | **No basta** — hay que correr los mismos checks que CI |
| Quieres ir rápido local | Mínimo: `./scripts/ci-check-docker.sh` |

GitHub ejecuta lo de [`.github/workflows/ci-dev.yml`](../.github/workflows/ci-dev.yml): **Pint + PHPUnit (MySQL) + PHPStan + `npm run build`**.

No hay CD automático en este repo: mergear a `Dev` **no despliega** solo; el deploy es aparte (Render/Docker manual).

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
  app_ciclo ./vendor/bin/phpunit -c phpunit.mysql.xml --filter=NombreDelTest
```

### Qué significa el resultado de tests

| Salida | Significado | ¿Merge? |
|--------|-------------|---------|
| `OK` / verde en GitHub | Todo pasó | Sí (si review OK) |
| `FAILURES` | Test roto — corregir código o test | **No** |
| `Skipped: 6` (CF497) | Normal — faltan marcas de demo en BD test | Sí si no hay failures |
| `Skipped: 142` con `composer run test` | Usaste SQLite — **vuelve a correr** `./scripts/test-mysql-docker.sh` | No confiar hasta MySQL |

### Errores frecuentes

| Problema | Solución |
|----------|----------|
| Pint falla | `docker compose exec app_ciclo ./vendor/bin/pint` y commitear |
| Muchos skipped | No usar solo `composer run test`; usar `./scripts/test-mysql-docker.sh` |
| MySQL connection refused | `docker compose up -d` y esperar ~15 s |
| `dubious ownership` en Docker | Ignorar; no afecta tests |
| CI rojo en GitHub pero local OK | `git pull origin Dev`, `./scripts/ci-check-docker.sh`, push de nuevo |

### Protección en GitHub (recomendado para admins del repo)

En **Settings → Branches → rule for `Dev`**:

- Require status checks: **PHP — lint, tests, static analysis** y **Frontend — Vite production build**
- Require pull request before merging

Así nadie mergea si los checks no pasan.

## Cambios recientes (CI + tests)

- CI en GitHub usa **MySQL** para PHPUnit (menos skips).
- Añadidos `phpunit.mysql.xml`, `scripts/test-mysql-docker.sh`, trait `InteractsWithMysqlTestDatabase`.
- `FavoriteListTest` y `ClientInvoiceDetailTest` usan `RefreshDatabase` + MySQL.
- `ci-check-docker.sh` ejecuta la suite MySQL completa.
