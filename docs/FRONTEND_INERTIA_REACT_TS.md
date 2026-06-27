# Frontend Inertia + React + TypeScript

Esta guía documenta la migración progresiva de Ciclo Finca 4 desde Blade + JavaScript modular hacia Laravel + Inertia.js + React + TypeScript.

## Arquitectura

- Laravel sigue siendo el router principal y la fuente de verdad para reglas de negocio, validación, permisos, sesiones, CSRF, queries y persistencia.
- Inertia se usa solo en rutas migradas mediante `Inertia::render(...)`.
- Blade convive con Inertia durante la migración.
- Emails, PDF, vistas imprimibles y exports binarios permanecen en Blade/Laravel.
- React se encarga de presentación, estado de UI, formularios y componentes.

## Arquitectura frontend (por características + shared)

Inertia resuelve páginas solo desde `resources/ts/Pages`. Esas páginas deben ser **delgadas** (re-export del módulo real):

```tsx
// resources/ts/Pages/Client/Products/Show.tsx
export { default } from '@/features/client/product/pages/ProductShowPage';
```

### Reglas de ubicación

| Ubicación | Cuándo |
|---|---|
| `features/client/{feature}/` | Lógica, componentes y hooks de un solo módulo (home, catalog, product, cart, auth, profile, invoices, favorites, notifications, legal) |
| `shared/` | UI reutilizable, layouts, hooks globales, helpers genéricos, tipos compartidos |
| `Pages/` | Solo entrada Inertia (re-exports) |
| `Layouts/`, `Components/`, `hooks/`, `lib/`, `types/` (raíz) | **Re-exports temporales** `@deprecated` hacia `shared/` o `features/` — eliminar cuando no queden imports |

### Estructura aplicada

```txt
resources/ts/
  app.tsx
  bootstrap.ts
  **/*.ts                   # legacy admin/shared/client (sin .js en resources/ts)
  Pages/Client/...          # capa delgada Inertia
  features/client/
    home|catalog|product|cart|auth|profile|invoices|favorites|notifications|legal/
      components/
      hooks/
      types.ts
      pages/               # product: ProductShowPage.tsx
  shared/
    components/ui/          # Button, ToastProvider, ImageFallback, …
    components/layout/      # ClientLayout, ClientAuthLayout
    components/client/      # header/*, footer/*
    hooks/                  # useToast, useFlashToasts
    lib/                    # parseJsonResponse, confirm, inertiaErrors
    types/                  # models.ts, inertia.d.ts
```

`FavoritesDrawer` vive en `features/client/favorites/components/` porque es específico del flujo de favoritos del cliente, aunque se monte desde `ClientLayout`.

### Pendiente de naming

- Rutas y dominio siguen usando **Invoices** (`/invoices`, `Client/Invoices/*`). Renombrar a **Orders** en UI/estructura sería un cambio transversal (rutas, copy, tests, admin); documentado como pendiente, no aplicado en esta corrida.

## Archivos base

- Root Inertia: `resources/views/app.blade.php`
- Entrypoint React: `resources/ts/app.tsx`
- Bootstrap TS/axios: `resources/ts/bootstrap.ts`
- Middleware compartido: `app/Http/Middleware/HandleInertiaRequests.php`
- Tipos compartidos: `resources/ts/shared/types/`; tipos de página en `resources/ts/types/` (catalog, home, invoices, …)
- Layouts canónicos: `resources/ts/shared/components/layout/`
- UI compartida: `resources/ts/shared/components/ui/`
- Módulos: `resources/ts/features/client/*`
- API de dominio: `features/client/{cart,favorites}/api.ts`

## Props compartidas

`HandleInertiaRequests` expone:

- `auth.client`
- `auth.admin`
- `cartCount`
- `csrfToken`
- `flash`
- `theme`

No se envían passwords, tokens persistentes ni modelos completos.

## Rutas migradas (cliente)

