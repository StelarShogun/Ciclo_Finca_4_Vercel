<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\DashboardChartRequest;
use App\Http\Requests\Admin\Dashboard\DashboardExportRequest;
use App\Http\Requests\Admin\Dashboard\DashboardIndexRequest;
use App\Models\AppSetting;
use App\Services\Admin\Dashboard\DashboardChartService;
use App\Services\Admin\Dashboard\DashboardExportService;
use App\Services\Admin\Dashboard\DashboardKpiService;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use App\ViewModels\Admin\DashboardViewModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardIndexRequest $request, DashboardKpiService $dashboard): Response
    {
        $this->authorizeDashboard();

        try {
            $ttl = max(15, (int) config('cf4_performance.admin_dashboard_index_ttl', 60));
            $data = Cache::remember(AdminDashboardCache::indexKey(), $ttl, fn () => $dashboard->summary());
            $data = $dashboard->withRequestRange($data, $request);
            $data['weeklyReportDay'] = AppSetting::getWeeklyReportDay();
            $data['weeklyReportHour'] = AppSetting::getWeeklyReportHour();
            $data['weeklyReportMinute'] = AppSetting::getWeeklyReportMinute();
            $data['weeklyReportRecipients'] = AppSetting::getWeeklyReportRecipients();

            return Inertia::render('Admin/Dashboard/Index', DashboardViewModel::from($data));
        } catch (\Throwable $e) {
            $this->logDashboardError('admin_dashboard_index_failed', $e);

            return Inertia::render('Admin/Dashboard/Index', DashboardViewModel::empty());
        }
    }

    public function inertiaPilot(DashboardIndexRequest $request, DashboardKpiService $dashboard): Response
    {
        $this->authorizeDashboard();

        try {
            $ttl = max(15, (int) config('cf4_performance.admin_dashboard_index_ttl', 60));
            $data = Cache::remember(AdminDashboardCache::indexKey(), $ttl, fn () => $dashboard->summary());
            $data = $dashboard->withRequestRange($data, $request);

            return Inertia::render('Admin/Dashboard/Index', DashboardViewModel::from($data));
        } catch (\Throwable $e) {
            $this->logDashboardError('admin_dashboard_inertia_pilot_failed', $e);

            return Inertia::render('Admin/Dashboard/Index', DashboardViewModel::empty());
        }
    }

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
