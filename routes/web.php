<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClientPurchaseHistoryController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReportsRegistryExportController;
use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminOrderSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassificationCatalogController;
use App\Http\Controllers\ClientCatalogProductSuggestionsController;
use App\Http\Controllers\ClientPageController;
use App\Http\Controllers\ClientUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteProductController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\ProductClassificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Restricts deploy helper routes outside local and testing environments.
$assertDeployHelperAllowed = function (Request $request): void {
    if (app()->environment('local', 'testing')) {
        return;
    }

    $secret = (string) config('app.deploy_secret', '');

    // Hide the endpoint when no deploy secret is configured.
    if ($secret === '') {
        abort(404);
    }

    // Compare the provided secret using a timing-safe check.
    if (! hash_equals($secret, (string) $request->query('key', ''))) {
        abort(404);
    }
};

// Protected deploy utilities.
Route::get('/run-migrations', function (Request $request) use ($assertDeployHelperAllowed) {
    $assertDeployHelperAllowed($request);

    try {
        $exitCode = Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        if ($exitCode !== 0) {
            return response(
                '❌ migrate exited with code '.$exitCode.'<br><pre>'.e($output).'</pre>',
                500
            );
        }

        return '✅ Migrations executed successfully:<br><pre>'.e($output).'</pre>';
    } catch (Throwable $e) {
        return response('❌ Error running migrations: '.e($e->getMessage()), 500);
    }
});

// Runs seeders with optional class-based execution.
Route::get('/run-seeders/{class?}', function (Request $request, ?string $class = null) use ($assertDeployHelperAllowed) {
    $assertDeployHelperAllowed($request);

    // Restrict execution to valid seeder class names.
    if ($class !== null && $class !== '') {
        if (! preg_match('/^Database\\\\Seeders\\\\[A-Za-z0-9_]+$/', $class)) {
            return response('❌ Invalid seeder class name.', 400);
        }
    }

    try {
        $params = ['--force' => true];

        if ($class) {
            $params['--class'] = $class;
        }

        $exitCode = Artisan::call('db:seed', $params);
        $output = Artisan::output();

        if ($exitCode !== 0) {
            return response(
                '❌ db:seed exited with code '.$exitCode.'<br><pre>'.e($output).'</pre>',
                500
            );
        }

        return '✅ Seeder executed:<br><pre>'.e($output).'</pre>';
    } catch (Throwable $e) {
        return response('❌ Error: '.e($e->getMessage()), 500);
    }
})->where('class', '[A-Za-z0-9\\\\_]+');

// Admin authentication routes.
Route::get('/admin/login', [AdminUserController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminUserController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminUserController::class, 'logout'])->name('admin.logout');

