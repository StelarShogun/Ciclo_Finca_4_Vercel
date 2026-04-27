<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Http\Middleware\PreventDirectAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->preventRequestForgery(except: [
            'api/*',
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
