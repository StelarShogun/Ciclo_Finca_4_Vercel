<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\Products\AdminInventoryProductQuery;
use App\Services\Admin\Products\InventoryClassificationFilterService;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;

final class ProductInventoryController extends Controller
{
    public function __construct(
        private AdminInventoryProductQuery $inventoryProductQuery,
        private InventoryClassificationFilterService $classificationFilters,
    ) {}

    public function index(Request $request)
    {
        $query = $this->inventoryProductQuery->filteredQuery($request)->with(['category.parent', 'supplier']);
        $lowStockProductsCount = Product::query()->lowStockAlert()->count();
        $hasClassificationSelections = collect((array) $request->input('classifications', []))
            ->contains(fn ($value) => is_string($value) && trim($value) !== '');
        $activeClassificationFilters = $hasClassificationSelections
            ? $this->classificationFilters->activeFilters($request)
            : [];

        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $paginator = $query->paginate($perPage)->withQueryString();

        $products = collect($paginator->items())->map(function ($product) {
            if (! $product instanceof Product) {
                return null;
            }

            return (object) [
                'product_id' => $product->product_id,
                'id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                'image' => $product->image ?? 'default.png',
                'category' => (object) ['name' => optional($product->category)->name ?? 'Uncategorized'],
                'stock' => $product->stock_current,
                'stock_status_class' => $product->adminInventoryStockBadgeClass(),
                'price' => $product->sale_price,
                'status' => ucfirst(str_replace('_', ' ', $product->status)),
                'status_class' => $product->status === 'active' ? 'success' :
                                ($product->status === 'inactive' ? 'warning' : 'secondary'),
            ];
        })->filter();

        $categories = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $subcategoriesByParent = Category::subcategoriesGroupedByCanonicalParent();

        return view('admin.products.inventory', [
            'products' => $products,
            'paginator' => $paginator,
            'lowStockProductsCount' => $lowStockProductsCount,
            'categories' => $categories,
            'subcategoriesByParent' => $subcategoriesByParent,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['supplier_id', 'name']),
            'inventoryExportsQuery' => AdminInventoryExportQuery::queryStringFromRequest($request),
            'activeClassificationFilters' => $activeClassificationFilters,
            'hasClassificationSelections' => $hasClassificationSelections,
        ]);
    }
}
