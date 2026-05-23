# 📦 Pull Request

## 📋 Description

Standardizes **SweetAlert2** across the admin panel and client storefront for confirmations, success toasts, errors, warnings, and loading states (CF4-146).

**Before:** Mixed UX — some screens used native `alert()`, `confirm()`, and `prompt()`, others used SweetAlert with different colors and button styles.

**After:** One shared look and behavior (CF4 green palette, rounded buttons, toasts top-right ~3s) via helpers `cf4Confirm`, `cf4Toast`, `cf4Error`, `cf4Warning`, `cf4Loading`, `cf4PromptTextarea`, plus global session flash handling on layout load.

**Out of scope (unchanged):** Unlighthouse, WebP/AVIF, Font Awesome subset, shell lazy-load strategy, existing Vite performance tuning. No new npm packages — reuses existing `sweetalert2`.

---

## 🎯 User Story

**Related US:** CF4-146

**US description:**
> As an admin user and as a client user, I want consistent confirmation and alert messages across all operations, so I always know whether an action succeeded, failed, or needs confirmation.

---

## 🧩 Changes made

### ✨ New feature

- Shared styles: `resources/css/shared/cf4-swal.css` (imported in admin `shell-base.css` and client `variables-reset.css`).
- Extended helpers: `resources/js/admin/shared/swal.js` and `resources/js/client/swal.js` (`cf4*` API + lazy `getSwal` / `fireSwal`).
- Global session flash → SweetAlert:
  - Admin: `resources/views/admin/partials/cf4-flash-swal.blade.php` + logic in `resources/js/admin/shell.js`.
  - Client: `resources/views/client/partials/cf4-flash-swal.blade.php` + `resources/js/client/client-flash.js`.
- New Vite entries: `client-flash.js`, `xml-deviation-review.js`, `detail-supplier-page.js`, `recovery-success-modal.js`, `register-validation-errors.js`, `invoices-review-modal.js`.
- Client invoice links: `data-cf4-confirm-invoice` + handlers in `invoices-page.js`.
- Regression test: `tests/Feature/CF4146SweetAlertConsistencyTest.php` (fails if native `alert`/`confirm`/`prompt` remain in `resources/js` or `resources/views`).

### 🐛 Bug fix

- Removed all native browser dialogs (`alert`, `confirm`, `prompt`) from JS and Blade in `resources/`.
- Removed `window.confirm` fallback on admin invoice/print confirmation in `sales.js`.

### ♻️ Refactor

- Migrated priority modules to `cf4*` helpers: orders, sales, supplier orders, suppliers, reports-by-category, XML deviation review, brand, login, clients (admin), inventory actions/modals, supplier order create, client profile (`clients-users.js`), cart/login flows, invoices review modal, etc.
- Removed duplicate inline session flash HTML on admin inventory, orders, and suppliers index (shell now shows flash once).
- Extracted inline scripts to Vite bundles where needed (XML deviation review, register errors, recovery success, invoices review).

### 🧪 Tests added or updated

- `tests/Feature/CF4146SweetAlertConsistencyTest.php` — scans `resources/js` and `resources/views` for native dialog calls.

### 📚 Documentation updated

- N/A (behavior-only UX change).

---

## 🏗 Affected modules or components

| Area | Affected? |
| ------ | ----------- |
| Backend (Laravel) | ☐ (session flash keys only; no controller changes required for this PR) |
| Frontend | ☑ |
| Database | ☐ |
| API | ☐ |
| Other | ☑ (Vite manifest, shared CSS) |

**Relevant files:**

- `resources/js/admin/shared/swal.js`, `resources/js/client/swal.js`
- `resources/js/admin/shell.js`, `resources/js/client/client-flash.js`
- `resources/css/shared/cf4-swal.css`
- `resources/views/admin/layouts/*.blade.php`, `resources/views/client/layouts/app.blade.php`
- `resources/views/admin/partials/cf4-flash-swal.blade.php`, `resources/views/client/partials/cf4-flash-swal.blade.php`
- `resources/js/admin/orders/*`, `resources/js/admin/sales/*`, `resources/js/admin/suppliers/*`, `resources/js/admin/inventory/*`, …
- `vite.config.js`
- `tests/Feature/CF4146SweetAlertConsistencyTest.php`

