<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\AdminUserController;
use App\Http\Controllers\Admin\Brands\BrandController;
use App\Http\Controllers\Admin\Categories\CategoryController;
use App\Http\Controllers\Admin\Classifications\ClassificationCatalogController;
use App\Http\Controllers\Admin\ClientPurchaseHistoryController;
use App\Http\Controllers\Admin\Clients\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Inventory\InventoryMovementController;
use App\Http\Controllers\Admin\Orders\OrderController;
use App\Http\Controllers\Admin\Orders\OrderSettingsController;
use App\Http\Controllers\Admin\Orders\WeeklyReportSettingsController;
use App\Http\Controllers\Admin\Products\ProductCatalogImportController;
use App\Http\Controllers\Admin\Products\ProductClassificationController;
use App\Http\Controllers\Admin\Products\ProductClassificationFilterController;
use App\Http\Controllers\Admin\Products\ProductController;
use App\Http\Controllers\Admin\Products\ProductGalleryController;
use App\Http\Controllers\Admin\Products\ProductInventoryController;
use App\Http\Controllers\Admin\Products\ProductManualStockController;
use App\Http\Controllers\Admin\Products\ProductStatusController;
use App\Http\Controllers\Admin\Products\ProductVariantController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReportsRegistryExportController;
use App\Http\Controllers\Admin\Sales\SalesController;
use App\Http\Controllers\Admin\Suppliers\SupplierController;
use App\Http\Controllers\Admin\Suppliers\SupplierOrderController;
use App\Http\Controllers\Admin\Suppliers\XmlPriceDeviationController;
use App\Http\Controllers\Client\Auth\GoogleOAuthController;
use App\Http\Controllers\Client\Auth\LoginController;
use App\Http\Controllers\Client\Auth\PasswordRecoveryController;
use App\Http\Controllers\Client\Auth\RegisterController;
use App\Http\Controllers\Client\Auth\VerificationController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\Catalog\ProductSuggestionsController;
use App\Http\Controllers\Client\Catalog\SearchTrendingController;
use App\Http\Controllers\Client\FavoriteController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\LegalController;
use App\Http\Controllers\Client\NotificationController;
use App\Http\Controllers\Client\ProductPageController;
use App\Http\Controllers\Client\ProductReviewController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\StorefrontController;
use App\Http\Controllers\Internal\DeployHelperController;
use App\Http\Controllers\Internal\VercelController;
use App\Services\Client\Cart\CartDatabaseStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::prefix('internal/vercel')->group(function () {
    Route::get('/cron/scheduler', [VercelController::class, 'scheduler']);
    Route::post('/jobs/catalog-import', [VercelController::class, 'catalogImport']);
    Route::post('/jobs/media-conversions', [VercelController::class, 'mediaConversions']);
    Route::post('/jobs/order-maintenance', [VercelController::class, 'orderMaintenance']);
});

// Protected deploy utilities.
Route::get('/run-migrations', [DeployHelperController::class, 'migrations']);

// Runs seeders with optional class-based execution.
Route::get('/run-seeders/{class?}', [DeployHelperController::class, 'seeders'])->where('class', '[A-Za-z0-9\\\\_]+');

