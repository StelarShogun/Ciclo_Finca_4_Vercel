<?php

use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SupplierOrderController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientPageController;
use App\Http\Controllers\ClientUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ============================================================
// DEV UTILITIES — Remove before deploying to production
// ============================================================

Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);

        return '✅ Migrations executed successfully: <br><pre>'.Artisan::output().'</pre>';
    } catch (Exception $e) {
        return '❌ Error running migrations: '.$e->getMessage();
    }
});

Route::get('/run-seeders/{class?}', function ($class = null) {
    try {
        $params = ['--force' => true];
        if ($class) {
            $params['--class'] = $class;
        }
        Artisan::call('db:seed', $params);

        return '✅ Seeder executed:<br><pre>'.Artisan::output().'</pre>';
    } catch (Exception $e) {
        return '❌ Error: '.$e->getMessage();
    }
});

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

    // Supplier Purchase Orders
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])->name('admin.supplier-orders.index');
    Route::get('/supplier-orders/{id}', [SupplierOrderController::class, 'show'])->name('admin.supplier-orders.show');
    Route::patch('/supplier-orders/{id}/state', [SupplierOrderController::class, 'updateState'])->name('admin.supplier-orders.update-state');
    Route::get('/supplier/details/{id}', [SupplierOrderController::class, 'supplierDetails'])->name('admin.supplier-orders.supplier');

    // Client Management (admin view)
    Route::get('/clientes', [AdminClientController::class, 'index'])->name('admin.clients.index');
    Route::patch('/clientes/{id}/ban', [AdminClientController::class, 'ban'])->name('admin.clients.ban');
    Route::patch('/clientes/{id}/unban', [AdminClientController::class, 'unban'])->name('admin.clients.unban');
});

// ============================================================
// CLIENT ROUTES
// ============================================================

// --- Public Pages ---
Route::get('/', [ClientPageController::class, 'home'])->name('clients.home');
Route::get('/catalog', [ClientPageController::class, 'catalog'])->name('clients.catalog');
Route::get('/product/{id}', [ClientPageController::class, 'product'])->name('clients.product');

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