**Known follow-up (low risk):** `resources/views/categories/parents/create.blade.php` still uses inline SweetAlert with `confirmButtonColor` (standalone page, not admin shell). Not a native-dialog violation.

---

## ⚙️ How to test this change

### Automated (required before merge)

```bash
npm run build
docker compose exec -T app_ciclo php artisan test --filter=CF4146SweetAlertConsistencyTest
```

Optional sanity check:

```bash
rg -n "\b(window\.)?(alert|confirm|prompt)\s*\(" resources/js resources/views
# Expected: no matches
```

### Environment

- Branch: `Dev` (or feature branch off `Dev`).
- Run `npm run build` (or Vite dev) so new JS entries are available.
- Use Chrome/Firefox; **do not** rely on native browser dialogs — you should only see styled CF4 modals/toasts.

### What QA should look for (simple)

| What you do | What you should see | What would be wrong |
|-------------|---------------------|---------------------|
| Confirm something (e.g. delete, cancel order) | Green or red rounded buttons, “Cancelar” on the left | Gray system `OK/Cancel` box |
| Action succeeds | Green toast, top-right, ~3 seconds, closes alone | Only a plain page banner or nothing |
| Action fails | Red/error modal with “Cerrar” | `alert()` popup |
| Need to type a reason (reject order) | SweetAlert textarea, min length message | `prompt()` box |
| Reload page after server message (flash) | One SweetAlert (toast or modal), not duplicate HTML banner + toast | Two messages saying the same thing |

---

## 📋 Manual QA checklist (step by step)

Use an **admin** account and a **client** account. If something asks for confirmation, **cancel once** and **confirm once** when noted.

### A. Admin — Inventory (`/inventory`)

1. Open **Inventario**.
2. Pick a product → **Desactivar / eliminar** (or equivalent destructive action).
3. **Cancel** the dialog → nothing should change.
4. Try again → **Confirm** → success **toast** (top-right, green, auto-close).
5. If you can trigger a validation/error (e.g. product in use), check **error** dialog (not browser alert).

### B. Admin — Client orders (`/orders`)

1. Open **Encargos en línea**.
2. **Marcar listo para recoger** on a pending order → confirm → toast on success.
3. **Confirmar encargo** (complete sale) → confirm dialog → toast.
4. **Rechazar encargo** → dialog with **textarea** for reason (min. 3 characters) → try empty → validation message → fill reason → confirm → toast.
5. Confirm there is **no** old green text banner at top for session messages (only SweetAlert if the server sent flash).

### C. Admin — Supplier orders (`/supplier-orders`)

1. Open **Pedidos a proveedor**.
2. **Confirmar pedido** → CF4 confirm → success toast.
3. **Cancelar pedido** → textarea for reason (same pattern as client order reject).
4. Filters: set **Desde** date **after** **Hasta** → submit → **warning** SweetAlert (not browser `alert`).

### D. Admin — XML deviation review

1. Go to supplier order flow → XML deviation **review** screen (route like `/supplier-orders/xml-deviation/review`).
2. Click apply with **zero** products selected → **warning** (“Seleccione al menos un producto…”).
3. Select products → apply → **confirm** with HTML summary → cancel → form does not submit.
4. Confirm → form submits, button shows spinner text.

### E. Admin — Reports by category

1. Open **reportes por categoría** (custom date range).
2. Choose **custom range**, leave one date empty → submit → **warning**.
3. Set end date **before** start date → **warning** (not `alert()`).

### F. Admin — Sales / invoices (`/sales` or sales list)

1. On a completed sale, click **Ver factura** → confirm → opens invoice.
2. Click **Imprimir** → confirm → print view / print dialog.
3. Cancel each once — should **not** navigate or print.

