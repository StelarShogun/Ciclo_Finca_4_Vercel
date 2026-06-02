# Frontend Inertia + React + TypeScript

Esta guía documenta la migración progresiva de Ciclo Finca 4 desde Blade + JavaScript modular hacia Laravel + Inertia.js + React + TypeScript.

## Arquitectura

- Laravel sigue siendo el router principal y la fuente de verdad para reglas de negocio, validación, permisos, sesiones, CSRF, queries y persistencia.
- Inertia se usa solo en rutas migradas mediante `Inertia::render(...)`.
- Blade convive con Inertia durante la migración.
- Emails, PDF, vistas imprimibles y exports binarios permanecen en Blade/Laravel.
- React se encarga de presentación, estado de UI, formularios y componentes.

## Archivos base

- Root Inertia: `resources/views/app.blade.php`
- Entrypoint React: `resources/js/app.tsx`
- Bootstrap TS/axios: `resources/js/bootstrap.ts`
- Middleware compartido: `app/Http/Middleware/HandleInertiaRequests.php`
- Tipos compartidos: `resources/js/types/`
- Layouts: `resources/js/Layouts/`
- Componentes UI: `resources/js/Components/UI/`
- Componentes Home: `resources/js/Components/Home/`
- Helpers reutilizables: `resources/js/lib/`

## Props compartidas

`HandleInertiaRequests` expone:

- `auth.client`
- `auth.admin`
- `cartCount`
- `csrfToken`
- `flash`
- `theme`

No se envían passwords, tokens persistentes ni modelos completos.

## Estructura objetivo

```txt
resources/js/
  app.tsx
  bootstrap.ts
  Pages/
    Client/
    Admin/
  Layouts/
  Components/
    UI/
    Home/
  hooks/
  lib/
  types/
```

## Rutas migradas

| Ruta | Nombre | Controller | Página React |
|---|---|---|---|
| `/` | `clients.home` | `ClientPageController@home` | `Client/Home/Index` |
| `/legal/terminos` | `clients.legal.terms` | `ClientLegalController@terms` | `Client/Legal/Terms` |

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
- `ImageFallback`

Tipos específicos:

- `resources/js/types/home.ts`

La vista Blade antigua `resources/views/client/home.blade.php` se mantiene temporalmente como referencia hasta completar validaciones visuales y siguientes migraciones.

La Home usa `Link` de Inertia para navegación interna y `usePage().url` desde `ClientLayout` para estado activo de menú. Esto evita depender de `window.location.pathname` y mantiene la base lista para una futura estrategia SSR si se habilita.

## Carrito Legacy Desde React

El carrito completo sigue en Blade/legacy y no se migra todavía. Para acciones puntuales desde páginas Inertia existe:

```txt
resources/js/lib/cart.ts
```

`addToCart(productId, quantity, csrfToken)` encapsula `fetch('/cart/add')`, headers JSON, CSRF y normalización de `cart_count` a `cartCount`.

Mientras carrito/catálogo/detalle de producto no estén migrados, los componentes React pueden seguir emitiendo el evento temporal `cf4:cart-count` para sincronizar el contador visual del header. Cuando carrito sea migrado, este helper debe convertirse en la API común de Home, catálogo y detalle.

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

- `ClientLayout`: cabecera cliente, navegación, carrito, cuenta y footer.
- `AdminLayout`: shell admin piloto con sidebar y contenedor estándar.
- `AuthLayout`: base para futuras pantallas de login/registro.

Los layouts importan y reutilizan clases CSS existentes; no introducen Tailwind como sistema visual.

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

`resources/js/app.tsx` se mantiene como entrypoint liviano. El CSS cliente se importa desde `ClientLayout` y páginas cliente específicas; el CSS admin se importa desde `AdminLayout`. Esto evita mezclar CSS admin en pantallas cliente antes de migrar catálogo.

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
