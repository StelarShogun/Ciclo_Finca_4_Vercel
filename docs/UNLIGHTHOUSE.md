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

Reports: `./lighthouse/localhost/*/reports/**/lighthouse.json` (latest run: `fec6/`). Dashboard URL is printed by the CLI (default `http://localhost:5678/`).

**Still open (optional):** subset or self-host Font Awesome; further trim `render-blocking-insight` on catalog/product; hero `image-delivery` ~66 KiB on mobile viewport.

**Docker:** [`Dockerfile`](../Dockerfile) builds GD with WebP (`libwebp-dev`). After image rebuild, run `php artisan media-library:regenerate --only=webp_480,webp_768,webp_1920` inside the app container.

## Admin scan (separate profile)

Requires `UNLIGHTHOUSE_ADMIN_COOKIE` in `.env.unlighthouse.local`. Without it, `npm run unlighthouse:admin` stops with instructions (see [Authentication → Admin](#admin)).

After a successful run, open reports under `./lighthouse-admin/localhost/*/reports/`. Compare admin scores separately from guest — inventory and reports pages load heavy JS bundles.

**Baseline admin scan (2026-05-21, desktop, DevTools session cookie + `XSRF-TOKEN`, `UNLIGHTHOUSE_NO_CACHE=1`, production build):**

| Route | Performance | FCP | Transfer (audit) | `finalUrl` |
|-------|-------------|-----|------------------|------------|
| `/dashboard` | **56** | 4.7 s | 1,419 KiB | `/dashboard` |
| `/inventory` | **86** | 1.5 s | 1,198 KiB | `/inventory` |
| `/orders` | **55** | 2.7 s | 334 KiB | `/orders` |
| `/sales` | **78** | 1.1 s | 9 KiB (HTML shell) | `/sales` |
| `/reports` | **88** | 1.5 s | 64 KiB | `/reports` |
| `/reports/desempeno-ventas` | **83** | 1.7 s | 120 KiB | `/reports/desempeno-ventas` |
| `/reports/productos-vendidos` | **83** | 1.7 s | 99 KiB | `/reports/productos-vendidos` |

Reports: `./lighthouse-admin/localhost/6a9f/` (CLI dashboard: `http://localhost:5678/` during scan). All routes authenticated (no `/admin/login` in `finalUrl`).

**vs earlier same-day run (`25c7/`):** `/inventory` **82 → 86** (spinner fix + code-split); `/dashboard` **66 → 56** and `/orders` **84 → 55** regressed — prioritize Chart.js/dashboard bundle and orders page assets. `/inventory` transfer is higher now because the page fully loads (previously stuck on loading overlay).

**Note:** If reports show `finalUrl` = `/admin/login`, refresh cookies, ensure a single `APP_KEY` in app `.env`, and re-run (see Admin authentication above).

## Client scan (authenticated, fixed routes)

Config: `/`, `/catalog`, `/cart`, `/profile`, `/invoices` (pedidos), plus `UNLIGHTHOUSE_PRODUCT_PATH`. Crawler disabled (`maxRoutes` = URL count). Run **separately** from admin:

```bash
npm run build && rm -f public/hot
npm run unlighthouse:client   # ./lighthouse-client/
npm run unlighthouse:admin    # ./lighthouse-admin/
```

Set `UNLIGHTHOUSE_CLIENT_COOKIE` (and optional `UNLIGHTHOUSE_CLIENT_XSRF_TOKEN`) in `.env.unlighthouse.local`, same pattern as admin.
