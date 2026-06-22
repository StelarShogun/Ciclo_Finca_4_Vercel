# Vercel deployment notes

This project can run a demo deployment on Vercel through the serverless PHP entrypoint in `api/index.php`.
The primary production path remains Render/Docker.

## Required Vercel environment variables

```dotenv
APP_ENV=production
APP_PLATFORM=vercel
APP_KEY=
APP_URL=https://your-project.vercel.app
FRONTEND_URL=https://your-project.vercel.app

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

FILESYSTEM_DISK=vercel_blob
MEDIA_DISK=vercel_blob
BLOB_READ_WRITE_TOKEN=
VERCEL_BLOB_PUBLIC_URL=https://your-store.public.blob.vercel-storage.com
VERCEL_BLOB_PREFIX=

QSTASH_TOKEN=
DEPLOY_SECRET=

QUEUE_CONNECTION=sync
SESSION_DRIVER=database
CACHE_STORE=database
PULSE_ENABLED=false
```

## Runtime behavior

- `vercel.json` routes all dynamic requests to `api/index.php` and serves Vite assets from `public/build`.
- Product media, gallery images, avatar uploads, and catalog-import temporary files use the `vercel_blob` disk when `APP_PLATFORM=vercel`.
- Catalog import and media-conversion jobs are published to QStash endpoints protected by `DEPLOY_SECRET`.
- Vercel Cron calls `/internal/vercel/cron/scheduler`; the endpoint accepts Vercel's cron user agent or `?key=<DEPLOY_SECRET>`.

## Pre-deploy checks

Run the regular project gate before treating the deployment as merge-ready:

```bash
./scripts/ci-check-docker.sh
```

For a Vercel-only smoke, verify:

```bash
npm run build
composer run lint
composer run phpstan
```
