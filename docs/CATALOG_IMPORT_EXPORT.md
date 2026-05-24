# Product catalog import / export (admin inventory)

## Export formats

From **Inventory → Exportar**:

| Format | Use case |
|--------|----------|
| **ZIP (bundle)** | Full migration: `catalog.json` manifest + product images under `images/`. Use **Catálogo completo** for all rows or **ZIP con filtros actuales** for the filtered list. |
| **JSON** | Data-only backup or integration (no binary images in the file). |
| **XML / Excel / PDF** | Reports and supplier hand-offs; column mapping is flexible on import. |

## Import

**Inventory → Importar** accepts:

- **ZIP** — same structure as the bundle export (recommended for cloning environments).
- **CSV / XML / JSON** — supplier files; column names can be in Spanish or English and in any order (`nombre`, `precio_venta`, `stock_actual`, `categoria`, `marca`, etc.).

Products match by `product_id`, SKU, or **name + category**. Missing fields keep sensible defaults.

Maximum upload size: **100 MB** (see validation in `ProductController::importCatalog`).

## Typical workflow

1. On source environment: **Exportar → Catálogo completo (ZIP + imágenes)**.
2. Copy the `.zip` to the target server.
3. On target: **Importar** → select the ZIP → confirm.
4. Run `php artisan cache:clear` only if storefront still shows stale catalog (normally automatic via `ClientStorefrontCache`).

## Docker

```bash
docker compose exec app_ciclo php artisan test --filter=ProductCatalogImportExportTest
```
