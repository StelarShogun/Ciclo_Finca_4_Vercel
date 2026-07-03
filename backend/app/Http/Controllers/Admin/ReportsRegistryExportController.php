<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReportExportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\RegistryExportRequest;
use App\Services\Admin\Reports\ReportsExportRouter;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ReportsRegistryExportController extends Controller
{
    public function download(RegistryExportRequest $request, string $slug, ReportsExportRouter $router): Response
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.export');

        try {
            return $router->download($request, $slug);
        } catch (ReportExportException $exception) {
            Log::warning('admin_registry_report_export_domain_error', SensitiveDataMasker::exceptionContext($exception, [
                'admin_id' => Auth::guard('admin')->id(),
                'slug' => $slug,
                'format' => $request->query('format'),
            ]));

            abort($exception->status(), $exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('admin_registry_report_export_failed', SensitiveDataMasker::exceptionContext($exception, [
                'admin_id' => Auth::guard('admin')->id(),
                'slug' => $slug,
                'format' => $request->query('format'),
            ]));

            abort(500, 'No fue posible exportar el reporte solicitado.');
        }
    }
}
