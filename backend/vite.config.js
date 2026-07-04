import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { fileURLToPath, URL } from "node:url";

const vitePort = Number(process.env.VITE_PORT) || 5173;

// =======================
// ADMIN ASSETS
// =======================
const adminAssets = [
    // TypeScript entrypoints
    "resources/ts/admin/sales/invoice-print.ts",

    // CSS
    "resources/css/admin/fonts.css",
    "resources/css/admin/fontawesome.css",
    "resources/css/admin/products/products-pdf.css",
    "resources/css/admin/sales/invoice-document.css",
    "resources/css/admin/dashboard/dashboard-pdf.css",
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
// Only technical Blade residual entries remain in the backend build.
const clientAssets = [
    "resources/ts/client/invoices-page.ts",

    "resources/css/client/variables-reset.css",
    "resources/css/client/fonts.css",
    "resources/css/client/fontawesome.css",
    "resources/css/client/invoice-print.css",
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
            laravel({
                detectTls: false,
                input: [...adminAssets, ...clientAssets, ...errorAssets],
                refresh: true,
            }),
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
