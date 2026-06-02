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
| `/` | `ClientPageController@home` | Migrada |
| `/catalog` | `ClientPageController@catalog` | Migrada |
| `/product/{id}/{slug?}` | `ClientPageController@product` | Migrada (`Client/Products/Show`) |
| `/legal/terminos` | `ClientLegalController@terms` | Migrada |
| `/legal/privacidad` | `ClientLegalController@privacy` | Migrada (`Client/Legal/Privacy`) |
| `/legal/cambios-devoluciones` | `ClientLegalController@returns` | Migrada (`Client/Legal/Returns`) |
| `/contacto` | `ClientLegalController@contact` | Migrada (`Client/Legal/Contact`) |
| `/login` | `ClientUserController` | Migrada (`Client/Auth/Login`) |
| `/register` | `ClientUserController` | Migrada (`Client/Auth/Register`) |
| `/verify` | `ClientUserController` | Migrada (`Client/Auth/VerifyCode`) |
| `/recovery*` | `ClientUserController` | Migrada (`Client/Auth/RecoveryRequest`, `Client/Auth/RecoveryReset`) |
| `/auth/google*` | `ClientUserController` | Laravel redirect/callback; mantener controller |

## Cliente autenticado

| Ruta | Controller | Estado recomendado |
|---|---|---|
| `/cart` | `ClientPageController@cart` | Migrada (`Client/Cart/Index`); acciones JSON siguen en controller |
| `/products/{product}/review` | `ProductReviewController` | Request/redirect JSON-Inertia según página migrada |
| `/invoices` | `ClientPageController@invoices` | Migrada (`Client/Invoices/Index`) |
| `/invoices/{sale}` | `ClientPageController@showInvoice` | Migrada (`Client/Invoices/Show`; sin thumbs de imagen en React — print sigue Blade) |
| `/invoices/{sale}/print` | `ClientPageController@printInvoice` | Blade permanente |
| `/notifications` | `ClientPageController@notifications` | Migrada (`Client/Notifications/Index`) |
| `/profile` | `ClientUserController@show` | Migrada |
| `/favorites` | `FavoriteProductController@index` | JSON + drawer en `ClientLayout` (Inertia) |

## Admin

| Ruta / módulo | Controller | Estado recomendado |
|---|---|---|
| `/admin/login` | `AdminUserController` | Blade temporal |
| `/dashboard` | `DashboardController@index` | Blade temporal; mantener hasta migrar dashboard completo |
| `/dashboard/inertia-pilot` | `DashboardController@inertiaPilot` | Piloto |
| `/inventory*` | `ProductController`, `InventoryMovementController` | Blade temporal |
| `/products*` | `ProductController`, `ProductVariantController` | Blade temporal |
| `/categories*` | `CategoryController` | Blade temporal |
| `/brands*` | `BrandController` | Blade temporal |
| `/sales*` | `SalesController` | Blade temporal |
| `/orders*` | `AdminOrderController`, settings controllers | Blade temporal |
| `/suppliers*` | `SupplierController` | Blade temporal |
| `/supplier-orders*` | `SupplierOrderController`, `XmlPriceDeviationController` | Blade temporal |
| `/reports*` | Reports controllers | Blade temporal |
| `/clientes*` | `AdminClientController` | Blade temporal |
| `/classifications*` | Classification controllers | Blade temporal |

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
2. ~~Carrito~~ (página Inertia; reducir `bundles/cart.js`)
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
- Controller: `ClientPageController@home`.
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
- Controller: `ClientPageController@catalog`.
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
