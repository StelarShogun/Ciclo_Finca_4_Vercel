<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ClientUserController;

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

// Throttle prevents brute-force attacks (5 attempts per minute)
Route::post('/login', [ClientUserController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

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
// OAUTH (Google & Facebook)
// ============================================================

Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/facebook', [UsuarioController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [UsuarioController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');

// ============================================================
// CART (authenticated clients)
// ============================================================

Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [ClientController::class, 'cart'])->name('clients.cart');
    Route::post('/cart/add', [ClientController::class, 'addToCart'])->name('clients.cart.add');
    Route::put('/cart/update', [ClientController::class, 'updateCart'])->name('clients.cart.update');
    Route::delete('/cart/remove/{id}', [ClientController::class, 'removeFromCart'])->name('clients.cart.remove');
    Route::delete('/cart/clear', [ClientController::class, 'clearCart'])->name('clients.cart.clear');
    Route::post('/cart/checkout', [ClientController::class, 'checkout'])->name('clients.cart.checkout');
});

// ============================================================
// ADMIN ROUTES (auth + admin.only + prevent.direct)
// ============================================================

Route::middleware(['auth', 'admin.only', 'prevent.direct'])->group(function () {

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