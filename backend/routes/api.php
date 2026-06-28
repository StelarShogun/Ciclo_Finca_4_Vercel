<?php

use App\Http\Controllers\Api\V1\Admin\BrandController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\ProductClassificationController;
use App\Http\Controllers\Api\V1\Admin\ProductGalleryController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController;
use App\Http\Controllers\Api\V1\Admin\SaleController;
use App\Http\Controllers\Api\V1\Admin\SupplierOrderController;
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

        // Pedidos a proveedores (recepción con stock de entrada en las Actions)
        Route::get('/supplier-orders', [SupplierOrderController::class, 'index']);
        Route::get('/supplier-orders/search-products', [SupplierOrderController::class, 'searchProducts']);
        Route::post('/supplier-orders', [SupplierOrderController::class, 'store']);
        Route::get('/supplier-orders/{order}', [SupplierOrderController::class, 'show'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/state', [SupplierOrderController::class, 'updateState'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/close-partial', [SupplierOrderController::class, 'closePartial'])->whereNumber('order');
        Route::post('/supplier-orders/{order}/receive', [SupplierOrderController::class, 'receive'])->whereNumber('order');
    });

    // --- Módulos cliente (se llenan en Bloque 5) ---
    Route::prefix('client')->middleware('auth:clients')->group(function (): void {
        //
    });
});
