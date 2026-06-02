# Frontend Inertia + React + TypeScript

Esta guía documenta la migración progresiva de Ciclo Finca 4 desde Blade + JavaScript modular hacia Laravel + Inertia.js + React + TypeScript.

## Arquitectura

- Laravel sigue siendo el router principal y la fuente de verdad para reglas de negocio, validación, permisos, sesiones, CSRF, queries y persistencia.
- Inertia se usa solo en rutas migradas mediante `Inertia::render(...)`.
- Blade convive con Inertia durante la migración.
- Emails, PDF, vistas imprimibles y exports binarios permanecen en Blade/Laravel.
- React se encarga de presentación, estado de UI, formularios y componentes.

## Arquitectura frontend (por características + shared)

Inertia resuelve páginas solo desde `resources/js/Pages`. Esas páginas deben ser **delgadas** (re-export del módulo real):

```tsx
// resources/js/Pages/Client/Products/Show.tsx
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
resources/js/
  app.tsx
  bootstrap.ts
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
- Entrypoint React: `resources/js/app.tsx`
- Bootstrap TS/axios: `resources/js/bootstrap.ts`
- Middleware compartido: `app/Http/Middleware/HandleInertiaRequests.php`
- Tipos compartidos: `resources/js/shared/types/` (+ re-exports en `resources/js/types/`)
- Layouts canónicos: `resources/js/shared/components/layout/`
- UI compartida: `resources/js/shared/components/ui/`
- Módulos: `resources/js/features/client/*`
- Helpers de dominio (cart/favorites API): `resources/js/lib/` (sin mover lógica de negocio a React)

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
| `/` | `clients.home` | `ClientPageController@home` | `Client/Home/Index` |
| `/catalog` | `clients.catalog` | `ClientPageController@catalog` | `Client/Catalog/Index` |
| `/legal/terminos` | `clients.legal.terms` | `ClientLegalController@terms` | `Client/Legal/Terms` |
| `/legal/privacidad` | `clients.legal.privacy` | `ClientLegalController@privacy` | `Client/Legal/Privacy` |
| `/legal/cambios-devoluciones` | `clients.legal.returns` | `ClientLegalController@returns` | `Client/Legal/Returns` |
| `/contacto` | `clients.contact` | `ClientLegalController@contact` | `Client/Legal/Contact` |
| `/product/{id}/{slug?}` | `clients.product` | `ClientPageController@product` | `Client/Products/Show` |
| `/cart` | `clients.cart` | `ClientPageController@cart` | `Client/Cart/Index` |
| `/login`, `/register`, `/verify`, `/recovery*` | `ClientUserController` | Auth pages bajo `Client/Auth/*` |
| `/profile` | `ClientUserController@show` | `Client/Profile/Index` |
| `/invoices`, `/invoices/{sale}` | `ClientPageController` | `Client/Invoices/Index`, `Client/Invoices/Show` |
| `/notifications` | `ClientPageController@notifications` | `Client/Notifications/Index` |
| `/favorites` (JSON) | `FavoriteProductController` | Drawer en layout; página `Client/Favorites/Index` si aplica |

### Home cliente

La Home cliente conserva la URL `/` y el nombre de ruta `clients.home`.

Props enviadas por `ClientPageController@home`:

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

- `resources/js/types/home.ts`

La vista Blade antigua `resources/views/client/home.blade.php` se mantiene temporalmente como referencia hasta completar validaciones visuales y siguientes migraciones.

La Home usa `Link` de Inertia para navegación interna y `usePage().url` desde `ClientLayout` para estado activo de menú. Esto evita depender de `window.location.pathname` y mantiene la base lista para una futura estrategia SSR si se habilita.

### Catálogo cliente

El catálogo cliente conserva la URL `/catalog` y el nombre de ruta `clients.catalog`.

Props enviadas por `ClientPageController@catalog`:

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

- `resources/js/types/catalog.ts`

Patrón de filtros: `CatalogFilters` usa `router.get('/catalog', params, { preserveScroll: true })`, conservando query string y dejando Laravel como fuente de verdad para validaciones de precio, filtros, orden y paginación.

### Detalle de producto

- Página Inertia: `Client/Products/Show` (antes `Client/Product/Index`).
- Lógica UI: `features/client/product/` (`ProductShowPage`, `ProductGallery`, `ProductPurchasePanel`, `QuantitySelector`, pestañas y reseñas).
- Payload Laravel: `ProductDetailPayloadBuilder::build(ProductDetailPayloadContext $context)` — contexto readonly en `App\Support\ClientInertia\ProductDetailPayloadContext`.

## JS legacy en páginas Inertia (clasificación)

| Asset / hook | Clasificación | Notas |
|---|---|---|
| `useCatalogPageInit` → `bundles/catalog.js`, `clients-catalog-heartbeat.js` | **temporal** | Rail, flyouts, filtros móviles, spotlight Swiper |
| `useProductPageInit` → `bundles/product.js` | **temporal** | Carrusel, cantidad, subtotal, add-to-cart DOM |
| `useCartPageInit` → `bundles/cart.js` | **temporal** | Acciones de línea y checkout en carrito migrado |
| `FavoritesDrawer` → `clients-header-auth.js` | **temporal** | Drawer y lista AJAX de favoritos |
| `lib/cart.ts`, `lib/favorites.ts` | **mantener** | Puente TS hasta acciones 100% React |
| `catalog-product-favorites.js` (Vite) | **temporal** | Favoritos en cards cuando legacy catalog bundle corre |
| `clients-home.js`, `clients-catalog.js`, `clients-product.js`, `clients-cart.js` | **B. bridge** en `vite.config.js` | Entradas Blade residual o bridge; no eliminar sin mapa de vistas Blade |
| `checkout-copy.js`, `invoice-print.css`, `clients-users.css` | **A. Blade residual** | Checkout/copy y estilos no-Inertia |
| `auth-welcome-toast.js`, `client-flash.js` | **A / temporal** | Blade auth; toasts Inertia usan `ToastProvider` |

## Carrito Legacy Desde React

El carrito **página** está en Inertia; las acciones de línea siguen parcialmente en `bundles/cart.js`. Para acciones puntuales desde otras páginas Inertia existe:

```txt
resources/js/lib/cart.ts
```

`addToCart(productId, quantity, csrfToken)` encapsula `fetch('/cart/add')`, headers JSON, CSRF y normalización de `cart_count` a `cartCount`.

Mientras carrito/catálogo/detalle de producto no estén migrados, los componentes React pueden seguir emitiendo el evento temporal `cf4:cart-count` para sincronizar el contador visual del header. Cuando carrito sea migrado, este helper debe convertirse en la API común de Home, catálogo y detalle.

## Favoritos Legacy Desde React

Favoritos todavía mantiene backend legacy. Para acciones puntuales desde catálogo existe:

```txt
resources/js/lib/favorites.ts
```

`toggleFavorite(productId, csrfToken)` encapsula `POST /favorites/toggle` y normaliza `is_favorite` a `isFavorite`.

## Cómo crear una página Inertia

1. Crear el componente en `resources/js/Pages/...`.
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
- `AdminLayout`: shell admin piloto (`resources/js/Layouts/AdminLayout.tsx`); **no migrar admin** en esta fase.
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

`resources/js/app.tsx` se mantiene como entrypoint liviano. El CSS cliente se importa desde `ClientLayout` y páginas cliente específicas; el CSS admin se importa desde `AdminLayout`. Esto evita mezclar CSS admin en pantallas cliente durante la migración progresiva.

No usar CSS-in-JS ni reescribir tokens globales en esta fase.

## Comandos

```bash
npm run build
npm run typecheck
npm run lint:react
docker exec laravel_app_ciclo php artisan test --filter=InertiaMigrationPilotTest
docker exec laravel_app_ciclo php artisan test --filter=CF4ClientLegalPagesTest
docker exec laravel_app_ciclo php artisan test
```

Documentación restaurada desde `main` (no eliminada sin motivo): `docs/CATALOG_IMPORT_EXPORT.md`, `docs/CRON_RENDER_LARAVEL.md`. `CF4-146-PR-BODY.md` era artefacto local de PR y no se restauró.

Última validación (corrida arquitectónica):

- `docker exec laravel_app_ciclo php artisan test`: **228 passed**, 192 skipped.
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
