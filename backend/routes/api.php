<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\BrandController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\ClassificationCatalogController;
use App\Http\Controllers\Api\V1\Admin\ClientController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\InventoryController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\ProductClassificationController;
use App\Http\Controllers\Api\V1\Admin\ProductGalleryController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SaleController;
use App\Http\Controllers\Api\V1\Admin\SupplierController;
use App\Http\Controllers\Api\V1\Admin\SupplierOrderController;
use App\Http\Controllers\Api\V1\Client\CartController as ClientCartController;
use App\Http\Controllers\Api\V1\Client\CatalogController as ClientCatalogController;
use App\Http\Controllers\Api\V1\Client\FavoriteController as ClientFavoriteController;
use App\Http\Controllers\Api\V1\Client\HomeController as ClientHomeController;
use App\Http\Controllers\Api\V1\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Api\V1\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Api\V1\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\V1\Client\ProductController as ClientProductController;
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\ClientAuthController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — consumida por el frontend Next.js (Sanctum cookie / SPA).
|--------------------------------------------------------------------------
| Prefijo real: /api/v1. La sesión viaja por cookie (statefulApi en
| bootstrap/app.php). El SPA pide GET /sanctum/csrf-cookie antes de POST.
| Los módulos admin/client se irán llenando por bloque, delegando en los
| Actions/Services existentes.
*/