| Ruta | Nombre | Controller | Página React |
|---|---|---|---|
| `/` | `clients.home` | `Client\StorefrontController@home` | `Client/Home/Index` |
| `/catalog` | `clients.catalog` | `Client\StorefrontController@catalog` | `Client/Catalog/Index` |
| `/legal/terminos` | `clients.legal.terms` | `Client\LegalController@terms` | `Client/Legal/Terms` |
| `/legal/privacidad` | `clients.legal.privacy` | `Client\LegalController@privacy` | `Client/Legal/Privacy` |
| `/legal/cambios-devoluciones` | `clients.legal.returns` | `Client\LegalController@returns` | `Client/Legal/Returns` |
| `/contacto` | `clients.contact` | `Client\LegalController@contact` | `Client/Legal/Contact` |
| `/product/{id}/{slug?}` | `clients.product` | `Client\ProductPageController` → `BuildProductDetailPage` | `Client/Products/Show` |
| `/cart` | `clients.cart` | `Client\CartController` | `Client/Cart/Index` |
| `/login`, `/register`, `/verify`, `/recovery*` | `Client\Auth\*` | Auth pages bajo `Client/Auth/*` |
| `/profile` | `clients.profile` | `Client\ProfileController@show` | `Client/Profile/Index` |
| `/invoices`, `/invoices/{sale}` | `Client\InvoiceController` | `Client/Invoices/Index`, `Client/Invoices/Show` |
| `/notifications` | `clients.notifications` | `Client\NotificationController` | `Client/Notifications/Index` |
| `/favorites` (JSON) | `Client\FavoriteController` | Drawer en layout |

### Home cliente

La Home cliente conserva la URL `/` y el nombre de ruta `clients.home`.

Props enviadas por `Client\StorefrontController@home`:

- `featuredProducts`: productos destacados serializados para tarjetas de Home.
- `categories`: categorías raíz con subcategorías e iconos.
- `showGuestRegisterCta`: controla CTAs para invitados.
- `hero`: textos del hero.

Props compartidas usadas por layout/componentes:

- `auth.client`
- `cartCount`
- `csrfToken`
- `flash`
- `theme`

Componentes creados para Home:

- `HeroSection`
- `FeaturedProducts`
- `CategoryPreview`
- `HomeSection`
- `ProductCard`
- `ImageFallback` → canónico en `shared/components/ui/ImageFallback.tsx`

Tipos específicos:

- `resources/ts/types/home.ts`

La ruta `/` usa Inertia (`Client/Home/Index`); no quedan vistas Blade de storefront.

La Home usa `Link` de Inertia para navegación interna y `usePage().url` desde `ClientLayout` para estado activo de menú. Esto evita depender de `window.location.pathname` y mantiene la base lista para una futura estrategia SSR si se habilita.

### Catálogo cliente

El catálogo cliente conserva la URL `/catalog` y el nombre de ruta `clients.catalog`.

Props enviadas por `Client\StorefrontController@catalog`:

- `products`: productos paginados serializados para cards.
- `pagination`: metadatos y links de paginación.
- `categories`: árbol de categorías raíz e hijas con URLs.
- `brands`: marcas disponibles para filtros.
- `filters`: búsqueda, categoría, marca, rango de precio, orden y `perPage`.
- `catalogSpotlight`: destacados/novedades.
- `favoriteProductIds`: ids favoritos del cliente autenticado; vacío para invitados.
- `summary`: totales y cantidad de filtros activos.

Componentes creados para catálogo:

- `CatalogFilters`
- `CategoryRail`
- `CatalogProductCard`
- `CatalogPagination`

Tipos específicos:

- `resources/ts/types/catalog.ts`

Patrón de filtros: `CatalogFilters` usa `router.get('/catalog', params, { preserveScroll: true })`, conservando query string y dejando Laravel como fuente de verdad para validaciones de precio, filtros, orden y paginación.

### Detalle de producto

- Página Inertia: `Client/Products/Show` → `features/client/product/pages/ProductShowPage.tsx`.
- Interacciones en React: cantidad + subtotal (`QuantitySelector`), carrito (`features/client/cart/api`), favoritos (`features/client/favorites/api`), carrusel en `ProductGallery`.
- Relacionados: `RelatedProductCard` usa los mismos helpers TS (sin delegación legacy).
- Payload Laravel: `ProductDetailPayloadBuilder::build(ProductDetailPayloadContext $context)`.
- `ImageFallback` canónico: `shared/components/ui/ImageFallback.tsx` (eliminado duplicado en `features/client/home`).

### Carrito React puro

- `Pages/Client/Cart/Index.tsx` re-exporta `features/client/cart/pages/CartIndexPage.tsx`.
- Las acciones principales ya corren en React/TypeScript: actualizar cantidad, eliminar línea, vaciar carrito y checkout.
- Componentes canónicos:
  - `CartItemRow`
  - `CartQuantitySelector`
  - `CartSummary`
  - `CartEmptyState`
  - `CartCheckoutActions`
