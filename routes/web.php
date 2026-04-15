<?php

use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminOrderSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassificationCatalogController;
use App\Http\Controllers\ClientPageController;
use App\Http\Controllers\ClientUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductClassificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Allow HTTP-triggered migrate/seed only in local/testing, or when ?key= matches config('app.deploy_secret').
 */
$assertDeployHelperAllowed = function (Request $request): void {
    if (app()->environment('local', 'testing')) {
        return;
    }
    $secret = (string) config('app.deploy_secret', '');
    if ($secret === '') {
        abort(404);
    }
    if (! hash_equals($secret, (string) $request->query('key', ''))) {
        abort(404);
    }
};

// ============================================================
// DEPLOY UTILITIES — Protected outside local/testing (see DEPLOY_SECRET)
// ============================================================

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

Route::get('/run-seeders/{class?}', function (Request $request, ?string $class = null) use ($assertDeployHelperAllowed) {
    $assertDeployHelperAllowed($request);

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

// ============================================================
// ADMIN ROUTES
// ============================================================

// --- Admin Authentication (public) ---
Route::get('/admin/login', [AdminUserController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminUserController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminUserController::class, 'logout'])->name('admin.logout');

// --- Admin Protected Routes ---
Route::middleware(['admin.only', 'prevent.direct'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

    // Inventory / Products
    Route::get('/inventory', [ProductController::class, 'inventory'])->name('inventory');
    // CF4-84 — catálogo CRUD dimensiones/valores por subcategoría
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

    // CF4-84 — asignación de clasificaciones al producto (subcategoría)
    Route::get('/product-classifications', [ProductClassificationController::class, 'index'])->name('admin.product-classifications.index');
    Route::get('/products/{product}/classifications/edit', [ProductClassificationController::class, 'edit'])->name('admin.products.classifications.edit');
    Route::put('/products/{product}/classifications', [ProductClassificationController::class, 'update'])->name('admin.products.classifications.update');

    // CF4-29 — featured toggle
    Route::post('/products/{id}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
    Route::resource('products', ProductController::class)->except(['create']);
    Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.force-delete');
    Route::get('/inventory/export/{format?}', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    // Brands
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);

    // Categories — subcategorías (CF4-68)
    Route::get('/categories/subcategories/create', [CategoryController::class, 'createSubcategory'])->name('categories.subcategories.create');
    Route::post('/categories/subcategories', [CategoryController::class, 'store'])->name('categories.subcategories.store');

    // Sales
    Route::resource('sales', SalesController::class);
    Route::post('/sales/{id}/complete', [SalesController::class, 'complete'])->name('sales.complete');
    Route::post('/sales/{id}/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->name('sales.refund');
    Route::get('/sales/{id}/print', [SalesController::class, 'print'])->name('sales.print');
    Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->name('sales.invoice');
    Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
    Route::get('/sales/history/heartbeat', [SalesController::class, 'historyHeartbeat'])->name('sales.history.heartbeat');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::put('/orders/settings/order-expiration', [AdminOrderSettingsController::class, 'update'])
        ->name('admin.orders.settings.order-expiration.update');

    // Supplier Purchase Orders
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])->name('admin.supplier-orders.index');
    Route::get('/supplier-orders/create', [SupplierOrderController::class, 'create'])->name('admin.supplier-orders.create');
    Route::post('/supplier-orders', [SupplierOrderController::class, 'store'])->name('admin.supplier-orders.store');
    Route::get('/supplier-orders/{id}/detail', [SupplierOrderController::class, 'detail'])->name('admin.supplier-orders.detail');
    Route::get('/admin/products/search', [SupplierOrderController::class, 'searchProducts'])->name('admin.products.search');
    Route::get('/supplier-orders/{id}', [SupplierOrderController::class, 'show'])->name('admin.supplier-orders.show');
    Route::patch('/supplier-orders/{id}/state', [SupplierOrderController::class, 'updateState'])->name('admin.supplier-orders.update-state');
    Route::get('/supplier/details/{id}', [SupplierOrderController::class, 'supplierDetails'])->name('admin.supplier-orders.supplier');

    // Client Management (admin view)
    Route::get('/clientes', [AdminClientController::class, 'index'])->name('admin.clients.index');
    Route::patch('/clientes/{id}/ban', [AdminClientController::class, 'ban'])->name('admin.clients.ban');
    Route::patch('/clientes/{id}/unban', [AdminClientController::class, 'unban'])->name('admin.clients.unban');

    // Admin catalog preview — stores admin identity in session then redirects to client catalog
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
});

// ============================================================
// CLIENT ROUTES
// ============================================================

// Clears admin catalog preview mode and returns to admin panel (or home if not admin)
Route::get('/admin/catalog-exit', function () {
    session()->forget('admin_catalog_mode');

    if (auth('admin')->check()) {
        return redirect('/dashboard');
    }

    return redirect()->route('clients.home');
})->name('admin.catalog.exit');

// --- Public Pages ---
Route::get('/', [ClientPageController::class, 'home'])->name('clients.home');
Route::get('/catalog', [ClientPageController::class, 'catalog'])->name('clients.catalog');
Route::get('/product/{id}/{slug?}', [ClientPageController::class, 'product'])
    ->where(['id' => '[0-9]+', 'slug' => '[a-z0-9\-]*'])
    ->name('clients.product');

// --- Client Authentication (public) ---
Route::get('/login', [ClientUserController::class, 'showLoginForm'])->name('login.show');
Route::get('/register', [ClientUserController::class, 'showRegisterForm'])->name('clients.register.form');
Route::post('/register', [ClientUserController::class, 'register'])->name('clients.register');

// Throttle prevents brute-force attacks (max 5 attempts per minute)
Route::post('/login', [ClientUserController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

// Email verification
Route::get('/verify', [ClientUserController::class, 'showVerifyForm'])->name('clients.verify.form');
Route::post('/verify', [ClientUserController::class, 'verify'])->name('clients.verify');
Route::post('/verify/resend', [ClientUserController::class, 'resendCode'])->name('clients.verify.resend');

Route::get('/recovery', [ClientUserController::class, 'showRecoveryForm'])->name('clients.recovery.form');
Route::post('/recovery', [ClientUserController::class, 'resetPassword'])->name('clients.recovery');
Route::get('/recovery/verify', [ClientUserController::class, 'showRecoveryVerifyForm'])->name('clients.recovery.verify.form');
Route::post('/recovery/verify', [ClientUserController::class, 'verifyRecoveryAndReset'])->name('clients.recovery.verify');

// Logs out both guards to prevent inconsistent session state
Route::post('/logout', function (Request $request) {
    Auth::guard('clients')->logout();
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('clients.home')->with('status', 'Session closed successfully.');
})->name('logout');

// Refreshes the CSRF token — called by JS on 419 responses
Route::get('/csrf-token', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf.token');

// --- OAuth (Google) ---
Route::get('/auth/google', [ClientUserController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [ClientUserController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// --- Client Protected Routes (requires clients guard) ---
Route::middleware(['auth:clients'])->group(function () {

    // Cart
    Route::get('/cart', [ClientPageController::class, 'cart'])->name('clients.cart');
    Route::post('/cart/add', [ClientPageController::class, 'addToCart'])->name('clients.cart.add');
    Route::put('/cart/update', [ClientPageController::class, 'updateCart'])->name('clients.cart.update');
    Route::delete('/cart/remove/{id}', [ClientPageController::class, 'removeFromCart'])->name('clients.cart.remove');
    Route::delete('/cart/clear', [ClientPageController::class, 'clearCart'])->name('clients.cart.clear');
    Route::post('/cart/checkout', [ClientPageController::class, 'checkout'])->name('clients.cart.checkout');

    // Profile
    Route::get('/profile', [ClientUserController::class, 'show'])->name('clients.profile');
    Route::put('/profile', [ClientUserController::class, 'update'])->name('clients.profile.update');
    Route::put('/profile/password', [ClientUserController::class, 'updatePassword'])->name('clients.profile.password');
});
