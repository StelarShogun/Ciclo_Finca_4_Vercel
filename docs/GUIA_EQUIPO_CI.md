# Guía del equipo — CI y tests antes de mergear a `Dev`

Documento para compañeros: qué correr localmente **antes** de abrir PR o mergear, para que coincida con lo que valida GitHub Actions.

Documentación técnica adicional: [CI.md](CI.md)

---

## Regla de oro

| Si… | Entonces… |
|-----|-----------|
| **CI en GitHub está en rojo** (checks del PR) | **No mergear** a `Dev` hasta corregir |
| Solo probaste en el navegador (`localhost:8080`) | **No basta** — hay que pasar los checks de CI |
| Quieres estar seguro antes de push | Ejecuta `./scripts/ci-check-docker.sh` |

**CI ≠ CD:** mergear a `Dev` **no despliega** la página en producción sola. CI solo valida que el código compila, cumple estilo y pasa tests. El deploy (Render, Docker manual, etc.) es otro paso.

---

## Qué ejecuta GitHub en cada PR hacia `Dev`

Archivo: `.github/workflows/ci-cd-dev.yml`

| Job en GitHub | Qué hace |
|---------------|----------|
| **PHP — lint, tests, static analysis** | Laravel Pint, PHPUnit con **MySQL 8**, PHPStan |
| **Frontend — Vite production build** | `npm ci` + `npm run build` |

**Recomendación para administradores del repo:** en GitHub → **Settings → Branches → regla para `Dev`** → exigir esos dos checks y PR obligatorio antes de merge.

---

## Checklist antes de cada PR

```bash
cd ~/inge/Ciclo_Finca_4_App
git checkout mi-rama-feature
git pull origin Dev
docker compose up -d
./scripts/ci-check-docker.sh
```

Si el script termina con:

```text
>>> CI parity checks passed.
```

y en GitHub los checks del PR están **verdes** → se puede pedir review y merge.

---

## Un solo comando (= lo mismo que CI local)

```bash
./scripts/ci-check-docker.sh
```

Ejecuta en este orden:

| Paso | Qué valida |
|------|------------|
| 1 | **Pint** — estilo de código PHP |
| 2 | **PHPUnit + MySQL** — suite completa de tests (~248 pasan) |
| 3 | **PHPStan** — análisis estático |
| 4 | **npm ci + npm run build** — assets Vite compilan |

**Requisitos:** Docker instalado, `.env` configurado (como siempre), contenedores levantados (el script los inicia si hace falta).

---

## Comandos por paso (para depurar errores)

Con Docker levantado (`docker compose up -d`):

```bash
# 1) Estilo de código
docker compose exec app_ciclo composer run lint

# Arreglar estilo automáticamente (luego commitear los cambios)
docker compose exec app_ciclo ./vendor/bin/pint

# 2) Tests completos — OBLIGATORIO antes de merge (usa MySQL, no SQLite)
./scripts/test-mysql-docker.sh

# 3) Análisis estático
docker compose exec app_ciclo composer run phpstan

# 4) Frontend
docker compose exec app_ciclo npm ci
docker compose exec app_ciclo npm run build
```

### Atajos Composer (dentro del contenedor)

```bash
# Rápido: incluye tests en SQLite (~142 skipped) — NO sustituye al script MySQL
docker compose exec app_ciclo composer run check

# PHP sin npm build: lint + test MySQL + phpstan
docker compose exec app_ciclo composer run check:full
```

---

## Tests específicos (archivo, clase o método)

Usar siempre **MySQL** (igual que CI):

```bash
# Un archivo completo
./scripts/test-mysql-docker.sh tests/Feature/SupplierOrderCreateTest.php

# Un método concreto (filtro PHPUnit)
./scripts/test-mysql-docker.sh --filter=test_completed_sale_can_be_returned

# Toda la carpeta Feature
./scripts/test-mysql-docker.sh tests/Feature/

# Solo tests Unit
./scripts/test-mysql-docker.sh tests/Unit/
```

### Varios filtros útiles

```bash
# Por nombre de clase
./scripts/test-mysql-docker.sh --filter=CF493SaleReturnTest

# Por parte del nombre del método
./scripts/test-mysql-docker.sh --filter=create_supplier_order
```

