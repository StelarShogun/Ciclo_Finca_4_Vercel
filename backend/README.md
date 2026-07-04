# Ciclo Finca 4 Backend

Laravel funciona como API y backend operativo para el frontend Next en `../frontend`.

## Contrato activo

- API principal: `routes/api.php`, prefijo `/api/v1`.
- Superficie web temporal: `routes/web.php` conserva utilidades internas, OAuth Google, CSRF, Pulse y artefactos técnicos de impresión, PDF, Excel y email.
- UI legacy Inertia/Blade: en proceso de retiro. No agregar páginas nuevas en `backend/resources/ts/Pages`, controladores con `Inertia::render` ni vistas Blade de UI normal.

## Inventario UI legacy

```bash
php scripts/audit-legacy-ui.php --json
php scripts/audit-legacy-ui.php --markdown
```

El baseline versionado vive en `docs/legacy-ui-baseline.json`. El test `LegacyUiInventoryTest` falla si aparecen nuevas rutas, vistas, assets o referencias UI legacy clasificadas como `delete`, `migrate-first` o `unknown`.

Para reducir la deuda, borra o migra una entrada y actualiza el baseline con:

```bash
php scripts/audit-legacy-ui.php --json > docs/legacy-ui-baseline.json
php scripts/audit-legacy-ui.php --markdown > docs/legacy-ui-inventory.md
```

## Validación

```bash
PHP_INI_SCAN_DIR=:scripts/php-ini.d php artisan config:clear
PHP_INI_SCAN_DIR=:scripts/php-ini.d php ./vendor/bin/phpunit --filter=LegacyUiInventoryTest
./scripts/ci-check-docker.sh
```
