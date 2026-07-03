<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\DashboardIndexRequest;
use App\Services\Admin\Dashboard\DashboardKpiService;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use App\ViewModels\Admin\DashboardViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard admin para el SPA Next. Devuelve el mismo payload que la vista
 * Inertia (DashboardViewModel) en JSON, reusando DashboardKpiService y el
 * caché del resumen. La serie de ventas es configurable por rango.
 */
final class DashboardController extends Controller
{
    public function index(DashboardIndexRequest $request, DashboardKpiService $dashboard): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('reports.view');

        try {
            $ttl = max(15, (int) config('cf4_performance.admin_dashboard_index_ttl', 60));
            $data = Cache::remember(AdminDashboardCache::indexKey(), $ttl, fn () => $dashboard->summary());
            $data = $dashboard->withRequestRange($data, $request);

            return response()->json(['data' => DashboardViewModel::from($data)]);
        } catch (\Throwable $e) {
            Log::error('api_admin_dashboard_failed', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => Auth::guard('admin')->id(),
            ]));

            return response()->json([
                'message' => 'No fue posible cargar el dashboard.',
            ], 500);
        }
    }
}
