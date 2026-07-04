<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Products\ProductCatalogImportController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReportsRegistryExportController;
use App\Http\Controllers\Admin\Sales\SalesController;
use App\Http\Controllers\Client\Auth\GoogleOAuthController;
use App\Http\Controllers\Client\Catalog\ProductSuggestionsController;
use App\Http\Controllers\Client\Catalog\SearchTrendingController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Internal\DeployHelperController;
use App\Http\Controllers\Internal\VercelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/vercel')->group(function () {
    Route::get('/cron/scheduler', [VercelController::class, 'scheduler']);
    Route::post('/jobs/catalog-import', [VercelController::class, 'catalogImport']);
    Route::post('/jobs/media-conversions', [VercelController::class, 'mediaConversions']);
    Route::post('/jobs/order-maintenance', [VercelController::class, 'orderMaintenance']);
});

Route::get('/run-migrations', [DeployHelperController::class, 'migrations']);
Route::get('/run-seeders/{class?}', [DeployHelperController::class, 'seeders'])->where('class', '[A-Za-z0-9\\\\_]+');

Route::get('/csrf-token', function (Request $request) {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => $request->session()->getId(),
        'authenticated' => auth('clients')->check() || auth('admin')->check(),
        'guard' => auth('admin')->check() ? 'admin' : (auth('clients')->check() ? 'clients' : null),
    ]);
});

Route::get('/auth/google', [GoogleOAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/products/suggestions', ProductSuggestionsController::class)
    ->middleware('throttle:60,1')
    ->name('public.products.suggestions');
Route::get('/catalog/search-trending', SearchTrendingController::class)
    ->middleware('throttle:60,1')
    ->name('public.catalog.search-trending');

Route::middleware(['admin.only', 'audit.sensitive.module'])->group(function () {
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');
    Route::get('/inventory/export/{format?}', [ProductCatalogImportController::class, 'export'])->name('products.export');
    Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
    Route::get('/sales/{id}/print', [SalesController::class, 'print'])->whereNumber('id')->name('sales.print');
    Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->whereNumber('id')->name('sales.invoice');
    Route::get('/reports/productos-vendidos/pdf', [ReportsController::class, 'productSalesPdf'])->name('admin.reports.product-sales.pdf');
    Route::get('/reports/productos-vendidos/excel', [ReportsController::class, 'productSalesExcel'])->name('admin.reports.product-sales.excel');
    Route::get('/reports/exportaciones/descarga/{slug}', [ReportsRegistryExportController::class, 'download'])
        ->where('slug', 'proveedores|marcas|pedidos-proveedores|usuarios|pedidos-clientes')
        ->name('admin.reports.exports.registry');
});

Route::middleware('auth:clients')->group(function () {
    Route::get('/invoices/{sale}/print', [InvoiceController::class, 'printInvoice'])
        ->whereNumber('sale')
        ->name('clients.invoices.print');
});

$frontendUrl = static function (string $path): string {
    $base = rtrim((string) config('app.spa_url', config('app.frontend_url')), '/');

    return $base.'/'.ltrim($path, '/');
};

$frontend = static fn (string $path) => static fn () => redirect($frontendUrl($path), 302);

Route::get('/', $frontend('/'))->name('clients.home');
Route::get('/catalog', $frontend('/catalog'))->name('clients.catalog');
Route::get('/product/{id}/{slug?}', fn (string $id) => redirect($frontendUrl('/product/'.$id), 302))->name('clients.product');
Route::get('/login', $frontend('/login'))->name('login.show');
Route::get('/auth/login', $frontend('/login'))->name('login');
Route::get('/register', $frontend('/register'))->name('clients.register.form');
Route::get('/verify', $frontend('/verify'))->name('clients.verify.form');
Route::get('/recovery', $frontend('/recovery'))->name('clients.recovery.form');
Route::get('/recovery/verify', $frontend('/recovery'))->name('clients.recovery.verify.form');
Route::get('/recovery/reset', $frontend('/recovery'))->name('clients.recovery.reset.form');
Route::get('/cart', $frontend('/cart'))->name('clients.cart');
Route::get('/favorites', $frontend('/favorites'))->name('clients.favorites.index');
Route::get('/invoices', $frontend('/invoices'))->name('clients.invoices');
Route::get('/invoices/{sale}', fn (string $sale) => redirect($frontendUrl('/invoices/'.$sale), 302))->name('clients.invoices.show');
Route::get('/notifications', $frontend('/notifications'))->name('clients.notifications');
Route::get('/profile', $frontend('/profile'))->name('clients.profile');
Route::get('/legal/terminos', $frontend('/terminos'))->name('clients.legal.terms');
Route::get('/legal/privacidad', $frontend('/privacidad'))->name('clients.legal.privacy');
Route::get('/legal/cambios-devoluciones', $frontend('/devoluciones'))->name('clients.legal.returns');
Route::get('/contacto', $frontend('/contacto'))->name('clients.contact');
Route::get('/admin/login', $frontend('/admin/login'))->name('admin.login');
