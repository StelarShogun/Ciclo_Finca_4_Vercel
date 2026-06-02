# `app/` layout (admin vs client)

HTTP controllers are grouped by **audience** (`Admin`, `Client`) and **domain** (Products, Orders, Auth, …). Routes and named routes are unchanged; only PHP namespaces moved.

## `app/Http/Controllers`

```
Controller.php                    # Base controller

Admin/
  Auth/AdminUserController.php
  DashboardController.php
  Brands/BrandController.php
  Categories/CategoryController.php
  Classifications/ClassificationCatalogController.php
  Clients/ClientController.php
  Inventory/InventoryMovementController.php
  Orders/OrderController.php
  Orders/OrderSettingsController.php
  Orders/WeeklyReportSettingsController.php
  Products/ProductController.php              # CRUD JSON + show; index delegates to inventory
  Products/ProductInventoryController.php
  Products/ProductStatusController.php        # activate, deactivate, force-delete
  Products/ProductClassificationFilterController.php
  Products/ProductClassificationController.php
  Products/ProductVariantController.php
  Products/ProductGalleryController.php
  Products/ProductManualStockController.php
  Products/ProductCatalogImportController.php
  Reports/ReportsController.php
  Reports/ReportsRegistryExportController.php
  Reports/AuditLogController.php
  Reports/ClientPurchaseHistoryController.php
  Sales/SalesController.php
  Suppliers/SupplierController.php
  Suppliers/SupplierOrderController.php
  Suppliers/XmlPriceDeviationController.php

Client/
  Concerns/BuildsClientCatalogPages.php   # Shared catalog/home payload helpers
  StorefrontController.php                # home, catalog, heartbeat
  ProductPageController.php                 # delegates to BuildProductDetailPage
  CartController.php
  InvoiceController.php
  NotificationController.php
  LegalController.php
  FavoriteController.php
  ProductReviewController.php
  ProfileController.php
  Catalog/ProductSuggestionsController.php
  Catalog/SearchTrendingController.php
  Auth/LoginController.php
  Auth/RegisterController.php
  Auth/VerificationController.php
  Auth/PasswordRecoveryController.php
  Auth/GoogleOAuthController.php
```

## `app/Http/Requests`

- `Client/Auth/*`, `Client/Profile/*` — storefront auth and profile
- `Admin/Products/*` — product CRUD, import, classifications assignment
- `Admin/Classifications/*` — classification catalog CRUD
- `Admin/Reports/*` — report filters
- `Admin/Clients/*` — admin client purchase history tables

## Other layers (unchanged convention)

| Layer | Location |
|-------|----------|
| Use cases | `app/Actions/Admin/*`, `app/Actions/Client/*` (e.g. `Client/Product/BuildProductDetailPage`, `Admin/Products/ActivateProduct`) |
| Domain / integrations | `app/Services/Admin/*`, `app/Services/Client/*`, `app/Services/Security/*` |
| DTOs | `app/Data/*` |
| Inertia payloads | `app/Support/ClientInertia/*` |
| Storefront cache / icons / pickup copy | `app/Services/Client/Storefront/*` (Support wrappers deprecated) |
| Client catalog page | `app/Actions/Client/Catalog/BuildCatalogPage` + `app/Services/Client/Catalog/*` |
| Catalog import/export | `app/Services/Admin/ProductCatalog/*`, `app/Data/Admin/ProductCatalog/CatalogImportOptions` |

See `app/Support/README.md` and `docs/BACKEND_ARCHITECTURE_PENDING.md` for follow-up extractions (`BuildProductDetailPage`, further admin product splits).
