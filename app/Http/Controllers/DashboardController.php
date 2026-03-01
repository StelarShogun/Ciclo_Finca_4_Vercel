<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Verificación adicional de seguridad
            if (!Auth::check()) {
                return redirect()->route('login.show')
                    ->with('error', 'Debe iniciar sesión para acceder.');
            }
            
            if (!method_exists(Auth::user(), 'isAdmin') || !Auth::user()->isAdmin()) {
                Auth::logout();
                return redirect()->route('login.show')
                    ->with('error', 'Acceso denegado. Solo administradores pueden acceder.');
            }
            
        } catch (\Exception $authException) {
            \Log::error('Error de autenticación en DashboardController: ' . $authException->getMessage());
            return redirect()->route('login.show')
                ->with('error', 'Error de autenticación.');
        }
        
        try {
            // Verificar que las tablas existan y tengan datos
            if (!\Schema::hasTable('products') || !\Schema::hasTable('categories') || !\Schema::hasTable('suppliers') || !\Schema::hasTable('sales')) {
                throw new \Exception('Database tables not found');
            }
            
            // Verificar categorías existentes (solo en modo debug)
            if (config('app.debug')) {
                $categoriasExistentes = Category::count();
                \Log::debug("Categorías en DB: {$categoriasExistentes}");
            }
            
            // Obtener estadísticas principales
            $totalProducts = Product::count();
            $totalSuppliers = Supplier::count();
            $totalCategories = Category::count();

            // Daily sales
            $todaySales = Sale::whereDate('sale_date', Carbon::today())
                ->where('status', 'completed')
                ->sum('total');

            // Productos con stock bajo (menos de 10 unidades)
            $lowStockProducts = Product::where('stock_current', '<', 10)
                ->where('status', 'active')
                ->count();

            // Lista de productos con stock bajo
            $lowStockProductsList = Product::with(['category', 'supplier'])
                ->where('stock_current', '<', 10)
                ->where('status', 'active')
                ->orderBy('stock_current', 'asc')
                ->limit(5)
                ->get();

            // Recent sales (last 5)
            $recentSales = Sale::with(['customer'])
                ->orderBy('sale_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function($sale) {
                    if (!$sale->customer) {
                        $sale->setRelation('customer', (object) ['nombre' => 'Unassigned', 'apellido' => '']);
                    }
                    return $sale;
                });

            // Sales by day (last 7 days)
            $salesByDay = Sale::select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('COALESCE(SUM(total), 0) as total')
                )
                ->where('sale_date', '>=', Carbon::now()->subDays(6))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Productos por categoría - usando relación Eloquent
            $productsByCategory = Category::withCount(['products' => function($query) {
                    $query->where('status', 'active');
                }])
                ->orderBy('products_count', 'desc')
                ->get()
                ->map(function($categoria) {
                    return [
                        'categoria' => $categoria->name,
                        'total' => $categoria->products_count
                    ];
                });

            // Calculate trends
            $yesterdaySales = Sale::whereDate('sale_date', Carbon::yesterday())
                ->where('status', 'completed')
                ->sum('total');

            $salesTrend = $this->calculateTrend($todaySales, $yesterdaySales);

            // Current month sales
            $monthlySales = Sale::whereMonth('sale_date', Carbon::now()->month)
                ->whereYear('sale_date', Carbon::now()->year)
                ->where('status', 'completed')
                ->sum('total');

            // Previous month sales
            $lastMonthSales = Sale::whereMonth('sale_date', Carbon::now()->subMonth()->month)
                ->whereYear('sale_date', Carbon::now()->subMonth()->year)
                ->where('status', 'completed')
                ->sum('total');

            $monthlyTrend = $this->calculateTrend($monthlySales, $lastMonthSales);

            // Top selling products
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

            // Proveedores con más productos
            $topSuppliers = Supplier::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(5)
                ->get();

            return view('dashboard', compact(
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
            // Log del error para debugging
            \Log::error('Error en DashboardController: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // En caso de error, devolver datos por defecto pero logear el error
            return view('dashboard', [
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
                'error' => 'Error al cargar datos del dashboard'
            ]);
        }
    }

    /**
     * Obtener datos del dashboard en formato JSON
     */
    public function getDashboardData()
    {
        try {
            if (!\Schema::hasTable('products') || !\Schema::hasTable('categories') || !\Schema::hasTable('suppliers') || !\Schema::hasTable('sales')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database tables not found'
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
                'lowStockProducts' => $lowStockProducts
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getDashboardData: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard'
            ], 500);
        }
    }

    /**
     * Obtener datos para gráficos via AJAX
     */
    public function getChartData(Request $request)
    {
        $period = $request->get('period', '7d');
        
        try {
            $startDate = $this->getStartDate($period);
            
            // Sales by day
            $salesData = Sale::select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('SUM(total) as total')
                )
                ->where('sale_date', '>=', $startDate)
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $categoryData = Category::withCount(['products' => function($query) {
                    $query->where('status', 'active');
                }])
                ->orderBy('products_count', 'desc')
                ->get()
                ->map(function($category) {
                    return [
                        'categoria' => $category->name,
                        'total' => $category->products_count
                    ];
                });

            return response()->json([
                'success' => true,
                'sales' => $salesData,
                'categories' => $categoryData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte del dashboard
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'pdf');
        
        try {
            // Obtener datos del dashboard
            $data = $this->getDashboardDataInternal();
            
            if ($format === 'pdf') {
                return $this->exportPDF($data);
            } elseif ($format === 'excel') {
                return $this->exportExcel($data);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato no soportado'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular tendencia porcentual
     */
    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Obtener fecha de inicio según el período
     */
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
     * Obtener datos completos del dashboard para uso interno
     */
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
                ->sum('total')
        ];
    }

    /**
     * Exportar a PDF
     */
    private function exportPDF($data)
    {
        // Implementar exportación a PDF
        // Por ahora, redirigir a una vista de impresión
        return view('exports.dashboard-pdf', $data);
    }

    /**
     * Exportar a Excel
     */
    private function exportExcel($data)
    {
        // Implementar exportación a Excel
        // Por ahora, devolver JSON
        return response()->json($data);
    }
}
