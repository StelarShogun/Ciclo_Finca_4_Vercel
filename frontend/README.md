# Ciclo Finca 4 Frontend

Next.js App Router para la tienda y el panel administrativo. El backend Laravel vive en `../backend` y expone la API usada por `lib/api/*`.

## Comandos

```bash
npm run dev
npm run lint
npm test
npm run build
```

## Entorno local

Crear `frontend/.env.local` con la URL pública del backend:

```bash
NEXT_PUBLIC_API_URL=http://localhost:8000
```

## Superficies

- Tienda: `/`, `/catalog`, `/product/[id]`, `/cart`, `/checkout`.
- Cliente: `/login`, `/register`, `/verify`, `/profile`, `/account`, `/favorites`, `/invoices`, `/notifications`.
- Admin: `/admin/login`, `/admin` y módulos bajo `/admin/*`.

## Roadmap de calidad

- Seguridad UX: sesión Sanctum clara, logout confiable, recuperación/verificación con feedback de rate limit.
- Diseño: tokens en `app/globals.css`, contraste AA, focus visible y targets táctiles mínimos.
- Ecommerce: empty/loading/error states en home, catálogo, producto, carrito y checkout.
- Admin: tablas, filtros, reportes, acciones destructivas e import progress con componentes existentes antes de sumar librerías.
- Accesibilidad y movimiento: teclado, labels, landmarks y `prefers-reduced-motion` en animaciones.
- Performance: imágenes dimensionadas, skeletons de rutas críticas y build limpio.

## Validación antes de merge

Desde `frontend/`:

```bash
npm run lint
npm test
npm run build
```

Antes de mergear a `Dev`, correr el gate completo desde la raíz:

```bash
./scripts/ci-check-docker.sh
```
