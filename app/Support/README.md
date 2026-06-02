# Support layer audit

Classes under `app/Support` are small, stateless helpers. Prefer **Actions** for use cases and **Services** for reusable domain or integration logic. Do not grow Support into a second Services tree.

## Definitive (keep here or move only with a deliberate refactor)

| Class / namespace | Role |
|-------------------|------|
| `ProductImageUrls` | Canonical product image URL resolution (card, thumb, gallery). High fan-out; move to `Services/Media` only when imports are updated in one pass. |
| `ClientFavoriteFormatter` | Maps favorite rows to API/Inertia shapes. |
| `ProductCatalog/*` | Import/export pipeline (`CatalogImportContext`, parsers, exporter, importer, field mapper). |
| `ClientInertia/*` | Inertia payload builders and pagination DTOs for the storefront migration. |
| `AdminPerPage`, `AdminPaginationPreserver`, `AdminDateRange` | Admin list/query conventions. |
| `ClientPickupPolicy`, `ClientStorefrontCache`, `ClientCategoryIcons` | Storefront policy and cache keys. |
| `Client/Auth/GoogleProfileNameParser` | OAuth name parsing. |
| `GdImage`, `DeferAfterResponse`, `SchedulerMonitor`, `DashboardTodaySales` | Cross-cutting utilities. |
| `UnaImport/*` | UNA catalog import adapters. |

## Temporary / transitional

| Class | Notes |
|-------|--------|
| `CartService` (in `app/Services`) | Deprecated facade over `CartManager` + stores; remove when all callers use Actions/Services directly. |

## Planned extractions (not in Support)

Controller and product-detail use cases are documented in `docs/APP_STRUCTURE.md` and `docs/BACKEND_ARCHITECTURE_PENDING.md`.
- Optional split of `GoogleOAuthService` into smaller auth services.

See also `docs/BACKEND_ARCHITECTURE_PENDING.md` for controller and command conventions.
