# Unused JS audit (React Doctor `deslop/unused-file`)

React Doctor’s `deslop/unused-file` rule traces reachability from **static** entry points. In this repo that produces **~200+ false positives** because many assets are loaded through paths the rule does not model.

**Do not bulk-delete files based on that rule alone.**

## How assets are actually loaded

| Mechanism | Entry | What it covers |
|-----------|--------|----------------|
| **Vite `input`** | `vite.config.js` | Admin/client CSS+JS bundles built for production |
| **Blade `@vite([...])`** | `resources/views/**/*.blade.php` | Legacy Blade pages still on JS bundles (not Inertia) |
| **Inertia** | `resources/js/app.tsx` | `import.meta.glob('./Pages/**/*.tsx')` — resolves pages at runtime |
| **Dynamic `import()`** | e.g. `useCatalogPageInit`, `HeaderCatalogSearch` | `client/bundles/catalog.js`, `header-catalog-search.js`, etc. |

## Categories

### 1. False positive — Inertia pages (`resources/js/Pages/**`)

All `Pages/Client/*.tsx` and `Pages/Admin/*.tsx` files are **reachable** via `app.tsx` glob when Inertia renders a route. They will appear as “unused” in deslop.

### 2. False positive — Vite entries (`vite.config.js`)

Every path in `adminAssets`, `clientAssets`, `sharedAssets`, and `errorAssets` is an entry point even if no TS file imports it directly.

### 3. False positive — Blade-only legacy bundles

Client/admin JS under `resources/js/client/*.js` and `resources/js/admin/**` referenced from Blade `@vite` (see generated list in CI or run the audit script below). Example: `clients-users.js` on login/register/profile Blade views; `clients-catalog.js` on `catalog.blade.php`.

### 4. False positive — Feature modules (`resources/js/features/**`, `resources/js/shared/**`)

Imported by Inertia pages and by each other. The static analyzer does not follow the glob entry.

### 5. Ignore — Build/vendor noise

Paths under `lighthouse*/`, `vendor/`, and similar should not be deleted as part of app cleanup.

### 6. Candidates for real removal (manual only)

Only after **all** of:

1. Not in `vite.config.js` `input`
2. Not in any `@vite([...])` in Blade
3. Not in any `import()` from TS/JS entry
4. Not referenced from another reachable bundle (grep `resources/js`)

Use:

```bash
# Blade + Vite cross-check (from repo root)
rg "@vite\(" -n resources/views
rg "resources/js" vite.config.js

# Dynamic imports from React
rg "import\(['\"]@/client/" resources/js

# Legacy bundle imports
rg "import\(|require\(" resources/js/client resources/js/admin
```

## Regenerate Blade ↔ Vite map

```bash
python3 scripts/audit-vite-blade-assets.py
```

Exit code `1` means a Blade `@vite` JS path is missing from `vite.config.js` `input`. As of the last audit, `resources/js/admin/sales/reports-by-category.js` is referenced from a Blade view but was not in the Vite input list — add it there before relying on that bundle in production.

## React Doctor recommendation

Until deslop supports Laravel Vite + Inertia + Blade:

- Treat `deslop/unused-file` as **informational**
- Prioritize rule families with real UX impact (`exhaustive-deps`, `no-derived-useState`, a11y, hooks)
- Schedule orphan deletion only from this audit checklist, one file at a time

## Related docs

- `docs/APP_STRUCTURE.md` — client Inertia vs Blade split
- `AGENTS.md` — phased migration notes
