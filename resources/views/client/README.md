# Client Blade views

The storefront UI is **Inertia + React** (`resources/js/Pages/Client/*`, `app/Http/Controllers/Client/*`).

## Active Blade (print only)

| View | Route | Notes |
|------|-------|--------|
| `invoice-print.blade.php` | `clients.invoices.print` | Printable invoice; `@vite(invoices-page.ts)` for auto-print |
| `layouts/print.blade.php` | — | Minimal print layout |

All other client routes render Inertia pages. Product thumbnails in admin reuse `shared/media/product-media.blade.php`.
