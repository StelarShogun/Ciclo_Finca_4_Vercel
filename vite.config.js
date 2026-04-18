import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

const vitePort = Number(process.env.VITE_PORT) || 5173;

// =======================
// ADMIN ASSETS
// =======================
const adminAssets = [
    // JS
    "resources/js/admin/dashboard/dashboard.js",
    "resources/js/admin/inventory/inventory.js",
    "resources/js/admin/sales/sales.js",
    'resources/js/admin/sales/reports-by-category.js',
    "resources/js/admin/orders/orders.js",
    "resources/js/admin/orders/supplier-orders.js",
    "resources/js/admin/orders/supplier-order-create.js",
    "resources/js/admin/suppliers/suppliers.js",
    "resources/js/admin/login/login.js",
    "resources/js/admin/users/clients.js",
    "resources/js/admin/brand/brand.js",
    "resources/js/admin/product-classifications/edit.js",
    "resources/js/admin/classifications/catalog.js",
    "resources/js/admin/reports/product-sales.js",
    "resources/js/admin/reports/sales-performance.js",
    "resources/js/admin/reports/client-purchase-history.js",

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
];

// =======================
// CLIENT ASSETS
// =======================
const clientAssets = [
    // JS
    "resources/js/client/clients-page.js",
    "resources/js/client/clients-users.js",

    // CSS
    "resources/css/client/variables-reset.css",
    "resources/css/client/header.css",
    "resources/css/client/footer.css",
    "resources/css/client/clients-page.css",
    "resources/css/client/clients-users.css",
];

export default defineConfig({
    plugins: [
        laravel({
            detectTls: false,
            input: [...adminAssets, ...clientAssets],
            refresh: true,
        }),
    ],
    server: {
        host: "0.0.0.0", // Para Docker
        port: vitePort,
        strictPort: true,
        hmr: {
            host: "localhost", // Navegador accede desde tu máquina
        },
    },
});
