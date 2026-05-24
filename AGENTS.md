## Learned User Preferences

- Write Git commit subjects/bodies and GitHub/GitLab PR titles and descriptions in English; Jira ticket IDs may appear alongside that text.
- When this stack runs in containers, run Laravel/PHP CLI (e.g. Artisan) inside the project Docker setup instead of assuming host PHP works the same.

## Learned Workspace Facts

- The app uses Laravel with Vite and modular CSS bundles importing shared tokens from `variables-reset.css` (admin and client); there is no central Tailwind config driving the whole UI.
- CF4-132 green branding is modeled with `--brand-*` CSS variables; existing `--color-dark` and `--color-light` stay semantic neutrals in legacy rules—do not remap them to brand greens without a deliberate token refactor.
- Most admin “headers” are per-page blocks (e.g. `.dashboard-header`, `.sales-header`, `.page-header`), not a single global top bar wired through `@yield('header')`.
- The storefront header wrapper class in Blade/CSS is `.cliente-header` (not `.client-header`).
- Client catalog work relies on a structural shell (`catalog-shell`, `catalog-hero`, `catalog-container`, category rail, filters, `catalog-main`); preserve established DOM ids and `data-*` hooks used by catalog JS, and keep presentation-only refactors from touching controllers, routes, queries, or APIs unless explicitly requested.
- Category flyouts or overlays rendered inside a scrolling column with `overflow: auto`/`scroll` are clipped by that overflow; fixing visibility usually means moving the flyout out of the scroll subtree, changing overflow/stacking, or using a portal-like pattern.
- Catalog UX (implemented): `footer.css` uses `--brand-*`; catalog search is a GET form in the client header on `clients.catalog` only; category trigger hidden from 1024px+ (rail + `catalog-sidebar-stack` sticky scroll); DM Sans on `.catalog-shell`; rail icons centered when collapsed; hero is an inset rounded card; filter submit uses `fa-sliders` not search icon.
- Storefront performance: home loads `clients-home.css` (home + spotlight chunks) instead of full `clients-page.css`; production sets long-cache headers on `/build/*` via `CacheStaticBuildAssets` middleware.
- Admin category create/subcategory pages use `admin-shell` + shared SweetAlert (`data-cf4-confirm`); catalog import/export documented in `docs/CATALOG_IMPORT_EXPORT.md`.
- If category-trigger markup changes, refresh assertions in tests such as `CF4ClientCatalogCategoryMenuTest`.
