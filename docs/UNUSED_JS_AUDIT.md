# Unused JS audit (React Doctor `deslop/unused-file`)

React Doctor’s `deslop/unused-file` rule traces reachability from **static** entry points. In this repo that produces **~200+ false positives** because many assets are loaded through paths the rule does not model.

**Do not bulk-delete files based on that rule alone.**

## How assets are actually loaded

| Mechanism | Entry | What it covers |
|-----------|--------|----------------|
| **Vite `input`** | `vite.config.js` | Admin/client CSS+JS bundles built for production |
| **Blade `@vite([...])`** | `resources/views/**/*.blade.php` | Legacy Blade pages still on JS bundles (not Inertia) |
| **Inertia** | `resources/ts/app.tsx` | `import.meta.glob('./Pages/**/*.tsx')` — resolves pages at runtime |
| **Dynamic `import()`** | e.g. `useCatalogPageInit`, `HeaderCatalogSearch` | `client/bundles/catalog.ts`, `header-catalog-search.ts`, etc. |

## Categories

### 1. False positive — Inertia pages (`resources/ts/Pages/**`)

All `Pages/Client/*.tsx` and `Pages/Admin/*.tsx` files are **reachable** via `app.tsx` glob when Inertia renders a route. They will appear as “unused” in deslop.

### 2. False positive — Vite entries (`vite.config.js`)

Every path in `adminAssets`, `clientAssets`, `sharedAssets`, and `errorAssets` is an entry point even if no TS file imports it directly.

### 3. False positive — Blade-only legacy bundles

Admin JS under `resources/ts/admin/**/*.ts` referenced from Blade `@vite`. Client storefront JS entrypoints were removed in favor of Inertia (`docs/LEGACY_JS_TO_TS_MIGRATION.md`); legacy Blade views may still exist but no longer load deleted bundles.

### 4. False positive — Feature modules (`resources/ts/features/**`, `resources/ts/shared/**`)

Imported by Inertia pages and by each other. The static analyzer does not follow the glob entry.

### 5. Ignore — Build/vendor noise

Paths under `lighthouse*/`, `vendor/`, and similar should not be deleted as part of app cleanup.

### 6. Candidates for real removal (manual only)

Only after **all** of:

1. Not in `vite.config.js` `input`
2. Not in any `@vite([...])` in Blade
3. Not in any `import()` from TS/JS entry
4. Not referenced from another reachable bundle (grep `resources/ts`)

Use:

```bash
# Blade + Vite cross-check (from repo root)
rg "@vite\(" -n resources/views
rg "resources/ts" vite.config.js

# Dynamic imports from React
rg "import\(['\"]@/client/" resources/ts

# Legacy bundle imports
rg "import\(|require\(" resources/ts/client resources/ts/admin
```

## Regenerate Blade ↔ Vite map

```bash
python3 scripts/audit-vite-blade-assets.py
```

Exit code `1` means a Blade `@vite` JS path is missing from `vite.config.js` `input`. `reports-by-category.ts` is now in the Vite input list.

## React Doctor recommendation

Until deslop supports Laravel Vite + Inertia + Blade:

- Treat `deslop/unused-file` as **informational**
- Prioritize rule families with real UX impact (`exhaustive-deps`, `no-derived-useState`, a11y, hooks)
- Schedule orphan deletion only from this audit checklist, one file at a time

## Related docs

- `docs/LEGACY_JS_TO_TS_MIGRATION.md` — aggressive JS→TS pass, eliminations, known breaks
- `docs/APP_STRUCTURE.md` — client Inertia vs Blade split
- `AGENTS.md` — phased migration notes
