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

## Dev-only Artisan commands

| Signature | Purpose |
|-----------|---------|
| `dev:setup-database` | `migrate:fresh` + `db:seed` (local/testing only) |
| `dev:clean-demo-data` | Remove demo products by name patterns (local/testing only) |

Removed: `app:check-tables`, `db:setup`, `db:clean`.

## Cart persistence

`CartDatabaseStore::save()` uses `upsert()` on `(client_id, product_id)`. Model events are not fired per row (same as prior `updateOrCreate` loop).
