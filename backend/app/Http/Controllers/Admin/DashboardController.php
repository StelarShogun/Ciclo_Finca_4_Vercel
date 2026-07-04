<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\DashboardChartRequest;
use App\Http\Requests\Admin\Dashboard\DashboardExportRequest;
use App\Services\Admin\Dashboard\DashboardChartService;
use App\Services\Admin\Dashboard\DashboardExportService;
use App\Services\Admin\Dashboard\DashboardKpiService;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getDashboardData(DashboardKpiService $dashboard)
    {
        $this->authorizeDashboard();

        try {
            return response()->json($dashboard->jsonSummary());
        } catch (\Throwable $e) {
            $this->logDashboardError('admin_dashboard_data_failed', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard',
            ], 500);
        }
    }

    public function getChartData(DashboardChartRequest $request, DashboardChartService $dashboard)
    {
        $this->authorizeDashboard();

        $period = $request->period();

        try {
            $ttl = max(60, (int) config('cf4_performance.admin_dashboard_charts_ttl', 300));
            $payload = Cache::remember(AdminDashboardCache::chartKey($period), $ttl, fn () => $dashboard->chartData($period));

            return response()->json([
                'success' => true,
                'sales' => $payload['sales'],
                'categories' => $payload['categories'],
            ]);
        } catch (\Throwable $e) {
            $this->logDashboardError('admin_dashboard_chart_data_failed', $e);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible obtener los datos del gráfico.',
            ], 500);
        }
    }

    public function exportReport(DashboardExportRequest $request, DashboardExportService $export)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.export');

        $format = $request->exportFormat();
        $period = $request->period();

        try {
            return $export->download($format, $period);
        } catch (\Throwable $e) {
            Log::error('admin_dashboard_export_failed', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => Auth::guard('admin')->id(),
                'format' => $format,
                'period' => $period,
            ]));

            return response()->json([
                'success' => false,
                'message' => 'No fue posible exportar el reporte del dashboard.',
            ], 500);
        }
    }

    private function authorizeDashboard(): void
    {
        if (! Auth::guard('admin')->check()) {
            abort(403);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.view');
    }

    private function logDashboardError(string $event, \Throwable $e): void
    {
        Log::error($event, SensitiveDataMasker::exceptionContext($e, [
            'admin_id' => Auth::guard('admin')->id(),
        ]));
    }
}
