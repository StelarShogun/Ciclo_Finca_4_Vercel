# Backend architecture — pending phases

This document tracks structural work **after** the `migrate-to-ts-and-react` hygiene pass. No behavior change is implied until each item is implemented and tested.

## Client HTTP layer

**Done:** controllers under `App\Http\Controllers\Client\*` — see `docs/APP_STRUCTURE.md`.

## Product detail page

**Done:**

- `App\Actions\Client\Product\BuildProductDetailPage`
- `App\Services\Client\Product\ProductReviewSummaryBuilder`
- `App\Services\Client\Product\RelatedProductFinder`
- `App\Services\Client\Product\ProductDetailPageSupport`

## Admin product controller

**Done:**

- `ProductInventoryController`, `ProductStatusController`, `ProductClassificationFilterController`
- Actions: `DeactivateProduct`, `ActivateProduct`, `ForceDeleteProduct`
- `InventoryClassificationFilterService` for classification filter JSON + inventory chips

## Auth services

`GoogleOAuthService` is stable but large; optional future split:

- `GoogleOAuthState`, `GoogleProfileClientResolver`, `GoogleOAuthClient`

## Client catalog listing

**Done:**

- `App\Actions\Client\Catalog\BuildCatalogPage`
- `App\Services\Client\Catalog\CatalogFilterResolver`, `CatalogQueryBuilder`, `CatalogPayloadBuilder`, `CatalogCategoryNavigationBuilder`, `CatalogSpotlightBuilder`
- `StorefrontController::catalog()` delegates to the action; `BuildsClientCatalogPages` is home-only

**Pending (optional):** `App\Actions\Client\Home\BuildHomePage` if `home()` grows again.

## Support layer

**Done:** `ClientStorefrontCache`, `ClientCategoryIcons`, `ClientPickupPolicy` → `App\Services\Client\Storefront\*` with `@deprecated` Support wrappers.

**Done:** `ProductCatalog/*` → `Services/Admin/ProductCatalog`; `CatalogImportOptions` in `Data/Admin/ProductCatalog`; `CatalogImportContext` deprecated wrapper in Support.

**Pending:** `UnaImport/*` → `Services/Admin/ProductCatalog/Una`; `ProductImageUrls` → `Services/Media`; `ClientFavoriteFormatter` → Favorites or ClientInertia.

## Dev-only Artisan commands

| Signature | Purpose |
|-----------|---------|
| `dev:setup-database` | `migrate:fresh` + `db:seed` (local/testing only) |
| `dev:clean-demo-data` | Remove demo products by explicit name/SKU patterns; requires `--force` or interactive confirm (local/testing only) |

Removed: `app:check-tables`, `db:setup`, `db:clean`.

## Cart persistence

`CartDatabaseStore::save()` uses `upsert()` on `(client_id, product_id)`. Model events are not fired per row (same as prior `updateOrCreate` loop).
