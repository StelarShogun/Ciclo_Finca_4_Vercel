import { defineConfig } from "vite";
import inertia from "@inertiajs/vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import { fileURLToPath, URL } from "node:url";

const vitePort = Number(process.env.VITE_PORT) || 5173;

// =======================
// SHARED ASSETS
// =======================
const sharedAssets = [
    "resources/ts/app.tsx",
    "resources/ts/shared/theme-toggle.ts",
];

// =======================
// ADMIN ASSETS
// =======================
const adminAssets = [
    // TypeScript entrypoints
    "resources/ts/admin/shell.ts",
    "resources/ts/admin/dashboard/dashboard.ts",
    "resources/ts/admin/sales/sales.ts",
    "resources/ts/admin/orders/supplier-orders.ts",
    "resources/ts/admin/orders/detail-supplier-page.ts",
    "resources/ts/admin/reports/product-sales.ts",
    "resources/ts/admin/reports/sales-performance.ts",

    // CSS
    "resources/css/admin/products/inventory.css",
    "resources/css/admin/products/products-pdf.css",
    "resources/css/admin/sales/sales.css",
    "resources/css/admin/sales/invoice-document.css",
    "resources/css/admin/orders/orders.css",
    "resources/css/admin/orders/supplier-order-detail.css",
    "resources/css/admin/suppliers/suppliers.css",
    "resources/css/admin/dashboard/dashboard.css",
    "resources/css/admin/dashboard/dashboard-pdf.css",
    "resources/css/admin/users/clients.css",
    "resources/css/admin/brands/brand.css",
    "resources/css/admin/login/login.css",
    "resources/css/admin/reports/reports-hub.css",
    "resources/css/admin/reports/exports.css",
    "resources/css/admin/reports/product-sales.css",
    "resources/css/admin/reports/sales-performance.css",
    "resources/css/admin/reports/client-purchase-history.css",
    "resources/css/admin/reports/audit-log.css",
    "resources/css/admin/shell-base.css",
    "resources/css/admin/components/page-header.css",
    "resources/css/admin/components/filters.css",
];

// =======================
// ERROR PAGE ASSETS
// =======================
const errorAssets = [
    "resources/ts/errors/scenes.ts",
    "resources/css/errors/state-card.css",
    "resources/css/errors/404-page.css",
];

// =======================
// CLIENT ASSETS
// =======================
// Inertia storefront loads via app.tsx + dynamic import(). Only Blade residual entries here.
const clientAssets = [
    "resources/ts/client/invoices-page.ts",

    // CSS
    "resources/css/client/fonts.css",
    "resources/css/client/fontawesome.css",
    "resources/css/client/variables-reset.css",
    "resources/css/client/header.css",
    "resources/css/client/footer.css",
    "resources/css/client/clients-page.css",
    "resources/css/client/clients-home.css",
    "resources/css/client/clients-users.css",
    "resources/css/client/invoice-print.css",
    "resources/css/client/legal-pages.css",
    "resources/css/client/product-badges.css",
    "resources/css/client/product-detail.css",
];

export default defineConfig(({ command }) => {
    const isDevServer = command === "serve";

    return {
        // Keep Vite cache outside node_modules to avoid permission issues on WSL mounts.
        cacheDir: ".vite-cache",
        // Dev only: serve /fonts from public/ (FA subset). Production build must keep
        // publicDir false (laravel-vite-plugin default) so Vite does not copy public/
        // into public/build — CI has no storage:link and public/storage would ENOENT.
        publicDir: isDevServer ? "public" : false,
        plugins: [
            react(),
            laravel({
                detectTls: false,
                input: [...sharedAssets, ...adminAssets, ...clientAssets, ...errorAssets],
                refresh: true,
            }),
            inertia(),
        ],
        server: {
            host: "0.0.0.0",
            port: vitePort,
            strictPort: true,
            hmr: {
                host: "localhost",
            },
        },
        resolve: {
            alias: {
                "@": fileURLToPath(new URL("./resources/ts", import.meta.url)),
            },
        },
    };
});
