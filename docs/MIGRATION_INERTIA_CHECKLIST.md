# Checklist de Migración Inertia

## Estado general

| Estado | Significado |
|---|---|
| Migrada | Ruta renderiza una página Inertia funcional |
| Piloto | Ruta Inertia paralela para validar arquitectura |
| Blade temporal | Se migrará más adelante |
| Blade permanente | Debe quedarse en Blade/Laravel |
| JSON | Endpoint dinámico existente; no requiere página Inertia |

## Rutas migradas / piloto

| Ruta | Nombre | Estado | Componente |
|---|---|---|---|
| `/` | `clients.home` | Migrada | `Client/Home/Index` |
| `/catalog` | `clients.catalog` | Migrada | `Client/Catalog/Index` |
| `/legal/terminos` | `clients.legal.terms` | Migrada | `Client/Legal/Terms` |
| `/dashboard/inertia-pilot` | `dashboard.inertia-pilot` | Piloto admin | `Admin/Dashboard/Index` |

## Cliente público

| Ruta | Controller | Estado recomendado |
|---|---|---|
| `/` | `Client\StorefrontController@home` | Migrada |
| `/catalog` | `Client\StorefrontController@catalog` | Migrada |
| `/product/{id}/{slug?}` | `Client\ProductPageController` → `BuildProductDetailPage` | Migrada (`Client/Products/Show`) |
| `/legal/terminos` | `Client\LegalController@terms` | Migrada |
| `/legal/privacidad` | `Client\LegalController@privacy` | Migrada (`Client/Legal/Privacy`) |
| `/legal/cambios-devoluciones` | `Client\LegalController@returns` | Migrada (`Client/Legal/Returns`) |
| `/contacto` | `Client\LegalController@contact` | Migrada (`Client/Legal/Contact`) |
| `/login` | `Client\Auth\LoginController` | Migrada (`Client/Auth/Login`) |
| `/register` | `Client\Auth\RegisterController` | Migrada (`Client/Auth/Register`) |
| `/verify` | `Client\Auth\VerificationController` | Migrada (`Client/Auth/VerifyCode`) |
| `/recovery*` | `Client\Auth\PasswordRecoveryController` | Migrada (`Client/Auth/RecoveryRequest`, `Client/Auth/RecoveryReset`) |
| `/auth/google*` | `Client\Auth\GoogleOAuthController` | Laravel redirect/callback |

## Cliente autenticado

| Ruta | Controller | Estado recomendado |
|---|---|---|
| `/cart` | `Client\CartController` | Migrada React (`Client/Cart/Index`); JSON vía Actions |
| `/products/{product}/review` | `Client\ProductReviewController` | JSON/redirect |
| `/invoices` | `Client\InvoiceController@invoices` | Migrada (`Client/Invoices/Index`) |
| `/invoices/{sale}` | `Client\InvoiceController@showInvoice` | Migrada (`Client/Invoices/Show`) |
| `/invoices/{sale}/print` | `Client\InvoiceController@printInvoice` | Blade permanente |
| `/notifications` | `Client\NotificationController` | Migrada (`Client/Notifications/Index`) |
| `/profile` | `Client\ProfileController@show` | Migrada |
| `/favorites` | `Client\FavoriteController@index` | JSON + drawer en `ClientLayout` |

## Admin