// Admin-only routes.
Route::middleware(['admin.only', 'prevent.direct', 'audit.sensitive.module'])->group(function () {

    // Dashboard routes.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

    // Report routes.
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/exportaciones', [ReportsController::class, 'exports'])->name('admin.reports.exports');

    // Limit export downloads to the supported registry slugs.
    Route::get('/reports/exportaciones/descarga/{slug}', [ReportsRegistryExportController::class, 'download'])
        ->where('slug', 'proveedores|marcas|pedidos-proveedores|usuarios|pedidos-clientes')
        ->name('admin.reports.exports.registry');

    Route::get('/reports/desempeno-ventas', [ReportsController::class, 'salesPerformance'])->name('admin.reports.sales-performance');
    Route::get('/reports/ventas/range', [ReportsController::class, 'salesPerformanceRange'])->name('admin.reports.sales.range');
    Route::get('/reports/ventas/metrics', [ReportsController::class, 'salesPerformanceMetrics'])->name('admin.reports.sales.metrics');
    Route::get('/reports/productos-vendidos', [ReportsController::class, 'productSales'])->name('admin.reports.product-sales');
    Route::get('/reports/productos-vendidos/table', [ReportsController::class, 'productSalesTable'])->name('admin.reports.product-sales.table');
    Route::get('/reports/productos-vendidos/pdf', [ReportsController::class, 'productSalesPdf'])->name('admin.reports.product-sales.pdf');
    Route::get('/reports/productos-vendidos/excel', [ReportsController::class, 'productSalesExcel'])
        ->name('admin.reports.product-sales.excel');
    Route::get('/sales/reports/by-category', [ReportsController::class, 'byCategory'])->name('sales.reports.byCategory');

    // Inventory movement routes.
    Route::prefix('inventory/movements')->name('admin.inventory.movements.')->group(function () {
        Route::get('/', [InventoryMovementController::class, 'index'])->name('index');
        Route::get('/{productId}', [InventoryMovementController::class, 'show'])->name('show');
        Route::get('/{productId}/json', [InventoryMovementController::class, 'json'])->name('json');
    });

    // Client purchase history routes.
    Route::get('/reports/client-purchases', [ClientPurchaseHistoryController::class, 'index'])->name('admin.reports.client-purchases');
    Route::get('/reports/client-purchases/table', [ClientPurchaseHistoryController::class, 'table'])->name('admin.reports.client-purchases.table');
    Route::get('/reports/client-purchases/{client}', [ClientPurchaseHistoryController::class, 'show'])
        ->whereNumber('client')
        ->name('admin.reports.client-purchases.show');
    Route::get('/reports/client-purchases/{client}/orders', [ClientPurchaseHistoryController::class, 'clientOrders'])
        ->whereNumber('client')
        ->name('admin.reports.client-purchases.orders');
    Route::get('/reports/audit-log', [AuditLogController::class, 'index'])->name('admin.reports.audit-log');

    // Inventory routes.
    Route::get('/inventory', [ProductController::class, 'inventory'])->name('inventory');

    // Classification catalog routes.
    Route::get('/classifications/catalog', [ClassificationCatalogController::class, 'index'])->name('admin.classifications.catalog.index');
    Route::get('/classifications/catalog/{category}/options', [ClassificationCatalogController::class, 'optionsForCategory'])->name('admin.classifications.catalog.options');
    Route::get('/classifications/catalog/{category}', [ClassificationCatalogController::class, 'showCategory'])->name('admin.classifications.catalog.show');
    Route::post('/classifications/catalog/{category}/dimensions', [ClassificationCatalogController::class, 'storeDimension'])->name('admin.classifications.dimensions.store');
    Route::get('/classifications/dimensions/{dimension}/edit', [ClassificationCatalogController::class, 'editDimension'])->name('admin.classifications.dimensions.edit');
    Route::put('/classifications/dimensions/{dimension}', [ClassificationCatalogController::class, 'updateDimension'])->name('admin.classifications.dimensions.update');
    Route::delete('/classifications/dimensions/{dimension}', [ClassificationCatalogController::class, 'destroyDimension'])->name('admin.classifications.dimensions.destroy');
    Route::post('/classifications/dimensions/{dimension}/restore', [ClassificationCatalogController::class, 'restoreDimension'])->name('admin.classifications.dimensions.restore');
    Route::get('/classifications/dimensions/{dimension}/values', [ClassificationCatalogController::class, 'indexValues'])->name('admin.classifications.values.index');
    Route::post('/classifications/dimensions/{dimension}/values', [ClassificationCatalogController::class, 'storeValue'])->name('admin.classifications.values.store');
    Route::get('/classifications/values/{value}/edit', [ClassificationCatalogController::class, 'editValue'])->name('admin.classifications.values.edit');
    Route::put('/classifications/values/{value}', [ClassificationCatalogController::class, 'updateValue'])->name('admin.classifications.values.update');
    Route::delete('/classifications/values/{value}', [ClassificationCatalogController::class, 'destroyValue'])->name('admin.classifications.values.destroy');
    Route::post('/classifications/values/{value}/restore', [ClassificationCatalogController::class, 'restoreValue'])->name('admin.classifications.values.restore');

    // Product classification assignment routes.
    Route::get('/product-classifications', [ProductClassificationController::class, 'index'])->name('admin.product-classifications.index');
    Route::get('/products/{product}/classifications/edit', [ProductClassificationController::class, 'edit'])->name('admin.products.classifications.edit');
    Route::put('/products/{product}/classifications', [ProductClassificationController::class, 'update'])->name('admin.products.classifications.update');

    // Product variant management routes.
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])
        ->whereNumber('product')
        ->name('admin.products.variants.store');
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
        ->whereNumber('product')
        ->whereNumber('variant')
        ->name('admin.products.variants.destroy');

    // Featured product toggle route.
    Route::post('/products/{id}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');

    // Product media management routes.
    Route::post('/products/{id}/gallery/{mediaId}/promote', [ProductController::class, 'promoteToMain'])->name('products.gallery.promote');
    Route::delete('/products/{id}/gallery/{mediaId}', [ProductController::class, 'removeGalleryImage'])->name('products.gallery.destroy');
    Route::resource('products', ProductController::class)->except(['create']);
    Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.force-delete');
    Route::get('/inventory/export/{format?}', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/inventory/add-manual/{id}', [ProductController::class, 'addManualStock'])
        ->name('products.stock.add')
        ->whereNumber('id');
    Route::post('/inventory/remove-manual/{id}', [ProductController::class, 'removeManualStock'])
        ->name('products.stock.remove')
        ->whereNumber('id');

    // Supplier routes.
    Route::resource('suppliers', SupplierController::class);

    // Brand routes.
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);

    // Parent category routes.
    Route::get('/categories/parents/create', [CategoryController::class, 'createParentCategory'])->name('categories.parents.create');
    Route::post('/categories/parents', [CategoryController::class, 'storeParentCategory'])->name('categories.parents.store');

    // Subcategory routes.
    Route::get('/categories/subcategories/create', [CategoryController::class, 'createSubcategory'])->name('categories.subcategories.create');
    Route::post('/categories/subcategories', [CategoryController::class, 'store'])->name('categories.subcategories.store');

    // Register static sales routes before the resource routes.
    Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
    Route::get('/sales/history/heartbeat', [SalesController::class, 'historyHeartbeat'])->name('sales.history.heartbeat');

    // Sales resource + state-change actions.
    Route::resource('sales', SalesController::class);
    Route::post('/sales/{id}/complete', [SalesController::class, 'complete'])->name('sales.complete');
    Route::post('/sales/{id}/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');

    Route::post('/sales/{id}/return', [SalesController::class, 'returnSale'])->name('sales.return');

    Route::get('/sales/{id}/print', [SalesController::class, 'print'])->name('sales.print');
    Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->name('sales.invoice');
    Route::patch('orders/{id}/ready-to-pickup', [SalesController::class, 'markReadyToPickup'])
        ->name('admin.orders.ready-to-pickup');

    // Client order management routes.
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::put('/orders/settings/order-expiration', [AdminOrderSettingsController::class, 'update'])
        ->name('admin.orders.settings.order-expiration.update');

    // Supplier order routes.
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])->name('admin.supplier-orders.index');
    Route::get('/supplier-orders/create', [SupplierOrderController::class, 'create'])->name('admin.supplier-orders.create');
    Route::post('/supplier-orders', [SupplierOrderController::class, 'store'])->name('admin.supplier-orders.store');
    Route::get('/supplier-orders/{id}/detail', [SupplierOrderController::class, 'detail'])->name('admin.supplier-orders.detail');
    Route::get('/admin/products/search', [SupplierOrderController::class, 'searchProducts'])->name('admin.products.search');
    Route::get('/supplier-orders/{id}', [SupplierOrderController::class, 'show'])->name('admin.supplier-orders.show');
    Route::patch('/supplier-orders/{id}/state', [SupplierOrderController::class, 'updateState'])->name('admin.supplier-orders.update-state');
    Route::post('/supplier-orders/{id}/receive', [SupplierOrderController::class, 'receiveOrder'])->name('admin.supplier-orders.receive');
    Route::post('/supplier-orders/{id}/close-partial', [SupplierOrderController::class, 'closePartial'])->name('admin.supplier-orders.close-partial');
    Route::get('/supplier/details/{id}', [SupplierOrderController::class, 'supplierDetails'])->name('admin.supplier-orders.supplier');

    // Admin client management routes.
    Route::get('/clientes', [AdminClientController::class, 'index'])->name('admin.clients.index');
    Route::patch('/clientes/{id}/ban', [AdminClientController::class, 'ban'])->name('admin.clients.ban');
    Route::patch('/clientes/{id}/unban', [AdminClientController::class, 'unban'])->name('admin.clients.unban');

    // Enables catalog preview without leaving the admin session.
    Route::get('/admin/catalog-preview', function () {
        $admin = auth('admin')->user();

        session([
            'admin_catalog_mode' => [
                'name' => $admin->name,
                'first_surname' => $admin->first_surname,
                'gmail' => $admin->gmail,
            ],
        ]);

        return redirect()->route('clients.catalog');
    })->name('admin.catalog.preview');

    // Enables store preview while preserving the admin session.
    Route::get('/admin/visit-store', function () {
        $admin = auth('admin')->user();

        session([
            'admin_catalog_mode' => [
                'name' => $admin->name,
                'first_surname' => $admin->first_surname,
                'gmail' => $admin->gmail,
            ],
        ]);

        return redirect()->route('clients.home');
    })->name('admin.visit-store');
});

