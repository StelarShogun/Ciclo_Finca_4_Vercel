<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Debe iniciar sesión como administrador para acceder.');
        }

        try {
            // Abort early if required tables are missing to avoid misleading query errors
            if (! \Schema::hasTable('products') || ! \Schema::hasTable('categories') || ! \Schema::hasTable('suppliers') || ! \Schema::hasTable('sales')) {
                throw new \Exception('Database tables not found');
            }

            if (config('app.debug')) {
                $categoriasExistentes = Category::count();
                \Log::debug("Categorías en DB: {$categoriasExistentes}");
            }

            $totalProducts = Product::count();
            $totalSuppliers = Supplier::count();
            $totalCategories = Category::count();

            $todaySales = Sale::whereDate('sale_date', Carbon::today())
                ->where('status', 'completed')
                ->sum('total');

            $lowStockProducts = Product::where('stock_current', '<', 10)
                ->where('status', 'active')
                ->count();

            $lowStockProductsList = Product::with(['category', 'supplier'])
                ->where('stock_current', '<', 10)
                ->where('status', 'active')
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
                ->map(function ($categoria) {
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

            // Join through sale_items to rank products by units sold in the last 30 days
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

            return view('admin.dashboard', compact(
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
            ));

        } catch (\Exception $e) {
            \Log::error('Error en DashboardController: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return zeroed-out defaults so the view renders gracefully instead of crashing
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
            if (! \Schema::hasTable('products') || ! \Schema::hasTable('categories') || ! \Schema::hasTable('suppliers') || ! \Schema::hasTable('sales')) {
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

            $lowStockProducts = Product::where('stock_current', '<', 10)
                ->where('status', 'active')
                ->count();

            return response()->json([
                'success' => true,
                'totalProducts' => $totalProducts,
                'totalSuppliers' => $totalSuppliers,
                'totalCategories' => $totalCategories,
                'todaySales' => $todaySales,
                'lowStockProducts' => $lowStockProducts,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getDashboardData: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard',
            ], 500);
        }
    }

    public function getChartData(Request $request)
    {
        // Default to the last 7 days if no period is specified
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

            $salesData = $this->fillSalesChartSeries($salesRows, $startDate, Carbon::now()->startOfDay());

            $categoryData = Category::withCount(['products' => function ($query) {
                $query->where('status', 'active');
            }])
                ->orderBy('products_count', 'desc')
                ->get()
                ->map(function ($category) {
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
        $format = $request->get('format', 'pdf');

        try {
            $data = $this->getDashboardDataInternal();

            if ($format === 'pdf') {
                return $this->exportPDF($data);
            } elseif ($format === 'excel') {
                return $this->exportExcel($data);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato no soportado',
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    // Returns 100% growth when the previous value is zero to avoid division by zero
    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    // Subtracts one less day than the period label implies to include today in the range
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
     * Una entrada por día en el rango con total 0 si no hubo ventas (el gráfico no queda “vacío”).
     *
     * @param  Collection<int, object>  $rows
     * @return array<int, array{date: string, total: float}>
     */
    private function fillSalesChartSeries($rows, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $d = $row->date;
            $key = $d instanceof Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
            $byDate[$key] = (float) $row->total;
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

    private function getDashboardDataInternal()
    {
        return [
            'totalProducts' => Product::count(),
            'totalSuppliers' => Supplier::count(),
            'todaySales' => Sale::whereDate('sale_date', Carbon::today())
                ->where('status', 'completed')
                ->sum('total'),
            'lowStockProducts' => Product::where('stock_current', '<', 10)
                ->where('status', 'active')
                ->count(),
            'monthlySales' => Sale::whereMonth('sale_date', Carbon::now()->month)
                ->whereYear('sale_date', Carbon::now()->year)
                ->where('status', 'completed')
                ->sum('total'),
        ];
    }

    private function exportPDF($data)
    {
        // TODO: implement proper PDF generation; currently falls back to a print view
        return view('exports.dashboard-pdf', $data);
    }

    private function exportExcel($data)
    {
        // TODO: implement Excel export via a library such as Laravel Excel
        return response()->json($data);
    }
}
