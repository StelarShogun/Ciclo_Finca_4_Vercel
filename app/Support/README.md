# Support layer audit

Classes under `app/Support` are small, stateless helpers or **deprecated facades** over `Services` / `Data`. Prefer **Actions** for use cases and **Services** for reusable domain or integration logic. Do not grow Support into a second Services tree.

## Keep in Support (transversal / admin conventions)

| Class | Role |
|-------|------|
| `AdminPerPage`, `AdminPaginationPreserver`, `AdminDateRange` | Admin list/query conventions |
| `GdImage`, `DeferAfterResponse`, `SchedulerMonitor`, `DashboardTodaySales` | Cross-cutting utilities |
| `ProductImageUrls` | Canonical product image URL resolution. High fan-out — move to `Services/Media` in one pass when ready |
| `ClientInertia/*` | Inertia payload builders for storefront migration |
| `Client/Auth/GoogleProfileNameParser` | OAuth name parsing |

## Moved to Services (Support wrappers remain `@deprecated`)

| Implementation | Wrapper (temporary) |
|----------------|---------------------|
| `App\Services\Client\Storefront\ClientStorefrontCache` | `App\Support\ClientStorefrontCache` |
| `App\Services\Client\Storefront\ClientCategoryIcons` | `App\Support\ClientCategoryIcons` |
| `App\Services\Client\Storefront\ClientPickupPolicy` | `App\Support\ClientPickupPolicy` |

New code should import the `Services\Client\Storefront\*` classes directly.

## Admin product catalog import/export

| Location | Role |
|----------|------|
| `App\Services\Admin\ProductCatalog\*` | Importer, exporter, parser, field mapper |
| `App\Data\Admin\ProductCatalog\CatalogImportOptions` | Import options (`fastImport`) |
| `App\Services\Admin\ProductCatalog\CatalogImportState` | Scoped fast-import flag (media conversions) |
| `App\Support\ProductCatalog\CatalogImportContext` | `@deprecated` — delegates to options/state |

## Planned move to Services (still in Support)

| Namespace | Target |
|-----------|--------|
| `UnaImport/*` | `App\Services\Admin\ProductCatalog\Una/*` (UNA supplier import) |
| `ClientFavoriteFormatter` | `App\Services\Client\Favorites\ClientFavoriteFormatter` or Inertia payload builder |

## Client catalog (no longer in controllers)

Listing logic lives in:

- `App\Actions\Client\Catalog\BuildCatalogPage`
- `App\Services\Client\Catalog\CatalogFilterResolver`
- `App\Services\Client\Catalog\CatalogQueryBuilder`
- `App\Services\Client\Catalog\CatalogPayloadBuilder`
- `App\Services\Client\Catalog\CatalogCategoryNavigationBuilder`
- `App\Services\Client\Catalog\CatalogSpotlightBuilder`

`BuildsClientCatalogPages` trait: **home-only** helpers.

## Temporary / transitional

| Class | Notes |
|-------|--------|
| `CartService` (in `app/Services`) | Deprecated facade over `CartManager` + stores |

See `docs/APP_STRUCTURE.md` and `docs/BACKEND_ARCHITECTURE_PENDING.md`.
