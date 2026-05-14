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
- Catalog UX follow-up direction: align `footer.css` with brand tokens; move catalog product search into the client header as a GET form only on the catalog route so it is not modeled as a sidebar filter; hide the catalog category trigger/toolbar from desktop layouts from about 1024px up and show them when the layout collapses to a single column; unify the category rail and filters in one sticky wrapper (`catalog-sidebar-stack`) with shared scrolling instead of mismatched sticky heights; optional second font scoped to `.catalog-shell`; refresh catalog-nav icons (e.g. replace `fa-th`, center rail icons when collapsed, avoid `fa-search` on “Aplicar filtros”); present the catalog hero as an inset rounded card so the shell background shows around it.
- If category-trigger markup changes, refresh assertions in tests such as `CF4ClientCatalogCategoryMenuTest`.