- Hook de acciones: `features/client/cart/hooks/useCartActions.ts`.
- API TS: `features/client/cart/api.ts`.
- Payload Inertia del carrito usa camelCase (`productId`, `unitPrice`, `stockCurrent`, `image.usesPlaceholder`, etc.) y normaliza snake_case sólo como compatibilidad al entrar a React.
- Laravel sigue validando stock y checkout; React sólo previene cantidades obviamente inválidas.
- Después de cada acción React actualiza estado local y ejecuta `router.reload({ only: [...] })` para resincronizar con Laravel.

### Vistas Blade reorganizadas (2026-06-02)

- Categorías admin: `resources/views/admin/categories/{parents,subcategories}/create.blade.php`.
- Layout de errores: `resources/views/errors/layouts/error.blade.php`.
- Componentes movidos:
  - `x-admin.admin-alert` → `resources/views/components/admin/admin-alert.blade.php`.
  - `x-shared.file-upload` → `resources/views/components/shared/file-upload.blade.php`.
  - `x-shared.state-card` → `resources/views/components/shared/state-card.blade.php`.
  - `x-shared.pagination` → `resources/views/components/shared/pagination.blade.php`.
- Wrappers temporales `@deprecated` conservados:
  - `x-admin-alert`
  - `x-cf-file-upload`
  - `x-cf4.state-card`
  - `x-pagination`
- `resources/views/vendor/pagination` y `resources/views/errors` permanecen como carpetas propias.
- `resources/views/emails/*` y `resources/views/app.blade.php` no se movieron: emails son canal transaccional Blade separado y `app.blade.php` es root view de Inertia.

### Re-exports eliminados (2026-06)

Se quitaron shims `@deprecated` ya sin consumidores: `Components/{Home,Catalog,UI,Product,Client}`, `Layouts/ClientLayout`, `Layouts/ClientAuthLayout`, `hooks/useToast`, `hooks/useFlashToasts`, `hooks/useCatalogPageInit`, `hooks/useProductPageInit`. Las páginas importan desde `@/shared` y `@/features`.

## JS legacy en páginas Inertia (clasificación, post JS→TS)

| Asset / hook | Clasificación | Notas |
|---|---|---|
| `useCatalogPageInit` → `bundles/catalog.ts`, `clients-catalog-heartbeat.ts` | **temporal** | Rail, flyouts, filtros móviles, spotlight Swiper |
| Detalle Inertia (`Client/Products/Show`) | **React puro** | `QuantitySelector`, `addToCart`/`toggleFavorite` TS |
| Carrito Inertia (`Client/Cart/Index`) | **React puro** | `useCartActions`, API TS |
| ~~`FavoritesDrawer` → `clients-header-auth.ts`~~ | **eliminado** | Drawer favoritos 100% React (`FavoritesDrawerContext`, `fetchFavoriteDrawerPage`) |
| ~~`catalog-product-favorites.ts`~~ | **eliminado** | Cards usan `features/client/favorites/api.ts` |
| Blade storefront pages | **eliminadas** | Solo `client/invoice-print.blade.php` para impresión |
| `invoices-page.ts` | **A. Blade activa** | Solo `invoice-print.blade.php` |
| `checkout-copy.ts`, `clients-users.css` | **shared TS/CSS** | Importado por bundles legacy + estilos |
| Flash cliente | **Inertia** | `ToastProvider` + `useFlashToasts` |

## API TS de carrito

La API canónica está en:

```txt
resources/ts/features/client/cart/api.ts
```

Funciones disponibles:

- `addToCart(productId, quantity, csrfToken)`
- `updateCartItem(productId, quantity, csrfToken)`
- `removeCartItem(productId, csrfToken)`
- `clearCart(csrfToken)`
- `checkoutCart(csrfToken, paymentMethod)`

Cada función usa `fetch`, envía CSRF, valida `content-type`, maneja 419 con mensaje claro, tolera respuestas no JSON y normaliza `cart_count` a `cartCount`.

## Favoritos desde React

```txt
features/client/favorites/api.ts
```

`toggleFavorite(productId, csrfToken)` encapsula `POST /favorites/toggle` y normaliza `is_favorite` a `isFavorite`.

