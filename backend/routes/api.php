<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
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
    });

    // --- Módulos cliente (se llenan en Bloque 5) ---
    Route::prefix('client')->middleware('auth:clients')->group(function (): void {
        //
    });
});
