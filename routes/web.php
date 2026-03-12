<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Route::get('/run-migrations', function () {
    try {
        // Esto ejecuta el comando 'php artisan migrate --force' desde el navegador
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

// Public customer routes
Route::get('/', [CustomerController::class, 'home'])->name('customers.home');
Route::get('/catalog', [CustomerController::class, 'catalog'])->name('customers.catalog');
Route::get('/product/{id}', [CustomerController::class, 'product'])->name('customers.product');

// Cart routes
Route::post('/cart/add', [CustomerController::class, 'addToCart'])->name('customers.cart.add');
Route::get('/cart', [CustomerController::class, 'cart'])->name('customers.cart');
Route::put('/cart/update', [CustomerController::class, 'updateCart'])->name('customers.cart.update');
Route::delete('/cart/remove/{id}', [CustomerController::class, 'removeFromCart'])->name('customers.cart.remove');

// Authentication Routes
Route::get('/login', [UsuarioController::class, 'showLogin'])->name('login.show');
Route::post('/login', [UsuarioController::class, 'login'])
    ->middleware('throttle:5,1') // 5 intentos por minuto para prevenir fuerza bruta
    ->name('login');
Route::post('/logout', function(Request $request) {
    // Logout más seguro
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect()->route('customers.home')->with('status', 'Sesión cerrada correctamente.');
})->name('logout');

// OAuth Routes
Route::get('/auth/google', [UsuarioController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [UsuarioController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/facebook', [UsuarioController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [UsuarioController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');

// Ruta para renovar token CSRF
Route::get('/csrf-token', function(Request $request) {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
})->name('csrf.token');

// Usuario Routes (públicas para login y registro)
Route::post('/usuarios/store-login', [UsuarioController::class, 'storeLogin'])->name('storeLogin');

// Usuario Routes protegidas (solo administradores)
Route::middleware(['auth', 'admin.only', 'prevent.direct'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
});

// Protected Routes (require authentication AND admin role with additional security)
Route::middleware(['auth', 'admin.only', 'prevent.direct'])->group(function () {
    
// Dashboard Routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

// Inventory/Products Routes
Route::get('/inventory', [ProductController::class, 'inventory'])->name('inventory');
Route::resource('products', ProductController::class)->except(['create']);
Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.force-delete');
Route::get('/inventory/export/{format?}', [ProductController::class, 'export'])->name('products.export');
Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

// Suppliers Routes
Route::resource('suppliers', SupplierController::class);

// Sales module (100% English)
Route::resource('sales', SalesController::class);
Route::post('/sales/{id}/complete', [SalesController::class, 'complete'])->name('sales.complete');
Route::post('/sales/{id}/cancel', [SalesController::class, 'cancel'])->name('sales.cancel');
Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->name('sales.refund');
Route::get('/sales/{id}/print', [SalesController::class, 'print'])->name('sales.print');
Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->name('sales.invoice');
Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');

// Dashboard API Routes
Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');

}); // Cierre del grupo middleware auth + admin.only