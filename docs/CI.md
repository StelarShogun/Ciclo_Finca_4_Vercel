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
| `Connection refused` MySQL | `docker compose up -d` y esperar ~15 s |
| Pint falla | `docker compose exec app_ciclo ./vendor/bin/pint` |
| Aviso git `dubious ownership` en Docker | Cosmético; no afecta PHPUnit |

## Cambios recientes (CI + tests)

- CI en GitHub usa **MySQL** para PHPUnit (menos skips).
- Añadidos `phpunit.mysql.xml`, `scripts/test-mysql-docker.sh`, trait `InteractsWithMysqlTestDatabase`.
- `FavoriteListTest` y `ClientInvoiceDetailTest` usan `RefreshDatabase` + MySQL.
- `ci-check-docker.sh` ejecuta la suite MySQL completa.
