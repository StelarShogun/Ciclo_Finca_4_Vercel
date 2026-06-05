# Support layer

Stateless cross-cutting helpers only. **Not** a second Services tree.

## Current contents

| Class | Role |
|-------|------|
| `AdminPerPage`, `AdminPaginationPreserver`, `AdminDateRange` | Admin list/query conventions |
| `GdImage`, `DeferAfterResponse`, `SchedulerMonitor`, `DashboardTodaySales` | Utilities |
| `Client/Auth/GoogleProfileNameParser` | Google OAuth name parsing |

## Moved to Services (2026-06)

| Was | Now |
|-----|-----|
| `Support\ProductImageUrls` | `Services\Media\ProductImageUrls` |
| `Support\ClientFavoriteFormatter` | `Services\Client\Favorites\ClientFavoriteFormatter` |
| `Support\UnaImport\*` | `Services\Admin\ProductCatalog\Una\*` |

## Moved out of Support (clean core)

| Was | Now |
|-----|-----|
| `Support\ClientStorefrontCache` (wrapper) | `Services\Client\Storefront\ClientStorefrontCache` |
| `Support\ClientCategoryIcons` (wrapper) | `Services\Client\Storefront\ClientCategoryIcons` |
| `Support\ClientPickupPolicy` (wrapper) | `Services\Client\Storefront\ClientPickupPolicy` |
| `Support\ClientInertia/*` | `Services\Client\Inertia/*` |
| `Support\ProductCatalog\CatalogImportContext` | Removed — use `CatalogImportOptions` + `CatalogImportState` |
| `Services\CartService` | Removed — use `CartDatabaseStore` / `CartManager` |

## Client catalog (reference)

- `Actions\Client\Catalog\BuildCatalogPage`
- `Services\Client\Catalog\*`
- `Services\Client\Inertia\*` — Inertia payload builders

See `docs/APP_STRUCTURE.md`.
