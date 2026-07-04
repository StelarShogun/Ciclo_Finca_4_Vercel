# Backend API cutover roadmap

Objetivo: dejar `backend/` como API Laravel y conservar Blade solo para artefactos técnicos.

## Estado medible

Fuente de verdad:

```bash
cd backend
php scripts/audit-legacy-ui.php --markdown
```

Baseline actual: `backend/docs/legacy-ui-baseline.json`.

## Reglas

- Toda página pública nueva vive en `frontend/app`.
- `backend/routes/api.php` es el contrato de producto.
- `backend/routes/web.php` solo puede conservar CSRF, OAuth Google, Pulse, jobs internos, deploy helpers protegidos, descargas, impresión, PDF, Excel y errores.
- Blade técnico permitido: emails, errores, PDF, impresión, reportes exportables, Pulse y layouts mínimos para esos artefactos.
- No borrar una ruta web hasta confirmar que el frontend usa el endpoint API equivalente o que el flujo ya no existe.

## Orden de corte

1. Admin reportes y exports: migrar enlaces Next a `/api/v1/admin/reports/*` o mantener solo descargas técnicas.
2. Admin inventario, productos, marcas, categorías, proveedores, ventas y órdenes: eliminar las páginas Inertia cuando las pantallas Next tengan paridad.
3. Storefront cliente: home, catálogo, producto, auth, carrito, perfil, favoritos, facturas y notificaciones deben responder desde Next y APIs.
4. Assets backend: borrar `resources/ts/Pages`, módulos Inertia y CSS de UI normal cuando no queden referencias `@vite`.
5. Composer/NPM: retirar Inertia y dependencias UI solo cuando el inventario no tenga `Inertia::render` activo.

## Backlog frontend

- Seguridad UX: errores de sesión claros, logout visible, recuperación y verificación con rate-limit feedback.
- Diseño: tokens en `frontend/app/globals.css`, contraste AA, estados focus visibles y targets táctiles mínimos.
- Tienda: home, catálogo, producto, carrito y checkout con empty/loading/error states consistentes.
- Admin: tablas, filtros, reportes, acciones destructivas e import progress con componentes existentes primero.
- Accesibilidad: labels, landmarks, focus order, teclado y `prefers-reduced-motion`.
- Performance: skeletons útiles, imágenes dimensionadas y medición de rutas críticas.
- Observabilidad: logs sanitizados y trazas de flujos de auth, checkout, import/export.
