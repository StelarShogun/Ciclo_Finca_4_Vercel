import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

const vitePort = Number(process.env.VITE_PORT) || 5173;

// =======================
// ADMIN ASSETS
// =======================
const adminAssets = [
    // JS
    "resources/js/admin/shell.js",
    "resources/js/admin/dashboard/dashboard.js",
    "resources/js/admin/inventory/inventory-entry.js",
    "resources/js/admin/sales/sales.js",
    'resources/js/admin/sales/reports-by-category.js',
    "resources/js/admin/orders/orders.js",
    "resources/js/admin/orders/supplier-orders.js",
    "resources/js/admin/orders/supplier-order-create.js",
    "resources/js/admin/orders/xml-deviation-review.js",
    "resources/js/admin/orders/detail-supplier-page.js",
    "resources/js/admin/suppliers/suppliers.js",
    "resources/js/admin/login/login.js",
    "resources/js/admin/users/clients.js",
    "resources/js/admin/brand/brand.js",
    "resources/js/admin/product-classifications/index.js",
    "resources/js/admin/product-classifications/edit.js",
    "resources/js/admin/classifications/catalog.js",
    "resources/js/admin/classifications/forms.js",
    "resources/js/admin/reports/product-sales.js",
    "resources/js/admin/reports/sales-performance.js",
    "resources/js/admin/reports/exports-modal.js",
    "resources/js/admin/reports/inventory-movements.js",        
    "resources/js/admin/reports/client-purchase-history.js",  
    "resources/js/admin/reports/client-purchase-client-show.js",

    // CSS
    "resources/css/admin/products/inventory.css",
    "resources/css/admin/products/products-pdf.css",
    "resources/css/admin/sales/sales.css",
    "resources/css/admin/sales/invoice-document.css",
    "resources/css/admin/orders/orders.css",
    "resources/css/admin/orders/supplier-order-create.css",
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
    "resources/js/errors/scenes.js",
    "resources/css/errors/state-card.css",
    "resources/css/errors/404-page.css",
];

// =======================
// CLIENT ASSETS
// =======================
const clientAssets = [
    // JS
    "resources/js/client/checkout-copy.js",
    "resources/js/client/clients-header.js",
    "resources/js/client/clients-home.js",
    "resources/js/client/clients-catalog.js",
    "resources/js/client/clients-cart.js",
    "resources/js/client/clients-product.js",
    "resources/js/client/clients-users.js",
    "resources/js/client/invoices-page.js",
    "resources/js/client/auth-welcome-toast.js",
    "resources/js/client/client-flash.js",
    "resources/js/client/recovery-success-modal.js",
    "resources/js/client/register-validation-errors.js",
    "resources/js/client/invoices-review-modal.js",

    // CSS
    "resources/css/client/fonts.css",
    "resources/css/client/fontawesome.css",
    "resources/css/client/variables-reset.css",
    "resources/css/client/header.css",
    "resources/css/client/footer.css",
    "resources/css/client/clients-page.css",
    "resources/css/client/clients-users.css",
    "resources/css/client/legal-pages.css",
];

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const appUrl = env.APP_URL || "http://localhost:8080";

    return {
        // Keep Vite cache outside node_modules to avoid permission issues on WSL mounts.
        cacheDir: ".vite-cache",
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
            // FA subset lives in public/fonts; @font-face uses /fonts/... which the browser
            // resolves against the Vite origin when CSS is served from npm run dev (404 without proxy).
            proxy: {
                "/fonts": {
                    target: appUrl,
                    changeOrigin: true,
                },
            },
        },
    };
});