---

## SQLite vs MySQL — no confundir

| Comando | Base de datos | Resultado típico | ¿Vale para merge? |
|---------|---------------|------------------|-------------------|
| `composer run test` | SQLite en RAM | ~112 pasan, **~142 skipped** | **No** (smoke rápido nada más) |
| `./scripts/test-mysql-docker.sh` | MySQL (`{DB_DATABASE}_test`) | **~248 pasan**, 6 skipped | **Sí** (es lo de CI) |

Muchos tests se saltan en SQLite porque el esquema real usa migraciones solo de MySQL (`ENUM`, `JSON_TABLE`, `FULLTEXT`). **No es un bug:** hay que usar el script MySQL.

---

## Cómo leer el resultado de PHPUnit

| Salida | Significado | ¿Se puede mergear? |
|--------|-------------|-------------------|
| `OK` / checks verdes en GitHub | Todo pasó | Sí (si el review está OK) |
| `FAILURES` / `ERRORS` | Test o código roto | **No** — corregir y volver a correr |
| `Skipped: 6` | Normal (p. ej. `CF497` — faltan marcas demo en BD test) | Sí, si no hay failures |
| `Skipped: 142` | Usaste `composer run test` (SQLite) | **No confiar** — correr `./scripts/test-mysql-docker.sh` |

---

## Base de datos de pruebas (local)

- El script crea `{DB_DATABASE}_test` si no existe (ej. `laravel_test`).
- Lee credenciales de tu `.env` (`DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`).
- **No subas `.env` a Git** — ya está en `.gitignore`.
- Opcional en `.env`:

```env
DB_TEST_DATABASE=ciclo_finca_test
```

---

## Errores frecuentes

| Problema | Qué hacer |
|----------|-----------|
| **Pint falla** en CI | `docker compose exec app_ciclo ./vendor/bin/pint` → commit → push |
| **Muchos skipped** | No uses solo `composer run test`; usa `./scripts/test-mysql-docker.sh` |
| **MySQL connection refused** | `docker compose up -d` y espera ~15 s |
| **Aviso git `dubious ownership`** en Docker | Cosmético; no afecta tests |
| **CI rojo en GitHub, local OK** | `git pull origin Dev`, `./scripts/ci-check-docker.sh`, push de nuevo |
| **npm build falla** | `docker compose exec app_ciclo npm ci` y commitear `package-lock.json` si cambió |

---

## Seguridad al subir código

- Este flujo **no expone** credenciales de producción (`.env` no va al repo).
- Las contraseñas `laravel` / `password` del workflow de GitHub son solo para MySQL **temporal en CI**, no para tu servidor en vivo.
- Mergear con CI verde **no da acceso** a producción a nadie que vea el repo.

---

## Resumen para copiar al chat del equipo

```text
Antes de mergear a Dev:
  1. git pull origin Dev
  2. docker compose up -d
  3. ./scripts/ci-check-docker.sh

Test de un archivo:
  ./scripts/test-mysql-docker.sh tests/Feature/MiArchivoTest.php

Test de un método:
  ./scripts/test-mysql-docker.sh --filter=nombre_del_metodo

NO usar solo "composer run test" (muchos skipped en SQLite).

Si GitHub está rojo → no mergear.

Más detalle: docs/GUIA_EQUIPO_CI.md y docs/CI.md
```

---

## Scripts y archivos de referencia

| Archivo | Para qué sirve |
|---------|----------------|
| `scripts/ci-check-docker.sh` | Replica CI local (Pint + tests MySQL + PHPStan + build) |
| `scripts/test-mysql-docker.sh` | Solo PHPUnit con MySQL |
| `scripts/pint-docker.sh` | Solo Pint (con/sin `--test`) |
| `phpunit.mysql.xml` | Config PHPUnit para MySQL |
| `phpunit.xml` | Config PHPUnit para SQLite (smoke) |
| `.github/workflows/ci-cd-dev.yml` | Pipeline en GitHub |
| `docs/CI.md` | Documentación técnica CI/CD |

---

*Última actualización: alineado con la implementación CI en rama `Dev` (PHPUnit MySQL, jobs PHP + frontend).*
