<?php

namespace App\Http\Middleware;

use App\Services\Admin\Audit\AuditLogger;
use App\Services\Shared\Security\SensitiveDataMasker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra accesos de lectura (GET HTML) a módulos sensibles del panel admin.
 * No registra endpoints AJAX/JSON ni recursos técnicos para evitar ruido en la bitácora.
 */
class LogSensitiveAdminModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() !== 'GET') {
            return $response;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $response;
        }

        $path = trim($request->path(), '/');

        if ($this->isGloballyExcluded($path)) {
            return $response;
        }

        $module = $this->resolveSensitiveModule($path);
        if ($module === null) {
            return $response;
        }

        try {
            app(AuditLogger::class)->logAdminAction(
                'module_access',
                $module,
                'Acceso a módulo sensible desde el panel.',
                ['path' => '/'.$path]
            );
        } catch (\Throwable $e) {
            Log::warning('module_access audit log write failed', SensitiveDataMasker::exceptionContext($e, [
                'path' => $path,
            ]));
        }

        return $response;
    }

    private function isGloballyExcluded(string $path): bool
    {
        $prefixes = [
            'dashboard/data',
            'dashboard/chart-data',
            'dashboard/export',
            'sales/export',
            'sales/history',
            'reports/ventas/range',
            'reports/ventas/metrics',
            'reports/productos-vendidos/table',
            'reports/productos-vendidos/pdf',
            'reports/productos-vendidos/excel',
            'reports/client-purchases/table',
            'inventory/movements',
            'inventory/export',
            'admin/products/search',
            'supplier/details',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function resolveSensitiveModule(string $path): ?string
    {
        if ($path === 'dashboard') {
            return 'dashboard';
        }

        if ($path === 'inventory') {
            return 'products';
        }

        if (str_starts_with($path, 'products')) {
            return 'products';
        }

        if ($path === 'sales' || preg_match('#^sales/\d+(/(invoice|print))?$#', $path)) {
            return 'sales';
        }

        if ($path === 'sales/reports/by-category') {
            return 'reports';
        }

        if ($path === 'orders') {
            return 'orders';
        }

        if ($path === 'supplier-orders' || $path === 'supplier-orders/create' || preg_match('#^supplier-orders/\d+/detail$#', $path)) {
            return 'supplier_orders';
        }

        if (str_starts_with($path, 'reports')) {
            return 'reports';
        }

        if ($path === 'clientes') {
            return 'clients';
        }

        return null;
    }
}