## Cómo crear una página Inertia

1. Crear el componente en `resources/ts/Pages/...`.
2. En el controller, cambiar solo la ruta migrada a `Inertia::render(...)`.
3. Enviar props serializables y pequeñas.
4. Mantener validaciones en Form Requests o controller.
5. Usar `Link`, `Head`, `router` y `useForm` de `@inertiajs/react`.

Ejemplo:

```php
return Inertia::render('Client/Legal/Terms', [
    'legalTitle' => 'Términos y condiciones',
]);
```

## Formularios

Usar `useForm` de Inertia para:

- Login/registro
- Perfil
- Carrito/checkout cuando la ruta ya esté migrada
- CRUD admin migrado

Laravel mantiene la validación; React muestra errores desde `form.errors`.

## Layouts

- `shared/components/layout/ClientLayout.tsx`: composición de `ClientHeader`, `FavoritesDrawer`, `main`, `ClientFooter`.
- `shared/components/layout/ClientAuthLayout.tsx`: auth cliente sin shell completo.
- `AdminLayout`: shell admin piloto (`resources/ts/Layouts/AdminLayout.tsx`); **no migrar admin** en esta fase.
- Re-export: `@/Layouts/ClientLayout` → shared.

Los layouts importan CSS cliente existente; no introducen Tailwind como sistema visual.

El dashboard admin Inertia sigue siendo piloto (`/dashboard/inertia-pilot`) y no reemplaza el dashboard real `/dashboard`.

## CSS y dark mode

El sistema visual sigue usando:

- `resources/css/client/variables-reset.css`
- `resources/css/client/header.css`
- `resources/css/client/footer.css`
- `resources/css/client/clients-home.css`
- `resources/css/client/clients-page.css`
- `resources/css/admin/shell-base.css`
- `resources/css/admin/dashboard/dashboard.css`

`resources/ts/app.tsx` se mantiene como entrypoint liviano. El CSS cliente se importa desde `ClientLayout` y páginas cliente específicas; el CSS admin se importa desde `AdminLayout`. Esto evita mezclar CSS admin en pantallas cliente durante la migración progresiva.

No usar CSS-in-JS ni reescribir tokens globales en esta fase.

## Legacy JS → TypeScript (2026-06)

Corrida agresiva documentada en `docs/LEGACY_JS_TO_TS_MIGRATION.md`:

- `resources/ts/**/*.ts` eliminado o convertido a `.ts`.
- Entradas Vite cliente reducidas a `invoices-page.ts` (Blade print) + CSS; storefront vía `app.tsx`.
- Bundles `clients-*.js` eliminados donde la ruta ya es Inertia; catálogo conserva `bundles/catalog.ts` vía `import()` dinámico.

## Comandos

```bash
npm run build
npm run typecheck
python3 scripts/audit-vite-blade-assets.py
npm run lint:react
docker exec laravel_app_ciclo php artisan test --filter=InertiaMigrationPilotTest
docker exec laravel_app_ciclo php artisan test --filter=CF4ClientLegalPagesTest
docker exec laravel_app_ciclo php artisan test
```

Documentación restaurada desde `main` (no eliminada sin motivo): `docs/CATALOG_IMPORT_EXPORT.md`, `docs/CRON_RENDER_LARAVEL.md`. `CF4-146-PR-BODY.md` era artefacto local de PR y no se restauró.

Última validación (producto React + importador):

- `docker exec laravel_app_ciclo php artisan test`: **228 passed**, 195 skipped.
- `npm run build`: OK.
- `npm run typecheck`: OK.
- `npm run lint:react`: OK, React Doctor **82 / 100** (warnings opcionales).
- Eliminado `tsconfig.tmp.json` (ruido de trabajo).

Si Docker no está disponible, `php artisan test --filter=InertiaMigrationPilotTest` puede ejecutarse en host, pero este proyecto normalmente valida Artisan dentro del contenedor.

## React Doctor

El script `npm run lint:react` ejecuta:

```bash
npx react-doctor@latest
```

Debe ejecutarse después de los pilotos y antes de cerrar cada módulo grande migrado.

## Deploy

El deploy sigue siendo monolítico:

- Laravel sirve rutas Blade e Inertia.
- Vite genera assets en `public/build`.
- `CacheStaticBuildAssets` sigue aplicando headers a `/build/*`.
