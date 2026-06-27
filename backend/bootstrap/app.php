<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\CacheStaticBuildAssets;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Http\Middleware\PreventDirectAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CacheStaticBuildAssets::class);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Sanctum SPA: hace que /api/* respete la cookie de sesión del frontend
        // Next (mismo dominio padre) y aplique CSRF a los requests con estado.
        // Ver SANCTUM_STATEFUL_DOMAINS en .env.
        $middleware->statefulApi();

        $middleware->trustProxies(at: '*');

        // api/* ya NO se exime de CSRF: el flujo SPA de Sanctum valida el
        // XSRF-TOKEN. Los endpoints internos siguen exentos (secreto en header).
        $middleware->preventRequestForgery(except: [
            'internal/vercel/*',
        ]);

        $middleware->alias([
            'admin.only' => AdminOnly::class,
            'prevent.direct' => PreventDirectAccess::class,
            'audit.sensitive.module' => LogSensitiveAdminModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
