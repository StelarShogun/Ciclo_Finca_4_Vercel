<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\ClientUserController;


// ============================================================
// ADMIN LOGIN ROUTES
// ============================================================
Route::get('/admin/login', [AdminUserController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminUserController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminUserController::class, 'logout'])->name('admin.logout');

// ============================================================
// DEV UTILITIES (remove in production)
// ============================================================

Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "✅ Migraciones ejecutadas con éxito: <br><pre>" . Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "❌ Error ejecutando migraciones: " . $e->getMessage();
    }
});

Route::get('/run-seeders/{class?}', function ($class = null) {
    try {
        $params = ['--force' => true];
        if ($class) {
            $params['--class'] = $class;
        }
        Artisan::call('db:seed', $params);
        return "✅ Seeder ejecutado:<br><pre>" . Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});

// ============================================================
// PUBLIC ROUTES
// ============================================================

Route::get('/', [ClientController::class, 'home'])->name('clients.home');
Route::get('/catalog', [ClientController::class, 'catalog'])->name('clients.catalog');
Route::get('/product/{id}', [ClientController::class, 'product'])->name('clients.product');

// ============================================================
// AUTHENTICATION
// ============================================================

Route::get('/login', [ClientUserController::class, 'showLoginForm'])->name('login.show');
Route::get('/register', [ClientUserController::class, 'showRegisterForm'])->name('clients.register.form');
Route::post('/register', [ClientUserController::class, 'register'])->name('clients.register');
Route::get('/verify', [ClientUserController::class, 'showVerifyForm'])->name('clients.verify.form');
Route::post('/verify', [ClientUserController::class, 'verify'])->name('clients.verify');
Route::post('/verify/resend', [ClientUserController::class, 'resendCode'])->name('clients.verify.resend');
Route::get('/recovery', [ClientUserController::class, 'showRecoveryForm'])->name('clients.recovery.form');
Route::post('/recovery', [ClientUserController::class, 'resetPassword'])->name('clients.recovery');
Route::get('/recovery/verify', [ClientUserController::class, 'showRecoveryVerifyForm'])->name('clients.recovery.verify.form');
Route::post('/recovery/verify', [ClientUserController::class, 'verifyRecoveryAndReset'])->name('clients.recovery.verify');

// Throttle prevents brute-force attacks (5 attempts per minute)
Route::post('/login', [ClientUserController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

// ============================================================
// ADMIN LOGIN (público para que el usuario admin pueda autenticarse)
// ============================================================
Route::get('/admin/login', [AdminUserController::class, 'showLoginForm'])
    ->name('admin.login');

Route::post('/admin/login', [AdminUserController::class, 'login'])
    ->name('admin.login.submit');

// Logs out both guards to avoid inconsistent session state
Route::post('/logout', function (Request $request) {
    Auth::guard('clients')->logout();
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('clients.home')->with('status', 'Sesión cerrada correctamente.');
})->name('logout');

// Refreshes the CSRF token (called by JS on 419 responses)
Route::get('/csrf-token', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf.token');

// ============================================================
// OAUTH (Google for clients; Facebook via UsuarioController if needed)
// ============================================================

Route::get('/auth/google', [ClientUserController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [ClientUserController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// ============================================================
// CART (authenticated clients)
// ============================================================

// Cart (clientes autenticados)
Route::middleware(['auth:clients'])->group(function () {
    Route::get('/cart', [ClientController::class, 'cart'])->name('clients.cart');
    Route::post('/cart/add', [ClientController::class, 'addToCart'])->name('clients.cart.add');
    Route::put('/cart/update', [ClientController::class, 'updateCart'])->name('clients.cart.update');
    Route::delete('/cart/remove/{id}', [ClientController::class, 'removeFromCart'])->name('clients.cart.remove');
    Route::delete('/cart/clear', [ClientController::class, 'clearCart'])->name('clients.cart.clear');
    Route::post('/cart/checkout', [ClientController::class, 'checkout'])->name('clients.cart.checkout');
});

// Usuario Routes protegidas (solo administradores)
Route::middleware(['auth:admin'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
});

// ============================================================
// ADMIN ROUTES (admin.only + prevent.direct)
// ============================================================

    Route::middleware(['auth:admin', 'admin.only', 'prevent.direct'])->group(function () {

    // — User management —
    Route::post('/usuarios/store-login', [UsuarioController::class, 'storeLogin'])->name('storeLogin');
    Route::resource('usuarios', UsuarioController::class);

    // — Dashboard —
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

    // — Inventory / Products —
    Route::get('/inventory', [ProductController::class, 'inventory'])->name('inventory');
    Route::resource('products', ProductController::class)->except(['create']);
    Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.force-delete');
    Route::get('/inventory/export/{format?}', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

    // — Suppliers —
    Route::resource('suppliers', SupplierController::class);

    // — Sales —
    Route::resource('sales', SalesController::class);
    Route::post('/sales/{id}/complete', [SalesController::class, 'complete'])->name('sales.complete');
    Route::post('/sales/{id}/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->name('sales.refund');
    Route::get('/sales/{id}/print', [SalesController::class, 'print'])->name('sales.print');
    Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->name('sales.invoice');
    Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
    Route::get('/sales/history/heartbeat', [SalesController::class, 'historyHeartbeat'])
        ->name('sales.history.heartbeat');

    // — Clients (admin view) —
    Route::get('/clientes', [AdminClientController::class, 'index'])->name('admin.clients.index');
    Route::patch('/clientes/{id}/ban', [AdminClientController::class, 'ban'])->name('admin.clients.ban');
    Route::patch('/clientes/{id}/unban', [AdminClientController::class, 'unban'])->name('admin.clients.unban');

});

// ============================================================
// CLIENT PROFILE (authenticated clients)
// ============================================================
 
Route::middleware(['auth:clients'])->group(function () {
 
    // Show profile page
    Route::get('/profile', [ClientUserController::class, 'show'])
        ->name('clients.profile');
 
    // Update basic profile data (name, surnames, email)
    Route::put('/profile', [ClientUserController::class, 'update'])
        ->name('clients.profile.update');
 
    // Update or define password
    Route::put('/profile/password', [ClientUserController::class, 'updatePassword'])
        ->name('clients.profile.password');
 
});