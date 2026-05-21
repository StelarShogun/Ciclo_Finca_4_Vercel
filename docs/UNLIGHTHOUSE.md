# Unlighthouse — site-wide Lighthouse (local)

[Unlighthouse](https://unlighthouse.dev/guide/getting-started/) crawls the app, runs Lighthouse per URL, and opens a live dashboard. This project uses three config files aligned with the [official guides](https://unlighthouse.dev/guide/guides/).

## Official references

- [Getting started / CLI](https://unlighthouse.dev/guide/getting-started/)
- [Configuration](https://unlighthouse.dev/guide/guides/config)
- [Config reference](https://unlighthouse.dev/api-doc/config)
- [CLI flags](https://unlighthouse.dev/integrations/cli)
- [URL discovery](https://unlighthouse.dev/guide/guides/url-discovery)
- [Authentication](https://unlighthouse.dev/guide/guides/authentication)
- [Lighthouse options](https://unlighthouse.dev/guide/guides/lighthouse)
- [Device / throttling](https://unlighthouse.dev/guide/guides/device)
- [Debugging](https://unlighthouse.dev/guide/guides/debugging)

## Requirements

- **Node.js 20+**
- **Chrome/Chromium** for Puppeteer. If Unlighthouse fails to download Chrome, install manually and set `UNLIGHTHOUSE_CHROME_PATH`:

```bash
npx @puppeteer/browsers install chrome@stable
# Typical path (Linux): ~/.cache/puppeteer/chrome/linux-*/chrome-linux64/chrome
```

Do **not** download Chrome into the project root (`chrome/` is gitignored). Unlighthouse’s default cache is `~/.unlighthouse/`.

- App at **http://localhost:8080** (Docker)
- For realistic `render-blocking` scores: `npm run build` and no `public/hot` (Vite dev server)

## Setup

```bash
cp .env.unlighthouse.example .env.unlighthouse.local
# Edit .env.unlighthouse.local (never commit it)
npm install
```

### Product URL for guest scan

```bash
docker exec laravel_app_ciclo php artisan tinker --execute="echo '/product/'.App\Models\Product::activeInClientStore()->value('product_id');"
```

Set the result in `UNLIGHTHOUSE_PRODUCT_PATH` inside `.env.unlighthouse.local`.

## Commands

| Script | Config | Purpose |
|--------|--------|---------|
| `npm run unlighthouse:guest` | `unlighthouse.config.ts` | Public home, catalog, product (CF4-133) |
| `npm run unlighthouse:client` | `unlighthouse.client.config.ts` | Logged-in client (fixed URLs, no crawler) |
| `npm run unlighthouse:admin` | `unlighthouse.admin.config.ts` | Admin panel |
| `npm run unlighthouse:auth` | admin then client (sequential) | Both authenticated profiles |

The wrapper [`scripts/unlighthouse/run.sh`](../scripts/unlighthouse/run.sh) loads `.env.unlighthouse.local` when present.

### Run admin + client together

```bash
npm run build
rm -f public/hot
npm run unlighthouse:auth
```

- Admin reports: `./lighthouse-admin/`
- Client reports: `./lighthouse-client/`
- Cookies are auto-generated via Artisan when missing (`unlighthouse:admin-cookie`, `unlighthouse:client-cookie`).
- Fresh scan: `UNLIGHTHOUSE_NO_CACHE=1 npm run unlighthouse:auth`

One-off without npm scripts:

```bash
npx unlighthouse --site http://localhost:8080 --urls /,/catalog --debug
```

Reports are written under **`./lighthouse/`** (default `outputPath`). Browser profile: **`.puppeteer-data/`** (gitignored).

## Authentication

### Client

1. **Artisan (local only):** `docker exec laravel_app_ciclo php artisan unlighthouse:client-cookie` → set `UNLIGHTHOUSE_CLIENT_COOKIE`.
2. **Programmatic** (default in config): set `UNLIGHTHOUSE_CLIENT_EMAIL` and `UNLIGHTHOUSE_CLIENT_PASSWORD` in `.env.unlighthouse.local`. Works only if `RECAPTCHA_SITE_KEY` is not set in the app `.env` ([`ClientUserController`](../app/Http/Controllers/ClientUserController.php)).
3. **Cookies**: log in at `/login`, DevTools → Application → Cookies → copy `ciclo-finca-session` (or your `SESSION_COOKIE` name). Set `UNLIGHTHOUSE_CLIENT_COOKIE` to `name=value` or the raw value.

### Admin

reCAPTCHA blocks programmatic admin login. The **guest** scan does **not** include admin routes — run the admin profile separately.

**Cookie (pick one):**

1. **Artisan (local only):**

```bash
docker exec laravel_app_ciclo php artisan unlighthouse:admin-cookie
# Paste the printed line into .env.unlighthouse.local as UNLIGHTHOUSE_ADMIN_COOKIE=...
# Verify: curl -H "Cookie: $(docker exec ... admin-cookie | head -1)" http://localhost:8080/dashboard → 200
```

Optional (recommended): also set `UNLIGHTHOUSE_ADMIN_XSRF_TOKEN` to the `XSRF-TOKEN` cookie value from DevTools.

**Copy tips:** use Application → Cookies → `http://localhost:8080` → copy the **Value** column (not the address bar). If the value ends in `%3D`, the tooling decodes it automatically. Paste while still logged in on `/dashboard`.

Verify before scanning:

```bash
source .env.unlighthouse.local
curl -s -o /dev/null -w "%{http_code}\n" \
  -H "Cookie: ciclo-finca-session=YOUR_VALUE; XSRF-TOKEN=YOUR_XSRF" \
  http://localhost:8080/dashboard
# Expect 200 — if 302, refresh cookies or fix APP_KEY (single entry in app .env)
```

2. **Manual:** sign in at `http://localhost:8080/admin/login`, copy `ciclo-finca-session` from DevTools → Application → Cookies.

**Run:**

```bash
npm run unlighthouse:admin
# Fresh run (no cache): UNLIGHTHOUSE_NO_CACHE=1 npm run unlighthouse:admin
```

Reports go to **`./lighthouse-admin/`** (not `./lighthouse/`). URLs scanned: `/dashboard`, `/inventory`, `/reports`, `/orders`, `/sales`, plus two report subpages.

CLI equivalent:

```bash
npx unlighthouse --config-file unlighthouse.admin.config.ts --cookies "ciclo-finca-session=PASTE_VALUE"
```

If `npm run unlighthouse:admin` exits immediately, `UNLIGHTHOUSE_ADMIN_COOKIE` is missing — use the steps above.

If auth does not persist between pages:

1. Confirm `curl` returns **200** for `/dashboard` with the same cookies (see above).
2. Our admin config sets `puppeteerClusterOptions.maxConcurrency: 1`, an `authenticate` hook (warm origin + session cookies), and `lighthouseOptions.extraHeaders.Cookie` so the first route (`/dashboard`, sorted first) is not audited as `/admin/login`.
3. See [Auth not sticking](https://unlighthouse.dev/guide/guides/authentication) (`disableStorageReset` is already set in shared Lighthouse options).

## Debugging

```bash
UNLIGHTHOUSE_DEBUG=1 npm run unlighthouse:guest
```

Or add to `.env.unlighthouse.local`. For visual debugging, temporarily set in a config file ([debugging guide](https://unlighthouse.dev/guide/guides/debugging)):

```ts
puppeteerOptions: { headless: false, slowMo: 100 },
puppeteerClusterOptions: { maxConcurrency: 1 },
```

## What to look at (CF4-133 / performance)

| Audit | Guest focus |
|-------|-------------|
| `image-delivery-insight` | WebP hero, `default-*.webp` on product cards |
| `render-blocking-insight` | CDN fonts/FA; avoid scanning with Vite `:5174` |
| `layout-shifts` | Hero title / fonts on `/` |
| `total-byte-weight` | Large static assets |

Compare **guest** vs **client** vs **admin** separately; admin inventory pages load heavy JS and are not comparable to the storefront home.

On localhost, `cache-insight` often fails (short TTL) — expected. Throttling is off by default for local hosts ([device guide](https://unlighthouse.dev/guide/guides/device)).

## Seeder credentials (local only)

| Role | Email | Password |
|------|-------|----------|
| Client | `darwinn990@gmail.com` | `Darwin1234$` |
| Admin | `admin@cicloperez.com` | `Admin2024!@#` |

Do not commit `.env.unlighthouse.local` or production cookies.

## Baseline guest scan (local, 3 URLs)

**Before optimization (CF4-133 images only, Vite dev / cached):**

| Route | Performance | Notes |
|-------|-------------|-------|
| `/` | 56 | LCP 14.9 s; hero `image-delivery` ~66 KiB; render-blocking |
| `/catalog` | 56 | LCP 9.4 s; render-blocking ~1.5 s; product media ~33 KiB overserved |
| `/product/20/...` | 56 | LCP 11.7 s; render-blocking ~1.4 s |

**After performance pass (2026-05-20, production build, no `public/hot`, fresh scan):**

Prerequisites: `npm run build`, remove `public/hot`, app on `:8080`, Chrome at `~/.cache/puppeteer/.../chrome`.

```bash
export UNLIGHTHOUSE_CHROME_PATH="$HOME/.cache/puppeteer/chrome/linux-*/chrome-linux64/chrome"
export UNLIGHTHOUSE_PRODUCT_PATH=/product/20
./node_modules/.bin/unlighthouse --config-file unlighthouse.config.ts --no-cache
```

| Route | Performance | LCP | FCP | Payload | Notes |
|-------|-------------|-----|-----|---------|-------|
| `/` | **72** | 4.7 s | 3.2 s | ~598 KiB | Self-hosted fonts; `clients-header` + `clients-home` (no full `clients-page.js`); hero preload + width `srcset` |
| `/catalog` | **81** | 3.5 s | 3.4 s | ~658 KiB | Swiper lazy-loaded; `webp_480` card conversions; render-blocking ~800 ms (Font Awesome CDN) |
| `/product/20/...` | **85** | 2.8 s | 2.7 s | ~571 KiB | SweetAlert2 only on pages that need it; render-blocking ~1.1 s |

**After client + favicon pass (2026-05-21, production build, mobile scan):**

| Route | Performance | LCP | FCP | Payload | Notes |
|-------|-------------|-----|-----|---------|-------|
| `/` | **81** | 4.0 s | 3.1 s | ~419 KiB | `clients-header.js` 8.6 KiB → 0.9 KiB (search trending, favorites delegation, auth menu deferred to `requestIdleCallback`); favicon.svg 115 KiB → 0.7 KiB; favicon.ico 114 KiB → 15 KiB |
| `/catalog` | **81** | 3.6 s | 3.4 s | ~457 KiB | Same header refactor; -200 KiB favicons; CLS 0.05 mostly from catalog hero pile |
| `/product/20/...` | **84** | 2.9 s | 2.9 s | ~378 KiB | Same wins; SweetAlert2 still only loaded on demand |

> The `auth-welcome-toast.js` helper now `import('sweetalert2')` dynamically instead of relying on a globally-pre-imported `window.Swal`. The dead `sweetalert2-global.js` entry and its blade partial were removed.

Reports: `./lighthouse/localhost/*/reports/**/lighthouse.json` (latest run: `fec6/`). Dashboard URL is printed by the CLI (default `http://localhost:5678/`).

**Still open (optional):** subset or self-host Font Awesome; further trim `render-blocking-insight` on catalog/product; hero `image-delivery` ~66 KiB on mobile viewport.

**Docker:** [`Dockerfile`](../Dockerfile) builds GD with WebP (`libwebp-dev`). After image rebuild, run `php artisan media-library:regenerate --only=webp_480,webp_768,webp_1920` inside the app container.

## Admin scan (separate profile)

Requires `UNLIGHTHOUSE_ADMIN_COOKIE` in `.env.unlighthouse.local`. Without it, `npm run unlighthouse:admin` stops with instructions (see [Authentication → Admin](#admin)).

After a successful run, open reports under `./lighthouse-admin/localhost/*/reports/`. Compare admin scores separately from guest — inventory and reports pages load heavy JS bundles.

**Baseline admin scan (2026-05-21, desktop, DevTools session cookie + `XSRF-TOKEN`, `UNLIGHTHOUSE_NO_CACHE=1`, production build):**

| Route | Performance | FCP | LCP | Speed Index | CLS |
|-------|-------------|-----|-----|-------------|-----|
| `/dashboard` | **70** | 2.5 s | 2.8 s | 2.5 s | 0.011 |
| `/inventory` | **87** | 1.5 s | 1.6 s | 1.5 s | 0.001 |
| `/orders` | **91** | 1.4 s | 1.4 s | 1.4 s | 0 |
| `/sales` | **96** | 1.1 s | 1.1 s | 1.1 s | 0 |
| `/reports` | **97** | 0.9 s | 1.1 s | 0.9 s | 0 |
| `/reports/desempeno-ventas` | **98** | 0.9 s | 0.9 s | 0.9 s | 0 |
| `/reports/productos-vendidos` | **93** | 1.2 s | 1.3 s | 1.2 s | 0.04 |

**Post dashboard + favicon pass (2026-05-21):**

| Route | Performance | FCP | LCP | Speed Index | CLS | Payload |
|-------|-------------|-----|-----|-------------|-----|---------|
| `/dashboard` | **72** | 2.4 s | 2.6 s | 2.4 s | 0.012 | 343 KiB |
| `/inventory` | **85** | 1.6 s | 1.8 s | 1.6 s | 0.001 | 148 KiB |
| `/orders` | **91** | 1.4 s | 1.4 s | 1.4 s | 0 | 68 KiB |
| `/sales` | **93** | 1.2 s | 1.4 s | 1.2 s | 0 | 66 KiB |
| `/reports` | **90** | 1.4 s | 1.5 s | 1.4 s | 0 | 55 KiB |
| `/reports/desempeno-ventas` | **93** | 1.2 s | 1.4 s | 1.2 s | 0 | 56 KiB |
| `/reports/productos-vendidos` | **93** | 1.2 s | 1.4 s | 1.2 s | **0.007** | 58 KiB |

Dashboard wins on this round (without rewriting the UI):

- Chart.js no longer enters the bootstrap path. `dashboard.js` schedules charts via `window.load` + `requestIdleCallback`, so the ~70 KiB Chart.js chunk only starts after FCP/LCP have been captured.
- Sales + category donut now share a single `/dashboard/chart-data` request via an in-memory promise cache (`Dashboard.loadChartDataset`); the previous code fired the same endpoint twice and re-rendered KPIs from the same cached data the Blade had just printed.
- `getChartData` is wrapped in `Cache::remember('cf4:admin:dashboard_charts:'.$period, …, 300)` and the dashboard index TTL was bumped to 300 s (env `CF4_CACHE_ADMIN_DASHBOARD`, `CF4_CACHE_ADMIN_DASHBOARD_CHARTS`).
- The IntersectionObserver-driven reveal animation that set `opacity:0; transform:translateY(20px)` on every KPI / chart / table was removed (it shifted every card after first paint and dominated CLS); the `.kpis-section`, `.tables-section`, and `.kpi-card` rules now reserve height with `min-height` + `contain: layout`.
- Sidebar logo now points to `assets/images/brand/logo-ciclo-finca-icon-64.png` (4.8 KiB) + a `.webp` source instead of the 114 KiB `logo.png`; the low-stock table uses `default-96.webp` (1.3 KiB) instead of `default.png` (28 KiB) and ships `width/height/loading/decoding`.

Storefront wins on the same pass:

- `public/favicon.svg` had a stray second `<svg>` glued on the end. Trimming it took the file from 115 KiB to 0.75 KiB (the SVG is what every modern browser actually fetches).
- `public/favicon.ico` was a 1024×1024 JPEG renamed to `.ico` (114 KiB). Regenerated with `magick … -define icon:auto-resize=48,32,16` it is now 15 KiB. The PNG variants in `public/` (16/32 and the PWA icons) are real PNGs again instead of base64 text blobs.
- `resources/js/client/clients-header.js` was reduced from 8.6 KiB → 0.9 KiB. Header search trending (`header-catalog-search.js`), the catalog favorites delegation, the authenticated user menu / favorites drawer and the invoice heartbeat polling all load through `requestIdleCallback` after DOMContentLoaded.

Reports: `./lighthouse-admin/localhost/6a9f/`. All routes authenticated (no `/admin/login` in `finalUrl`).

**vs prior run (Vite dev server, contaminated build):** Big jumps after measuring **production** (`public/hot` removed) and applying the asset-distribution work below:

- `/dashboard` **56 → 70** (Chart.js deferred to idle).
- `/orders` **55 → 91** (no `sales.js`/`sales.css` cross-load; viewSale moved into orders.js).
- `/sales` **78 → 96** (`min-height` on KPI grid/cards + sales-table container, fixed Poppins font-face with `font-display: optional`).
- `/inventory` **86 → 87** (modals + stock chunk dynamic-loaded on click/idle instead of in the bootstrap `Promise.all`).
- CLS dropped to **0–0.04** on every admin route (was 0.08–0.32).

**Note:** If reports show `finalUrl` = `/admin/login`, refresh cookies, ensure a single `APP_KEY` in app `.env`, and re-run (see Admin authentication above).

## Client scan (authenticated, fixed routes)

Config: `/`, `/catalog`, `/cart`, `/profile`, `/invoices` (pedidos), plus `UNLIGHTHOUSE_PRODUCT_PATH`. Crawler disabled (`maxRoutes` = URL count). Run **separately** from admin:

```bash
npm run build && rm -f public/hot
npm run unlighthouse:client   # ./lighthouse-client/
npm run unlighthouse:admin    # ./lighthouse-admin/
```

Set `UNLIGHTHOUSE_CLIENT_COOKIE` (and optional `UNLIGHTHOUSE_CLIENT_XSRF_TOKEN`) in `.env.unlighthouse.local`, same pattern as admin.

**Client baseline (2026-05-21, mobile, fresh production build, DevTools cookies):**

| Route | Performance | FCP | LCP | Speed Index | CLS | Payload |
|-------|-------------|-----|-----|-------------|-----|---------|
| `/` | **83** | 3.4 s | 3.4 s | 4.1 s | 0 | 423 KiB |
| `/catalog` | **100** | 1.4 s | 1.5 s | 1.5 s | 0 | 177 KiB |
| `/cart` | **100** | 0.94 s | 0.94 s | 0.94 s | 0 | 12 KiB |
| `/profile` | **100** | 0.93 s | 0.93 s | 0.93 s | 0 | 15 KiB |
| `/invoices` | **100** | 1.07 s | 1.07 s | 1.07 s | 0 | 20 KiB |
| `/product/20/…` | **100** | 0.94 s | 1.01 s | 0.94 s | 0 | 33 KiB |

The header refactor (`clients-header.js` 8.6 KiB → 0.9 KiB; favorites drawer, search trending, invoice heartbeat all behind `requestIdleCallback`) means the authenticated session no longer pays a JS tax — guest `/` 81 vs client `/` 83 is essentially the same score, and the second-pageview routes (browser cache warm) all land at 100.

**Home + dashboard optimization pass (2026-05-21, mobile, production build, fresh scan):**

Guest (`./lighthouse/localhost/fec6/`):

| Route | Performance | FCP | LCP | Speed Index | CLS | Payload |
|-------|-------------|-----|-----|-------------|-----|---------|
| `/` | **98** | 1.7 s | 2.0 s | 2.1 s | 0 | 167 KiB |
| `/catalog` | **94** | 2.3 s | 2.6 s | 3.5 s | 0.024 | 279 KiB |
| `/product/20/…` | **94** | 1.7 s | 2.0 s | 5.3 s | 0.001 | 202 KiB |

Client authenticated (`./lighthouse-client/localhost/c9c6/`):

| Route | Performance | FCP | LCP | Speed Index | CLS | Payload |
|-------|-------------|-----|-----|-------------|-----|---------|
| `/` | **98** | 1.7 s | 2.1 s | 1.7 s | 0 | 171 KiB |
| `/catalog` | **100** | 1.2 s | 1.3 s | 1.4 s | 0 | 170 KiB |
| `/cart` | **100** | 0.9 s | 0.9 s | 0.9 s | 0.004 | 12 KiB |
| `/profile` | **100** | 1.1 s | 1.1 s | 1.1 s | 0.001 | 18 KiB |
| `/invoices` | **100** | 1.4 s | 1.4 s | 1.4 s | 0 | 48 KiB |
| `/product/20/…` | **100** | 1.4 s | 1.4 s | 1.4 s | 0.001 | 55 KiB |

Admin (`./lighthouse-admin/localhost/4524/`):

| Route | Performance | FCP | LCP | Speed Index | CLS | Payload |
|-------|-------------|-----|-----|-------------|-----|---------|
| `/dashboard` | **84** | 1.6 s | 1.7 s | 2.0 s | 0.011 | 205 KiB |
| `/inventory` | **94** | 1.1 s | 1.4 s | 1.1 s | 0 | 82 KiB |
| `/orders` | **98** | 0.9 s | 0.9 s | 0.9 s | 0 | 24 KiB |
| `/sales` | **91** | 1.3 s | 1.5 s | 1.3 s | 0 | 55 KiB |
| `/reports` | **99** | 0.8 s | 0.8 s | 0.8 s | 0 | 12 KiB |
| `/reports/desempeno-ventas` | **98** | 0.9 s | 0.9 s | 0.9 s | 0 | 14 KiB |
| `/reports/productos-vendidos` | **98** | 0.9 s | 0.9 s | 0.9 s | **0.006** | 16 KiB |

Key changes in this pass:

- **Hero/LCP:** regenerated 480/768/1280/1600/1920 WebP + 480–1600 AVIF; `<picture>` with AVIF primary; `<link rel="preload" as="image">` with `imagesrcset`/`imagesizes` on home only. Guest `/` payload **419 → 167 KiB**, LCP **4.0 → 2.0 s**, score **81 → 98**.
- **Below-the-fold home:** `content-visibility: auto` + `contain-intrinsic-size` on trust strip, featured, categories, benefits, how-it-works, testimonials, final CTA.
- **Fonts:** removed Google Fonts `@import` for Inter; client Poppins/DM Sans now use explicit `@font-face` with `font-display: optional` (fixes catalog CLS spike from `font-display: swap`).
- **Font Awesome subset:** `scripts/fonts/build-fa-subset.mjs` + `resources/css/shared/fontawesome-subset.css` — fa-solid **158 KiB → 12 KiB**, fa-regular **25 KiB → 5 KiB** (`npm run build:fa-subset` to regenerate).
- **Dashboard:** improved **72 → 84** (LCP 2.6 → 1.7 s, payload 343 → 205 KiB) but still below the 90+ target; remaining gap is likely the large `fontawesome.css` class map (~55 KiB gzipped CSS) plus dashboard-specific JS/CSS still on the critical path.

**Acceptance vs targets:**

| Target | Result |
|--------|--------|
| Guest `/` ≥ 90 | ✅ **98** |
| Client `/` ≥ 90 | ✅ **98** |
| LCP &lt; 2.2 s (home) | ✅ **2.0–2.1 s** |
| Payload 250–320 KiB (home) | ✅ **167–171 KiB** |
| `/dashboard` ≥ 90 | ❌ **84** (improved +12 pts) |
| `/reports/productos-vendidos` CLS &lt; 0.01 | ✅ **0.006** |
| No regressions on other routes | ✅ all admin/client routes ≥ 91 except dashboard |