| Ruta / módulo | Controller | Estado recomendado |
|---|---|---|
| `/admin/login` | `Admin\Auth\AdminUserController` | Blade temporal |
| `/dashboard` | `Admin\DashboardController@index` | Blade temporal |
| `/dashboard/inertia-pilot` | `Admin\DashboardController@inertiaPilot` | Piloto |
| `/inventory*` | `Admin\Products\ProductInventoryController`, filtros `ProductClassificationFilterController`, `Admin\Inventory\InventoryMovementController` | Blade temporal |
| `/products*` | `Admin\Products\ProductController`, `ProductStatusController`, `ProductVariantController` | Blade temporal |
| `/categories*` | `Admin\Categories\CategoryController` | Blade temporal |
| `/brands*` | `Admin\Brands\BrandController` | Blade temporal |
| `/sales*` | `Admin\Sales\SalesController` | Blade temporal |
| `/orders*` | `Admin\Orders\OrderController`, settings controllers | Blade temporal |
| `/suppliers*` | `Admin\Suppliers\SupplierController` | Blade temporal |
| `/supplier-orders*` | `Admin\Suppliers\SupplierOrderController`, `XmlPriceDeviationController` | Blade temporal |
| `/reports*` | `Admin\Reports\*` | Blade temporal |
| `/clientes*` | `Admin\Clients\ClientController` | Blade temporal |
| `/classifications*` | `Admin\Classifications\ClassificationCatalogController` | Blade temporal |

## JSON / endpoints dinámicos

| Ruta | Estado |
|---|---|
| `/api/catalog/heartbeat` | JSON existente |
| `/api/products/suggestions` | JSON existente |
| `/api/catalog/search-trending` | JSON existente |
| `/dashboard/data` | JSON existente |
| `/dashboard/chart-data` | JSON existente |
| `/reports/*/table`, metrics, range | JSON/HTML parcial existente; revisar por módulo |
| `/invoices/heartbeat`, `/notifications/heartbeat` | JSON existente |
| `/csrf-token` | JSON existente |

## Blade permanente

| Tipo | Rutas / vistas |
|---|---|
| Emails | `resources/views/emails/*` |
| PDF admin | reportes PDF, `products-pdf`, dashboard PDF |
| Print cliente/admin | `invoice-print`, `sales/print`, facturas imprimibles |
| Exports binarios | Excel/CSV/PDF por controllers |

## Arquitectura frontend (2026-06)