// Clears admin catalog preview mode.
Route::get('/admin/catalog-exit', function () {
    session()->forget('admin_catalog_mode');

    if (auth('admin')->check()) {
        return redirect('/dashboard');
    }

    return redirect()->route('clients.home');
})->name('admin.catalog.exit');

// Public client pages.
Route::get('/', [ClientPageController::class, 'home'])->name('clients.home');
Route::get('/catalog', [ClientPageController::class, 'catalog'])->name('clients.catalog');

// Predictive suggestions for the public client catalog search bar.
Route::get('/api/products/suggestions', ClientCatalogProductSuggestionsController::class)
    ->middleware('throttle:60,1')
    ->name('api.products.suggestions');

// Product route with numeric ID and optional SEO slug.
Route::get('/product/{id}/{slug?}', [ClientPageController::class, 'product'])
    ->where(['id' => '[0-9]+', 'slug' => '[a-z0-9\-]*'])
    ->name('clients.product');

// Public client authentication routes.
Route::get('/login', [ClientUserController::class, 'showLoginForm'])->name('login.show');
Route::get('/register', [ClientUserController::class, 'showRegisterForm'])->name('clients.register.form');
Route::post('/register', [ClientUserController::class, 'register'])->name('clients.register');

// Throttle login attempts to reduce brute-force abuse.
Route::post('/login', [ClientUserController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

// Email verification routes.
Route::get('/verify', [ClientUserController::class, 'showVerifyForm'])->name('clients.verify.form');
Route::post('/verify', [ClientUserController::class, 'verify'])->name('clients.verify');
Route::post('/verify/resend', [ClientUserController::class, 'resendCode'])->name('clients.verify.resend');

// Password recovery routes.
Route::get('/recovery', [ClientUserController::class, 'showRecoveryForm'])->name('clients.recovery.form');
Route::post('/recovery', [ClientUserController::class, 'resetPassword'])->name('clients.recovery');
Route::get('/recovery/verify', [ClientUserController::class, 'showRecoveryVerifyForm'])->name('clients.recovery.verify.form');
Route::post('/recovery/verify', [ClientUserController::class, 'verifyRecoveryAndReset'])->name('clients.recovery.verify');

// Logs out the client while preserving the admin session when both are active.
Route::post('/logout', function () {
    $request = request();
    Auth::guard('clients')->logout();

    if (Auth::guard('admin')->check()) {
        // Remove only client-specific session data.
        $request->session()->forget([
            'client_id',
            'client_name',
            'client_first_surname',
            'client_second_surname',
            'pending_client_id',
            'pending_gmail',
            'cart',
        ]);
        $request->session()->regenerateToken();

        return redirect()->route('clients.home')->with('status', 'Sesión de cliente cerrada.');
    }

    // Invalidate the full session when no admin session is active.
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('clients.home')->with('status', 'Session closed successfully.');
})->name('logout');

// Returns a fresh CSRF token for frontend recovery flows.
Route::get('/csrf-token', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf.token');

// Google OAuth routes.
Route::get('/auth/google', [ClientUserController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [ClientUserController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Authenticated client routes.
Route::middleware(['auth:clients'])->group(function () {

    // Cart routes.
    Route::get('/cart', [ClientPageController::class, 'cart'])->name('clients.cart');
    Route::post('/cart/add', [ClientPageController::class, 'addToCart'])->name('clients.cart.add');
    Route::put('/cart/update', [ClientPageController::class, 'updateCart'])->name('clients.cart.update');
    Route::delete('/cart/remove/{id}', [ClientPageController::class, 'removeFromCart'])->name('clients.cart.remove');
    Route::delete('/cart/clear', [ClientPageController::class, 'clearCart'])->name('clients.cart.clear');
    Route::post('/cart/checkout', [ClientPageController::class, 'checkout'])->name('clients.cart.checkout');

    // Product reviews.
    Route::post('/products/{product}/review', [ProductReviewController::class, 'storeOrUpdate'])
        ->whereNumber('product')
        ->name('clients.products.review.store');
    Route::post('/products/reviews/batch', [ProductReviewController::class, 'storeBatch'])
        ->name('clients.products.review.batch');

    // Invoice routes.
    Route::get('/invoices', [ClientPageController::class, 'invoices'])->name('clients.invoices');
    Route::get('/invoices/heartbeat', [ClientPageController::class, 'invoicesHeartbeat'])->name('clients.invoices.heartbeat');
    Route::get('/notifications', [ClientPageController::class, 'notifications'])->name('clients.notifications');
    Route::get('/invoices/{sale}', [ClientPageController::class, 'showInvoice'])
        ->whereNumber('sale')
        ->name('clients.invoices.show');

    // Profile routes.
    Route::get('/profile', [ClientUserController::class, 'show'])->name('clients.profile');
    Route::put('/profile', [ClientUserController::class, 'update'])->name('clients.profile.update');
    Route::put('/profile/password', [ClientUserController::class, 'updatePassword'])->name('clients.profile.password');

    // Favorite products routes.
    Route::get('/favorites', [FavoriteProductController::class, 'index'])->name('clients.favorites.index');
    Route::post('/favorites/toggle', [FavoriteProductController::class, 'toggle'])->name('clients.favorites.toggle');
});
