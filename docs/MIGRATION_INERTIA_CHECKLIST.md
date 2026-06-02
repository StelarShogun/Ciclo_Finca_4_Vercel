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
| `/legal/terminos` | `clients.legal.terms` | Migrada | `Client/Legal/Terms` |
| `/dashboard/inertia-pilot` | `dashboard.inertia-pilot` | Piloto admin | `Admin/Dashboard/Index` |

## Cliente público

| Ruta | Controller | Estado recomendado |
|---|---|---|
| `/` | `ClientPageController@home` | Migrada |
| `/catalog` | `ClientPageController@catalog` | Blade temporal; migrar como módulo completo |
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
| `/cart` + acciones | `ClientPageController` | Blade temporal; migrar después de catálogo/producto |
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

1. Catálogo completo.
2. Producto + favoritos + reseñas.
3. Carrito + checkout.
4. Auth cliente.
5. Cuenta cliente.
6. Legal restante.
7. Admin shell + dashboard completo.
8. Inventario.
9. Ventas/pedidos.
10. Proveedores/reportes/resto admin.

## Detalle de Home migrada

- Ruta: `/` (`clients.home`).
- Controller: `ClientPageController@home`.
- Página React: `resources/js/Pages/Client/Home/Index.tsx`.
- Props propias: `featuredProducts`, `categories`, `showGuestRegisterCta`, `hero`.
- Props compartidas usadas: `auth.client`, `cartCount`, `csrfToken`, `flash`, `theme`.
- Componentes creados: `HeroSection`, `FeaturedProducts`, `CategoryPreview`, `HomeSection`, `ProductCard`, `ImageFallback`.
- Tipos creados: `resources/js/types/home.ts`.
- Tests: `InertiaMigrationPilotTest`, `CF4ClientHomeGuestCtaTest`, `CF4ClientLegalPagesTest`.
- Siguen en Blade: catálogo, detalle de producto, carrito, checkout, perfil, favoritos, notificaciones, admin real y módulos operativos.

## Criterio por ruta

Una ruta se marca como migrada solo si:

- Renderiza `Inertia::render`.
- Tiene página TSX con props tipadas.
- No depende del entry JS legacy equivalente.
- Mantiene URL y nombre de ruta.
- Pasa build, typecheck y prueba Feature.
- No rompe light/dark mode ni responsive.
