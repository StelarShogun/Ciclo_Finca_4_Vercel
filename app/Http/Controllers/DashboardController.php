<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\AppSetting;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\Admin\AdminPdfExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Debe iniciar sesión como administrador para acceder.');
        }

        try {
            $data = $this->gatherDashboardData();

            $data['weeklyReportDay'] = AppSetting::getWeeklyReportDay();
            $data['weeklyReportHour'] = AppSetting::getWeeklyReportHour();
            $data['weeklyReportMinute'] = AppSetting::getWeeklyReportMinute();
            $data['weeklyReportRecipients'] = AppSetting::getWeeklyReportRecipients();

            return view('admin.dashboard', $data);

        } catch (\Exception $e) {
            Log::error('Error en DashboardController: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('admin.dashboard', [
                'totalProducts' => 0,
                'totalSuppliers' => 0,
                'totalCategories' => 0,
                'todaySales' => 0,
                'lowStockProducts' => 0,
                'lowStockProductsList' => collect(),
                'recentSales' => collect(),
                'salesByDay' => collect(),
                'productsByCategory' => collect(),
                'salesTrend' => 0,
                'monthlySales' => 0,
                'monthlyTrend' => 0,
                'topProducts' => collect(),
                'topSuppliers' => collect(),
                'error' => 'Error al cargar datos del dashboard',
            ]);
        }
    }

    public function getDashboardData()
    {
        try {
            if (! Schema::hasTable('products') || ! Schema::hasTable('categories') || ! Schema::hasTable('suppliers') || ! Schema::hasTable('sales')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database tables not found',
                ], 500);
            }

            $totalProducts = Product::count();
            $totalSuppliers = Supplier::count();
            $totalCategories = Category::count();

            $todaySales = Sale::whereDate('sale_date', Carbon::today())
                ->where('status', 'completed')
                ->sum('total');

            $lowStockProducts = Product::whereColumn('stock_current', '<', 'stock_minimum')->count();

            return response()->json([
                'success' => true,
                'totalProducts' => $totalProducts,
                'totalSuppliers' => $totalSuppliers,
                'totalCategories' => $totalCategories,
                'todaySales' => $todaySales,
                'lowStockProducts' => $lowStockProducts,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getDashboardData: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard',
            ], 500);
        }
    }

    public function getChartData(Request $request)
    {
        $period = $request->get('period', '7d');

        try {
            $startDate = $this->getStartDate($period)->startOfDay();

            $salesRows = Sale::query()
                ->select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('SUM(total) as total')
                )
                ->where('sale_date', '>=', $startDate)
                ->where('status', 'completed')
                ->groupBy(DB::raw('DATE(sale_date)'))
                ->orderBy('date')
                ->get();

            $salesData = $this->fillSalesChartSeries(collect($salesRows), $startDate, Carbon::now()->startOfDay());

            $categoryData = Category::withCount(['products' => function ($query) {
                $query->where('status', 'active');
            }])
                ->orderBy('products_count', 'desc')
                ->get()
                ->map(function (Category $category) {
                    return [
                        'categoria' => $category->name,
                        'total' => $category->products_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'sales' => $salesData,
                'categories' => $categoryData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Debe iniciar sesión como administrador para acceder.');
        }

        $format = $request->get('format', 'pdf');
        $period = $request->get('period', '7d');
        if (! in_array($period, ['7d', '30d', '90d'], true)) {
            $period = '7d';
        }

        try {
            if (! Schema::hasTable('products') || ! Schema::hasTable('categories') || ! Schema::hasTable('suppliers') || ! Schema::hasTable('sales')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database tables not found',
                ], 500);
            }

            $data = $this->gatherDashboardData();

            $startDate = $this->getStartDate($period)->startOfDay();
            $salesRows = Sale::query()
                ->select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('SUM(total) as total')
                )
                ->where('sale_date', '>=', $startDate)
                ->where('status', 'completed')
                ->groupBy(DB::raw('DATE(sale_date)'))
                ->orderBy('date')
                ->get();
            $salesChartSeries = $this->fillSalesChartSeries(collect($salesRows), $startDate, Carbon::now()->startOfDay());

            $filterLines = [
                'Gráfico de ventas: '.$this->chartPeriodLabel($period),
            ];

            $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

            if ($format === 'pdf') {
                $pdfData = array_merge($data, [
                    'salesChartSeries' => $salesChartSeries,
                    'chartPeriodLabel' => $this->chartPeriodLabel($period),
                    'pdfTitle' => 'Reporte del dashboard',
                    'pdfSubtitle' => 'Resumen operativo — Ciclo Finca 4',
                    'logoPath' => is_file($logoPath) ? $logoPath : null,
                    'filterLines' => $filterLines,
                    'generatedFor' => 'Administración',
                ]);

                return app(AdminPdfExportService::class)->download(
                    'admin.exports.dashboard-pdf',
                    $pdfData,
                    'dashboard'
                );
            }

            if ($format === 'excel') {
                return $this->exportExcel($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Formato no soportado',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error al exportar dashboard: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    private function chartPeriodLabel(string $period): string
    {
        return match ($period) {
            '30d' => 'últimos 30 días',
            '90d' => 'últimos 90 días',
            default => 'últimos 7 días',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherDashboardData(): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('categories') || ! Schema::hasTable('suppliers') || ! Schema::hasTable('sales')) {
            throw new \RuntimeException('Database tables not found');
        }

        if (config('app.debug')) {
            $categoriasExistentes = Category::count();
            Log::debug("Categorías en DB: {$categoriasExistentes}");
        }

        $totalProducts = Product::count();
        $totalSuppliers = Supplier::count();
        $totalCategories = Category::count();

        $todaySales = Sale::whereDate('sale_date', Carbon::today())
            ->where('status', 'completed')
            ->sum('total');

        $lowStockProducts = Product::lowStockAlert()->count();

        $lowStockProductsList = Product::with(['category', 'supplier'])
            ->lowStockAlert()
            ->orderBy('stock_current', 'asc')
            ->limit(5)
            ->get();

        $recentSales = Sale::with(['client'])
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get();

        $salesByDay = Sale::select(
            DB::raw('DATE(sale_date) as date'),
            DB::raw('COALESCE(SUM(total), 0) as total')
        )
            ->where('sale_date', '>=', Carbon::now()->subDays(6))
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $productsByCategory = Category::withCount(['products' => function ($query) {
            $query->where('status', 'active');
        }])
            ->orderBy('products_count', 'desc')
            ->get()
            ->map(function (Category $categoria) {
                return [
                    'categoria' => $categoria->name,
                    'total' => $categoria->products_count,
                ];
            });

        $yesterdaySales = Sale::whereDate('sale_date', Carbon::yesterday())
            ->where('status', 'completed')
            ->sum('total');

        $salesTrend = $this->calculateTrend($todaySales, $yesterdaySales);

        $monthlySales = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('total');

        $lastMonthSales = Sale::whereMonth('sale_date', Carbon::now()->subMonth()->month)
            ->whereYear('sale_date', Carbon::now()->subMonth()->year)
            ->where('status', 'completed')
            ->sum('total');

        $monthlyTrend = $this->calculateTrend($monthlySales, $lastMonthSales);

        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.sale_date', '>=', Carbon::now()->subDays(30))
            ->select(
                'products.name',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as total_vendido'),
                DB::raw('SUM(sale_items.total) as ingresos')
            )
            ->groupBy('products.product_id', 'products.name', 'products.image')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        $topSuppliers = Supplier::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(5)
            ->get();

        return compact(
            'totalProducts',
            'totalSuppliers',
            'totalCategories',
            'todaySales',
            'lowStockProducts',
            'lowStockProductsList',
            'recentSales',
            'salesByDay',
            'productsByCategory',
            'salesTrend',
            'monthlySales',
            'monthlyTrend',
            'topProducts',
            'topSuppliers'
        );
    }

    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getStartDate($period)
    {
        switch ($period) {
            case '7d':
                return Carbon::now()->subDays(6);
            case '30d':
                return Carbon::now()->subDays(29);
            case '90d':
                return Carbon::now()->subDays(89);
            default:
                return Carbon::now()->subDays(6);
        }
    }

    /**
     * @param  iterable<int, Sale|object>  $rows
     * @return array<int, array{date: string, total: float}>
     */
    private function fillSalesChartSeries(iterable $rows, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $d = data_get($row, 'date');
            $key = $d instanceof Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
            $byDate[$key] = (float) data_get($row, 'total');
        }

        $out = [];
        $cursor = $rangeStart->copy()->startOfDay();
        $end = $rangeEnd->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $out[] = ['date' => $key, 'total' => $byDate[$key] ?? 0.0];
            $cursor->addDay();
        }

        return $out;
    }

    private function exportExcel(array $data)
    {
        return response()->json($data);
    }
}