Route::prefix('v1')->group(function (): void {
    // --- Autenticación ---
    Route::prefix('auth')->group(function (): void {
        // Cliente
        Route::post('/login', [ClientAuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/logout', [ClientAuthController::class, 'logout'])->middleware('auth:clients');
        Route::post('/register', [ClientAuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/verify', [ClientAuthController::class, 'verify'])->middleware('throttle:10,1');
        Route::post('/verify/resend', [ClientAuthController::class, 'resendCode'])->middleware('throttle:3,1');
        Route::post('/recovery', [ClientAuthController::class, 'recoverySend'])->middleware('throttle:3,1');
        Route::post('/recovery/verify', [ClientAuthController::class, 'recoveryVerify'])->middleware('throttle:10,1');
        Route::post('/recovery/reset', [ClientAuthController::class, 'recoveryReset'])->middleware('throttle:5,1');

        // Admin
        Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin');
    });

    // Identidad del usuario autenticado (cualquiera de los dos guards).
    Route::get('/me', [MeController::class, 'show'])->middleware('auth:admin,clients');

    // --- Módulos admin (se llenan en Bloque 4) ---
    Route::prefix('admin')->middleware('auth:admin')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/form-options', [ProductController::class, 'formOptions']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product');
        Route::put('/products/{product}', [ProductController::class, 'update'])->whereNumber('product');
        Route::post('/products/{product}/activate', [ProductController::class, 'activate'])->whereNumber('product');
        Route::post('/products/{product}/deactivate', [ProductController::class, 'deactivate'])->whereNumber('product');
        Route::post('/products/{product}/featured', [ProductController::class, 'toggleFeatured'])->whereNumber('product');
        Route::delete('/products/{product}/force', [ProductController::class, 'forceDelete'])->whereNumber('product');

        // Galería de imágenes
        Route::get('/products/{product}/gallery', [ProductGalleryController::class, 'index'])->whereNumber('product');
        Route::post('/products/{product}/gallery', [ProductGalleryController::class, 'store'])->whereNumber('product');
        Route::post('/products/{product}/gallery/{media}/promote', [ProductGalleryController::class, 'promote'])->whereNumber('product')->whereNumber('media');
        Route::delete('/products/{product}/gallery/{media}', [ProductGalleryController::class, 'destroy'])->whereNumber('product')->whereNumber('media');

        // Variantes (productos existentes enlazados)
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->whereNumber('product');
        Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->whereNumber('product')->whereNumber('variant');
        Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->whereNumber('product')->whereNumber('variant');

        // Clasificaciones (por categoría, un valor por dimensión)
        Route::get('/products/{product}/classifications', [ProductClassificationController::class, 'index'])->whereNumber('product');
        Route::put('/products/{product}/classifications', [ProductClassificationController::class, 'update'])->whereNumber('product');

        // Marcas
        Route::get('/brands', [BrandController::class, 'index']);
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->whereNumber('brand');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->whereNumber('brand');

        // Categorías (jerarquía padre/subcategoría)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories/parent', [CategoryController::class, 'storeParent']);
        Route::post('/categories/subcategory', [CategoryController::class, 'storeSubcategory']);

        // Ventas (historial + ciclo de vida; stock con transacciones en las Actions)
        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/heartbeat', [SaleController::class, 'heartbeat']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale');
        Route::put('/sales/{sale}', [SaleController::class, 'update'])->whereNumber('sale');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->whereNumber('sale');
        Route::post('/sales/{sale}/ready', [SaleController::class, 'markReady'])->whereNumber('sale');
        Route::post('/sales/{sale}/complete', [SaleController::class, 'complete'])->whereNumber('sale');
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->whereNumber('sale');
        Route::post('/sales/{sale}/return', [SaleController::class, 'returnSale'])->whereNumber('sale');

        // Encargos (pedidos del carrito web; ciclo de vida via /sales)
        Route::get('/orders', [OrderController::class, 'index']);

        // Pedidos a proveedores (recepción con stock de entrada en las Actions)
        Route::get('/supplier-orders', [SupplierOrderController::class, 'index']);
        Route::get('/supplier-orders/search-products', [SupplierOrderController::class, 'searchProducts']);
        Route::post('/supplier-orders', [SupplierOrderController::class, 'store']);
        Route::get('/supplier-orders/{order}', [SupplierOrderController::class, 'show'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/state', [SupplierOrderController::class, 'updateState'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/close-partial', [SupplierOrderController::class, 'closePartial'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/receive', [SupplierOrderController::class, 'receive'])->whereNumber('order');

        // Proveedores (CRUD)
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->whereNumber('supplier');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->whereNumber('supplier');

        // Inventario (stock + ajustes manuales con movimiento + historial)
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::post('/inventory/{product}/add', [InventoryController::class, 'addStock'])->whereNumber('product');
        Route::post('/inventory/{product}/remove', [InventoryController::class, 'removeStock'])->whereNumber('product');
        Route::get('/inventory/{product}/movements', [InventoryController::class, 'movements'])->whereNumber('product');
        Route::post('/inventory/import', [InventoryController::class, 'import']);
        Route::get('/inventory/import/{importId}/progress', [InventoryController::class, 'importProgress']);

        // Clientes (lista + historial de compras + bloqueo/desbloqueo)
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show'])->whereNumber('client');
        Route::post('/clients/{client}/ban', [ClientController::class, 'ban'])->whereNumber('client');
        Route::post('/clients/{client}/unban', [ClientController::class, 'unban'])->whereNumber('client');

        // Auditoría (bitácora, solo lectura)
        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        // Catálogo de clasificaciones ("Opciones por tipo": atributos y valores por subcategoría)
        Route::get('/classification-catalog', [ClassificationCatalogController::class, 'index']);
        Route::get('/classification-catalog/{category}', [ClassificationCatalogController::class, 'show'])->whereNumber('category');
        Route::post('/classification-catalog/{category}/dimensions', [ClassificationCatalogController::class, 'storeDimension'])->whereNumber('category');
        Route::put('/classification-catalog/dimensions/{dimension}', [ClassificationCatalogController::class, 'updateDimension'])->whereNumber('dimension');
        Route::delete('/classification-catalog/dimensions/{dimension}', [ClassificationCatalogController::class, 'destroyDimension'])->whereNumber('dimension');
        Route::post('/classification-catalog/dimensions/{dimensionId}/restore', [ClassificationCatalogController::class, 'restoreDimension'])->whereNumber('dimensionId');
        Route::get('/classification-catalog/dimensions/{dimension}/values', [ClassificationCatalogController::class, 'values'])->whereNumber('dimension');
        Route::post('/classification-catalog/dimensions/{dimension}/values', [ClassificationCatalogController::class, 'storeValue'])->whereNumber('dimension');
        Route::put('/classification-catalog/values/{value}', [ClassificationCatalogController::class, 'updateValue'])->whereNumber('value');
        Route::delete('/classification-catalog/values/{value}', [ClassificationCatalogController::class, 'destroyValue'])->whereNumber('value');
        Route::post('/classification-catalog/values/{valueId}/restore', [ClassificationCatalogController::class, 'restoreValue'])->whereNumber('valueId');

        // Reportes (previsualización JSON; PDF/Excel/CSV se descargan desde rutas web)
        Route::get('/reports/sales-performance', [ReportController::class, 'salesPerformance']);
        Route::get('/reports/product-sales', [ReportController::class, 'productSales']);
        Route::get('/reports/category-sales', [ReportController::class, 'categorySales']);
        Route::get('/reports/client-purchases', [ReportController::class, 'clientPurchases']);
        Route::get('/reports/catalog-search', [ReportController::class, 'catalogSearch']);
        Route::get('/reports/inventory-movements', [ReportController::class, 'inventoryMovements']);
        Route::get('/reports/exports-config', [ReportController::class, 'exportsConfig']);
    });

    // --- Tienda pública (sin auth; el guard clients se consulta para favoritos) ---
    Route::get('/home', [ClientHomeController::class, 'index']);
    Route::get('/catalog', [ClientCatalogController::class, 'index']);
    Route::get('/catalog/heartbeat', [ClientCatalogController::class, 'heartbeat']);
    Route::get('/catalog/suggestions', [ClientCatalogController::class, 'suggestions']);
    Route::get('/products/{product}', [ClientProductController::class, 'show'])->whereNumber('product');

    // Carrito (invitado por sesión o cliente por DB; CartManager decide)
    Route::get('/cart', [ClientCartController::class, 'index']);
    Route::post('/cart/add', [ClientCartController::class, 'add']);
    Route::put('/cart/update', [ClientCartController::class, 'update']);
    Route::delete('/cart/remove/{id}', [ClientCartController::class, 'remove'])->whereNumber('id');
    Route::delete('/cart/clear', [ClientCartController::class, 'clear']);

    // --- Cliente autenticado ---
    Route::middleware('auth:clients')->group(function (): void {
        Route::post('/cart/checkout', [ClientCartController::class, 'checkout']);

        // Reseñas de producto (valida compra previa)
        Route::post('/products/{product}/reviews', [ClientProductController::class, 'storeReview'])->whereNumber('product');

        // Favoritos (único por user+product; toggle idempotente)
        Route::get('/favorites', [ClientFavoriteController::class, 'index']);
        Route::post('/favorites/toggle', [ClientFavoriteController::class, 'toggle']);

        // Facturas (pertenencia por InvoicePolicy)
        Route::get('/invoices', [ClientInvoiceController::class, 'index']);
        Route::get('/invoices/{sale}', [ClientInvoiceController::class, 'show'])->whereNumber('sale');

        // Notificaciones (solo las del propio cliente)
        Route::get('/notifications', [ClientNotificationController::class, 'index']);
        Route::get('/notifications/heartbeat', [ClientNotificationController::class, 'heartbeat']);
        Route::post('/notifications/{notification}/read', [ClientNotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [ClientNotificationController::class, 'markAllRead']);

        // Perfil
        Route::get('/profile', [ClientProfileController::class, 'show']);
        Route::put('/profile', [ClientProfileController::class, 'update']);
        Route::put('/profile/password', [ClientProfileController::class, 'updatePassword']);
        Route::post('/profile/avatar', [ClientProfileController::class, 'updateAvatar']);
    });
});