- **Pages/**: solo entradas Inertia delgadas (`export { default } from '@/features/...'`).
- **features/client/**: módulos por dominio (`home`, `catalog`, `product`, `cart`, …).
- **shared/**: UI, layouts, hooks y libs transversales.
- **Re-exports temporales** en `Components/`, `Layouts/`, `hooks/`, `lib/`, `types/` — retirar cuando no queden imports legacy.
- **Pendiente naming**: `Invoices` vs `Orders` en rutas/copy (no renombrado en esta corrida).

## Organización Blade (2026-06-02)

- Vistas de categorías admin movidas a `resources/views/admin/categories/{parents,subcategories}`.
- Layout de errores movido a `resources/views/errors/layouts/error.blade.php`; `errors/*` sigue como carpeta propia.
- Componentes renombrados:
  - `x-admin.admin-alert`
  - `x-shared.file-upload`
  - `x-shared.state-card`
  - `x-shared.pagination`
- Wrappers temporales `@deprecated` conservan compatibilidad con:
  - `x-admin-alert`
  - `x-cf-file-upload`
  - `x-cf4.state-card`
  - `x-pagination`
- `resources/views/vendor/pagination` se mantiene intacto.
- No movidos: `resources/views/emails/*` por ser vistas transaccionales y `resources/views/app.blade.php` por ser el root view de Inertia.

## Backlog de componentes

MVP creado (canónicos en `shared/` o `features/`):

- `Button`
- `Input`
- `FormError`
- `FieldGroup`
- `Badge`
- `PageHeader`
- `LoadingState`
- `EmptyState`
- `Pagination`
- `HeroSection`
- `HomeSection`
- `FeaturedProducts`
- `CategoryPreview`
- `ProductCard`
- `ImageFallback`
- `CatalogFilters`
- `CategoryRail`
- `CatalogProductCard`
- `CatalogPagination`
- `CartItemRow`
- `CartQuantitySelector`
- `CartSummary`
- `CartEmptyState`
- `CartCheckoutActions`

Pendiente:

- `Select`
- `Textarea`
- `Checkbox`
- `StatusBadge`
- `QuantitySelector` (product feature — hecho)
- `Modal`
- `ConfirmDialog`
- `AdminTable`
- `DataToolbar`
- `PerPageSelect`
- `SearchInput`
- `FilterPanel`
- `StatCard`
- `Drawer`
- `Tabs`

## Orden de siguientes PRs

1. ~~Detalle de producto + favoritos + reseñas.~~ (Inertia + refactor feature `product`)
2. ~~Carrito~~ (React puro; `bundles/cart.js` queda sólo para Blade residual)
3. ~~Auth cliente.~~
4. ~~Cuenta cliente.~~
5. ~~Facturas/pedidos (listado/detalle).~~ — miniaturas en detalle Inertia pendientes
6. ~~Favoritos y notificaciones.~~ — drawer aún legacy JS
7. ~~Legal restante.~~
8. Admin shell + dashboard completo.
9. Inventario.
10. Ventas/proveedores/reportes/resto admin.

## Detalle de Home migrada

- Ruta: `/` (`clients.home`).
- Controller: `Client\StorefrontController@home`.
- Página React: `resources/js/Pages/Client/Home/Index.tsx`.
- Props propias: `featuredProducts`, `categories`, `showGuestRegisterCta`, `hero`.
- Props compartidas usadas: `auth.client`, `cartCount`, `csrfToken`, `flash`, `theme`.
- Componentes creados: `HeroSection`, `FeaturedProducts`, `CategoryPreview`, `HomeSection`, `ProductCard`, `ImageFallback`.
- Tipos creados: `resources/js/types/home.ts`.
- Helper creado: `resources/js/lib/cart.ts` para encapsular el POST legacy `/cart/add`.
- CSS: `app.tsx` queda liviano; CSS cliente/admin se carga desde layouts/páginas.
- Tests: `InertiaMigrationPilotTest`, `CF4ClientHomeGuestCtaTest`, `CF4ClientLegalPagesTest`.
- Siguen en Blade: checkout flow completo, dashboard admin real y módulos operativos admin.

## Detalle de Catálogo migrado

- Ruta: `/catalog` (`clients.catalog`).
- Controller: `Client\StorefrontController@catalog`.
- Página React: `resources/js/Pages/Client/Catalog/Index.tsx`.
- Props propias: `products`, `pagination`, `categories`, `brands`, `filters`, `selectedCategory`, `subcategories`, `parentCategoryForSubcats`, `catalogSpotlight`, `favoriteProductIds`, `emptyCategoryNoProducts`, `catalogVersion`, `summary`.
- Props compartidas usadas: `auth.client`, `cartCount`, `csrfToken`, `flash`, `theme`.
- Componentes creados: `CatalogFilters`, `CategoryRail`, `CatalogProductCard`, `CatalogPagination`.
- Tipos creados: `resources/js/types/catalog.ts`.
- Helper creado: `resources/js/lib/favorites.ts` para `POST /favorites/toggle`.
- Reutiliza `resources/js/lib/cart.ts` para `POST /cart/add`.
- Mantiene endpoints JSON legacy: `/api/catalog/heartbeat`, `/api/products/suggestions`, `/api/catalog/search-trending`.
- Legacy JS temporal: `bundles/catalog.js`, `bundles/product.js`, `bundles/cart.js` (ver tabla en `FRONTEND_INERTIA_REACT_TS.md`).

## Criterio por ruta

Una ruta se marca como migrada solo si:

- Renderiza `Inertia::render`.
- Tiene página TSX con props tipadas.
- No depende del entry JS legacy equivalente.
- Mantiene URL y nombre de ruta.
- Pasa build, typecheck y prueba Feature.
- No rompe light/dark mode ni responsive.

## Última validación

- `php artisan test`: **228 passed**, 192 skipped (incl. tests de display migrados a `assertInertia`).
- `npm run build` / `npm run typecheck`: OK.
- `npm run lint:react`: OK, React Doctor **82 / 100**.
- Producto: `Client/Products/Show`, builder con `ProductDetailPayloadContext`.