// Admin authentication routes.
Route::get('/admin/login', [AdminUserController::class, 'showLoginForm'])->name('admin.login');
// Throttle admin login attempts to reduce brute-force abuse (igual que el login de cliente).
Route::post('/admin/login', [AdminUserController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');
Route::post('/admin/logout', [AdminUserController::class, 'logout'])->name('admin.logout');

// Public JSON aliases kept before admin resource routes. Vercel PHP strips the
// /api prefix from requests routed through api/index.php.
Route::get('/products/suggestions', ProductSuggestionsController::class)
    ->middleware('throttle:60,1')
    ->name('public.products.suggestions');

Route::get('/catalog/search-trending', SearchTrendingController::class)
    ->middleware('throttle:60,1')
    ->name('public.catalog.search-trending');

Route::get('/catalog/heartbeat', [StorefrontController::class, 'catalogHeartbeat'])
    ->name('public.catalog.heartbeat');

// Admin-only routes.
Route::middleware(['admin.only', 'prevent.direct', 'audit.sensitive.module'])->group(function () {

    // Dashboard routes.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/inertia-pilot', [DashboardController::class, 'inertiaPilot'])->name('dashboard.inertia-pilot');
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
    Route::get('/reports/catalogo-busquedas', [ReportsController::class, 'catalogMostSearchedProducts'])
        ->name('admin.reports.catalog-search-products');
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
    Route::get('/inventory', [ProductInventoryController::class, 'index'])->name('inventory');
    Route::get('/inventory/classification-filters', [ProductClassificationFilterController::class, 'options'])
        ->name('inventory.classification-filters');
    Route::get('/inventory/classification-filters/dimensions', [ProductClassificationFilterController::class, 'dimensions'])
        ->name('inventory.classification-filters.dimensions');
    Route::get('/inventory/classification-filters/{slug}/suggest', [ProductClassificationFilterController::class, 'suggest'])
        ->where('slug', '[a-z0-9_-]+')
        ->name('inventory.classification-filters.suggest');

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
    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])
        ->whereNumber('product')
        ->whereNumber('variant')
        ->name('admin.products.variants.update');

    // Featured product toggle route.
    Route::post('/products/{id}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
    Route::patch('/products/{id}/activate', [ProductStatusController::class, 'activate'])->name('products.activate')->whereNumber('id');

    // Product media management routes.
    Route::post('/products/{id}/gallery/{mediaId}/promote', [ProductGalleryController::class, 'promoteToMain'])->name('products.gallery.promote');
    Route::delete('/products/{id}/gallery/{mediaId}', [ProductGalleryController::class, 'destroy'])->name('products.gallery.destroy');
    Route::resource('products', ProductController::class)->except(['create', 'destroy']);
    Route::delete('/products/{id}', [ProductStatusController::class, 'destroy'])->name('products.destroy')->whereNumber('id');
    Route::delete('/products/{id}/force-delete', [ProductStatusController::class, 'forceDelete'])->name('products.force-delete')->whereNumber('id');
    Route::get('/inventory/export/{format?}', [ProductCatalogImportController::class, 'export'])->name('products.export');
    Route::post('/inventory/import', [ProductCatalogImportController::class, 'import'])->name('products.import');
    Route::get('/inventory/import/active', [ProductCatalogImportController::class, 'importActive'])->name('products.import.active');
    Route::get('/inventory/import/{importId}/progress', [ProductCatalogImportController::class, 'importProgress'])->name('products.import.progress');
    Route::post('/inventory/import/{importId}/cancel', [ProductCatalogImportController::class, 'importCancel'])->name('products.import.cancel');
    Route::post('/inventory/import/dismiss', [ProductCatalogImportController::class, 'importDismiss'])->name('products.import.dismiss');
    Route::post('/inventory/add-manual/{id}', [ProductManualStockController::class, 'add'])
        ->name('products.stock.add')
        ->whereNumber('id');
    Route::post('/inventory/remove-manual/{id}', [ProductManualStockController::class, 'remove'])
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
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::put('/orders/settings/order-expiration', [OrderSettingsController::class, 'update'])
        ->name('admin.orders.settings.order-expiration.update');

    Route::put('/orders/settings/weekly-report', [WeeklyReportSettingsController::class, 'update'])
        ->name('admin.orders.settings.weekly-report.update');

    // Supplier order routes.
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])->name('admin.supplier-orders.index');
    Route::post('/supplier-orders', [SupplierOrderController::class, 'store'])->name('admin.supplier-orders.store');
    Route::get('/supplier-orders/{id}/detail', [SupplierOrderController::class, 'detail'])->name('admin.supplier-orders.detail');
    Route::get('/admin/products/search', [SupplierOrderController::class, 'searchProducts'])->name('admin.products.search');

    // XML Price Deviation (inside the admin auth middleware group)
    Route::prefix('supplier-orders/xml-deviation')->name('admin.supplier-orders.xml-deviation.')->group(function () {

        Route::get('/', [XmlPriceDeviationController::class, 'showUploadForm'])
            ->name('upload');
        Route::post('/analyse', [XmlPriceDeviationController::class, 'analyse'])
            ->name('analyse');
        Route::get('/review', [XmlPriceDeviationController::class, 'review'])
            ->name('review');
        Route::post('/apply', [XmlPriceDeviationController::class, 'apply'])
            ->name('apply');
    });

    Route::get('/supplier-orders/{id}', [SupplierOrderController::class, 'show'])->name('admin.supplier-orders.show');
    Route::patch('/supplier-orders/{id}/state', [SupplierOrderController::class, 'updateState'])->name('admin.supplier-orders.update-state');
    Route::post('/supplier-orders/{id}/receive', [SupplierOrderController::class, 'receiveOrder'])->name('admin.supplier-orders.receive');
    Route::post('/supplier-orders/{id}/close-partial', [SupplierOrderController::class, 'closePartial'])->name('admin.supplier-orders.close-partial');
    Route::get('/supplier/details/{id}', [SupplierOrderController::class, 'supplierDetails'])->name('admin.supplier-orders.supplier');

    // Admin client management routes.
    Route::get('/clientes', [ClientController::class, 'index'])->name('admin.clients.index');
    Route::patch('/clientes/{id}/ban', [ClientController::class, 'ban'])->name('admin.clients.ban');
    Route::patch('/clientes/{id}/unban', [ClientController::class, 'unban'])->name('admin.clients.unban');

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
Route::get('/', [StorefrontController::class, 'home'])->name('clients.home');
Route::get('/catalog', [StorefrontController::class, 'catalog'])->name('clients.catalog');
Route::get('/api/catalog/heartbeat', [StorefrontController::class, 'catalogHeartbeat'])->name('api.catalog.heartbeat');

Route::get('/legal/terminos', [LegalController::class, 'terms'])->name('clients.legal.terms');
Route::get('/legal/privacidad', [LegalController::class, 'privacy'])->name('clients.legal.privacy');
Route::get('/legal/cambios-devoluciones', [LegalController::class, 'returns'])->name('clients.legal.returns');
Route::get('/contacto', [LegalController::class, 'contact'])->name('clients.contact');

// Predictive suggestions for the public client catalog search bar.
Route::get('/api/products/suggestions', ProductSuggestionsController::class)
    ->middleware('throttle:60,1')
    ->name('api.products.suggestions');

Route::get('/api/catalog/search-trending', SearchTrendingController::class)
    ->middleware('throttle:60,1')
    ->name('api.catalog.search-trending');

// Product route with numeric ID and optional SEO slug.
Route::get('/product/{id}/{slug?}', [ProductPageController::class, 'product'])
    ->where(['id' => '[0-9]+', 'slug' => '[a-z0-9\-]*'])
    ->name('clients.product');

// Public client authentication routes.
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.show');
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('clients.register.form');
Route::post('/register', [RegisterController::class, 'register'])->name('clients.register');

// Throttle login attempts to reduce brute-force abuse.
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

// Email verification routes.
Route::get('/verify', [VerificationController::class, 'showVerifyForm'])->name('clients.verify.form');
Route::post('/verify', [VerificationController::class, 'verify'])->name('clients.verify');
Route::post('/verify/resend', [VerificationController::class, 'resendCode'])->name('clients.verify.resend');

// Password recovery routes.
Route::get('/recovery', [PasswordRecoveryController::class, 'showRecoveryForm'])->name('clients.recovery.form');
Route::post('/recovery', [PasswordRecoveryController::class, 'sendRecoveryCode'])->name('clients.recovery');
Route::get('/recovery/verify', [PasswordRecoveryController::class, 'showRecoveryVerifyForm'])->name('clients.recovery.verify.form');
Route::post('/recovery/verify', [PasswordRecoveryController::class, 'verifyRecoveryCode'])->name('clients.recovery.verify');
Route::get('/recovery/reset', [PasswordRecoveryController::class, 'showRecoveryResetForm'])->name('clients.recovery.reset.form');
Route::post('/recovery/reset', [PasswordRecoveryController::class, 'updateRecoveryPassword'])->name('clients.recovery.reset');

// Logs out the client while preserving the admin session when both are active.
Route::post('/logout', function () {
    $request = request();

    $loggingOutClient = Auth::guard('clients')->user();
    if ($loggingOutClient) {
        app(CartDatabaseStore::class)->save($loggingOutClient->user_id, Session::get('cart', []));
    }

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

        return redirect()->route('clients.home')->with('client_success_modal', [
            'kind' => 'logout',
            'authIcon' => 'signout',
            'title' => '¡Sesión cerrada!',
            'text' => 'Cerraste la sesión de cliente. Tu sesión de administrador sigue activa.',
        ]);
    }

    // Invalidate the full session when no admin session is active.
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('clients.home')->with('client_success_modal', [
        'kind' => 'logout',
        'authIcon' => 'signout',
        'title' => '¡Sesión cerrada!',
        'text' => 'Has cerrado sesión correctamente.',
    ]);
})->name('logout');