### G. Admin — Suppliers (`/suppliers`)

1. Create or edit supplier with invalid fields → **validation error** dialog.
2. Delete supplier → **danger** confirm (red button) → success toast.
3. Session success after redirect → **toast** from shell (no duplicate `x-admin-alert` on page).

### G2. Admin — Brands (`/brands`)

1. Delete a brand → confirm → toast or warning if blocked.

### H. Admin — Global flash

1. Perform any action that sets `session('success')` or `session('error')` on an admin shell page (e.g. save settings that redirects with flash).
2. On load: **one** SweetAlert (success toast or error modal). No second duplicate banner.

### I. Client — Profile (`/profile`)

1. **Editar perfil** → change a field → **Guardar** → confirm → save → **toast** + optional inline green alert on page.
2. Cancel confirm → changes not saved.
3. **Change password** → confirm → success toast on OK.

### J. Client — Invoices (`/invoices`)

1. Open **Mis Facturas**.
2. Click **Ver detalle** on a row → **“¿Ver factura?”** confirm → cancel stays on list.
3. Confirm → goes to detail page.
4. (If **Historial** tab has pending product reviews) review stars modal appears with CF4 styling — save or close as designed.

### K. Client — Global flash

1. Trigger `session('status')` or `session('error')` on any client page using `app` layout (e.g. form error redirect).
2. SweetAlert only — **no** old green/red Bootstrap alert blocks at top of main.

### L. Client — Login / register (if time)

1. Login with server-side error → CF4 error dialog (register/login blades).
2. Password recovery success → success modal then redirect (`recovery-success-modal.js`).

### Expected result (global)

- **No** native browser `alert`, `confirm`, or `prompt` anywhere in admin/client flows above.
- Destructive actions use **red** confirm button.
- Success uses **toast** (~3s, top-right) unless a full modal is more appropriate (errors, important warnings).
- Spanish copy, readable titles.
- `npm run build` passes; CI test `CF4146SweetAlertConsistencyTest` passes.

---

## 📸 Evidence

| # | Description | Image / link |
|---|-------------|-----------------|
| 1 | Confirm dialog (CF4 green / danger buttons) | *(QA screenshot)* |
| 2 | Success toast top-right | *(QA screenshot)* |
| 3 | Error modal | *(QA screenshot)* |
| 4 | Reject order with textarea | *(QA screenshot)* |
| 5 | `php artisan test --filter=CF4146` green | *(CI screenshot)* |

**Security:** No secrets added. Flash payloads use existing session keys only.

---

## 🧪 Testing performed

- [x] Manual testing (checklist above — dev to confirm on staging)
- [x] Unit tests (`CF4146SweetAlertConsistencyTest`)
- [ ] Integration tests (not added beyond feature scan)
- [ ] Staging environment testing (QA)

---

## ⚠️ Risks or impacts

- [ ] Performance impact (SweetAlert still lazy-loaded; shell prefetches on idle)
- [ ] Database changes
- [x] Possible conflict with other modules (pages with custom inline `Swal.fire` — mostly migrated; category parent create page is minor outlier)
- [ ] None

**Notes for reviewers:**

- Profile JS lives in `clients-users.js`, not legacy `clients-profile.js`.
- Admin invoice links keep `data-confirm-invoice` / `data-confirm-print` attribute names; handlers use `cf4Confirm`.
- Async `onclick` handlers (`markReadyToPickup`, etc.) remain on `window` for Blade compatibility.

---

## 📌 Pre-merge checklist

- [x] Code follows project conventions
- [ ] No console errors (verify in browser QA)
- [x] Tests updated if applicable
- [x] Build verified (`npm run build`)
- [ ] User story linked in Jira (CF4-146)
- [x] No secrets or sensitive data in code

---

## 👀 Suggested reviewer

@Darwin-Nunez-10

---

## 📅 Sprint

**Sprint:** *(current sprint)*
