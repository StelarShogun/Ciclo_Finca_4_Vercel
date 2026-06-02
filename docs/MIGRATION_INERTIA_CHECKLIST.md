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
| `/product/{id}/{slug?}` | `ClientPageController@product` | Blade temporal |
| `/legal/terminos` | `ClientLegalController@terms` | Migrada |
| `/legal/privacidad` | `ClientLegalController@privacy` | Blade temporal |
| `/legal/cambios-devoluciones` | `ClientLegalController@returns` | Blade temporal |
| `/contacto` | `ClientLegalController@contact` | Blade temporal |
| `/login` | `ClientUserController` | Blade temporal |
| `/register` | `ClientUserController` | Blade temporal |
| `/verify` | `ClientUserController` | Blade temporal |
| `/recovery*` | `ClientUserController` | Blade temporal |
| `/auth/google*` | `ClientUserController` | Laravel redirect/callback; mantener controller |

## Cliente autenticado

| Ruta | Controller | Estado recomendado |
|---|---|---|
| `/cart` + acciones | `ClientPageController` | Blade temporal; React usa helper `resources/js/lib/cart.ts` para `addToCart` |
| `/products/{product}/review` | `ProductReviewController` | Request/redirect JSON-Inertia según página migrada |
| `/invoices` | `ClientPageController@invoices` | Blade temporal |
| `/invoices/{sale}` | `ClientPageController@showInvoice` | Blade temporal |
| `/invoices/{sale}/print` | `ClientPageController@printInvoice` | Blade permanente |
| `/notifications` | `ClientPageController@notifications` | Blade temporal |
| `/profile` | `ClientUserController@show` | Blade temporal |
| `/favorites` | `FavoriteProductController@index` | Blade temporal |

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

## Backlog de componentes

MVP creado:

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
- `QuantitySelector`
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

1. Detalle de producto + favoritos + reseñas.
2. Carrito + checkout.
3. Auth cliente.
4. Cuenta cliente.
5. Facturas/pedidos.
6. Favoritos y notificaciones.
7. Legal restante.
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
- Siguen en Blade: detalle de producto, carrito, checkout, perfil, favoritos, notificaciones, dashboard admin real y módulos operativos.

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
- Siguen en Blade tras este corte: detalle de producto, carrito, checkout, auth cliente, perfil, facturas/pedidos, favoritos index, notificaciones y legales restantes.

## Criterio por ruta

Una ruta se marca como migrada solo si:

- Renderiza `Inertia::render`.
- Tiene página TSX con props tipadas.
- No depende del entry JS legacy equivalente.
- Mantiene URL y nombre de ruta.
- Pasa build, typecheck y prueba Feature.
- No rompe light/dark mode ni responsive.

## Última validación

- `InertiaMigrationPilotTest`: 7 tests passed.
- `CF4ClientHomeGuestCtaTest`: 2 tests passed.
- `CF4ClientLegalPagesTest`: 3 tests passed.
- `php artisan test --filter=Catalog`: passed, con skips esperados de MySQL.
- `php artisan test`: `221 passed`, `192 skipped`, `952 assertions`.
- `npm run build`: OK.
- `npm run typecheck`: OK.
- `npm run lint:react`: OK, React Doctor `90 / 100`, warnings opcionales.