// Returns a fresh CSRF token for frontend recovery flows.
Route::get('/csrf-token', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf.token');

// Google OAuth routes.
Route::get('/auth/google', [GoogleOAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Authenticated client routes.
Route::middleware(['auth:clients'])->group(function () {

    // Cart routes.
    Route::get('/cart', [CartController::class, 'cart'])->name('clients.cart');
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('clients.cart.add');
    Route::put('/cart/update', [CartController::class, 'updateCart'])->name('clients.cart.update');
    Route::delete('/cart/remove/{id}', [CartController::class, 'removeFromCart'])->name('clients.cart.remove');
    Route::delete('/cart/clear', [CartController::class, 'clearCart'])->name('clients.cart.clear');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('clients.cart.checkout');

    // Product reviews.
    Route::post('/products/{product}/review', [ProductReviewController::class, 'storeOrUpdate'])
        ->whereNumber('product')
        ->name('clients.products.review.store');
    Route::post('/products/reviews/batch', [ProductReviewController::class, 'storeBatch'])
        ->name('clients.products.review.batch');

    // Invoice routes.
    Route::get('/invoices', [InvoiceController::class, 'invoices'])->name('clients.invoices');
    Route::get('/invoices/heartbeat', [InvoiceController::class, 'invoicesHeartbeat'])->name('clients.invoices.heartbeat');
    Route::get('/notifications/heartbeat', [NotificationController::class, 'notificationsHeartbeat'])->name('clients.notifications.heartbeat');
    Route::get('/notifications', [NotificationController::class, 'notifications'])->name('clients.notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('clients.notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('clients.notifications.read');
    Route::get('/invoices/{sale}/print', [InvoiceController::class, 'printInvoice'])
        ->whereNumber('sale')
        ->name('clients.invoices.print');
    Route::get('/invoices/{sale}', [InvoiceController::class, 'showInvoice'])
        ->whereNumber('sale')
        ->name('clients.invoices.show');

    // Profile routes.
    Route::get('/profile', [ProfileController::class, 'show'])->name('clients.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('clients.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('clients.profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('clients.profile.avatar');

    // Favorite products routes.
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('clients.favorites.index');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('clients.favorites.toggle');
});
