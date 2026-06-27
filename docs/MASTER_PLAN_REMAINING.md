# Master Plan Remaining Work

Estado revisado sobre el árbol actual después del refactor grande. Esta lista solo incluye faltantes reales o trabajo que todavía requiere revisión manual; lo ya cubierto no se repite.

## Fase 1 — Seguridad y base arquitectónica

- Logs productivos sensibles ya usan contexto reducido o mensajes genéricos. Quedan usos controlados: hash interno, `ReportExportException` con mensaje de dominio, adapter filesystem y comandos dev/seeder manuales.
- Las excepciones de dominio base ya existen; `InventoryException` y `ReportExportException` ya están en uso. `SaleException` y `SupplierOrderException` quedan disponibles para el siguiente refactor de flujos que hoy devuelven errores JSON/ValidationException controlados.
- Policies/gates revisados: los controllers usan autorización explícita vía `Gate::forUser(...)->authorize(...)` o gates dedicados; se mantiene porque no reduce seguridad frente a `$this->authorize()`.
- Endpoints internos: `internal/vercel/*`, `/run-migrations` y `/run-seeders` usan header `X-Deploy-Secret`/`X-Internal-Key`; tests cubren rechazo de query-string secret para jobs.

## Fase 2 — Módulos críticos

- Productos: `ListProducts` y `UpdateManualStock` ya existen y están usados. Quedan solo aliases exactos no usados para `DeleteProduct`, `RestoreProduct`, `ToggleProductStatus`, `UploadProductImage`, `DeleteProductImage`, `ReorderProductImages`; crear únicamente cuando se conecten a un controller/flujo real para evitar clases muertas.
- Importación catálogo: storage/validator separados, job cubierto y limpieza automática de archivos locales antiguos antes de guardar nuevas importaciones.
- XML proveedores: ya existen Actions, storage, analyzer y applier; falta extraer el parseo interno del servicio legado solo si vuelve a crecer o se agregan nuevos formatos.
- Inventario: `InventoryMovementService` centraliza movimientos con `DB::transaction()` y `lockForUpdate()`; ajustes manuales, ventas, cancelaciones/devoluciones y proveedor pasan por ese punto. Agregado test unitario para entrada/salida manual y rechazo de stock negativo.

## Fase 3 — Cliente

- Catálogo: Actions exactas `ListCatalogProducts`, `ShowProductPage`, `GetProductSuggestions` y `GetTrendingSearches` ya existen y los controllers las usan; queda revisión visual responsive.
- Carrito: `AddProductToCart` y `CheckoutService` ya existen y `CartController` los usa; queda revisión visual/manual del checkout completo.
- Notificaciones: Actions exactas `MarkNotificationAsRead` y `MarkAllNotificationsAsRead` ya existen con endpoints protegidos por ownership; falta solo revisión visual del flujo de toasts/página.
- Reseñas: regla revisada. `SaveProductReview` exige compra completada, usa `updateOrCreate` y la tabla tiene unique `client_id/product_id`; queda solo moderación futura si el negocio la pide.

## Fase 4 — Reportes y dashboard

- Reportes v2 ya tiene registry/provider/exporters para registry exports y errores de dominio con `ReportExportException`; falta migrar reportes especiales antiguos a providers uniformes si se quiere una sola arquitectura para todos.
- Dashboard ya está separado en KPI/chart/recent services. La cache de índice y gráficas se centralizó en `AdminDashboardCache` y se invalida desde ventas, productos, stock manual, categorías y proveedores; queda solo medición fina de queries con un dataset grande real.
- Falta streaming/chunking real para exportaciones grandes fuera de los límites actuales.

## Fase 5 — Frontend

- `resources/js` ya fue movido a `resources/ts`; quedan carpetas legacy aceptables en nombres como `Pages` y wrappers Inertia, no rutas `js`.
- Smoke HTTP en contenedor verificado para home, catálogo, login cliente, login admin y sugerencias. Queda revisión visual manual/browser completa para admin: ventas, proveedor, productos, reportes, dashboard.
- Falta consolidar algunos componentes admin Blade/React duplicados solo donde duela mantenerlos.

## Fase 6 — Tests y limpieza

- Inventario base cubierto con `tests/Unit/Services/InventoryMovementServiceTest.php`; agregar tests unitarios específicos solo si se separan más services de ventas/proveedor.
- Tests de policies críticos agregados para ownership de ventas, facturas, favoritos, notificaciones, reseñas y perfil.
- Revisar y limpiar aliases o clases equivalentes si se decide no mantener nombres exactos del plan maestro.
- Gate Docker completo ejecutado: Pint, PHPUnit MySQL, PHPStan y `npm run build` pasaron.
