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

**ZIP bundle imports** also **create missing parent/sub categories** from `category_path` when the target database does not have them yet (typical when cloning local → Render).

Maximum upload size: **100 MB** (Laravel validation). PHP must allow at least that much: `upload_max_filesize` and `post_max_size` (see `docker/php/uploads.ini` in Docker).

Bulk ZIP import runs in **fast mode**: copies images without re-encoding during the upload, then **automatically generates WebP conversions in the background** after the import response returns.

### Import performance (2026-06)

`App\Services\Admin\ProductCatalog\ProductCatalogImporter` precarga en memoria (una consulta por tabla, no por fila). Opciones: `App\Data\Admin\ProductCatalog\CatalogImportOptions` (`fastImport` por defecto `true`).

- productos por `product_id`, SKU y par `category_id + name`
- categorías, marcas, proveedores
- resolución de producto existente: `product_id` → SKU → nombre+categoría (sin cambiar reglas)

Respuesta JSON/HTML incluye métricas adicionales:

| Campo | Significado |
|-------|-------------|
| `rows_total` | Filas parseadas del archivo |
| `duration_ms` | Tiempo total del import en ms |
| `media_count` | Imágenes tocadas en el import |
| `media_conversions_queued` | Jobs WebP encolados (sin conversión síncrona) |

`App\Services\Client\Storefront\ClientStorefrontCache::forgetAfterProductMutation()` se llama **una vez** al final del import, no por fila.

Pendiente: test de importación sintética ≥500 filas en CI (coste de tiempo); hoy cubierto por tests de SKU/nombre+categoría y métricas.

If that background pass is interrupted (container restart, timeout), the scheduler task `cf4:regenerate-missing-media-conversions` runs **once per day at 03:00** and finishes any remaining images — no manual admin step.

## Typical workflow

1. On source environment: **Exportar → Catálogo completo (ZIP + imágenes)**.
2. Copy the `.zip` to the target server.
3. On target: **Importar** → select the ZIP → confirm.
4. Run `php artisan cache:clear` only if storefront still shows stale catalog (normally automatic via `ClientStorefrontCache`).

## Docker

```bash
docker compose exec app_ciclo php artisan test --filter=ProductCatalogImportExportTest
```
