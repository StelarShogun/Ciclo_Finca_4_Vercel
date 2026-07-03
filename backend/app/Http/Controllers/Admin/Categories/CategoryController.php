<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\StoreParentCategoryRequest;
use App\Models\Category;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDashboardCache;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function createParentCategory()
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Category::class);

        return Inertia::render('Admin/Categories/CreateParent');
    }

    public function storeParentCategory(StoreParentCategoryRequest $request)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Category::class);

        $validated = $request->validated();

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_category_id' => null,
        ]);
        AdminDashboardCache::forget();

        return redirect()
            ->route('categories.parents.create')
            ->with('status', 'Categoría creada correctamente.');
    }

    public function createSubcategory(Request $request)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Category::class);

        // Avoid duplicated names in the parent selector (seeders may have inserted repeated roots).
        $categories = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $perPage = max(5, min(50, $request->integer('per_page', 15)));
        $currentPage = LengthAwarePaginator::resolveCurrentPage('page');
        $hierarchyRows = Category::hierarchyRowsForAdminDisplay();

        $categoriesHierarchy = new LengthAwarePaginator(
            $hierarchyRows->forPage($currentPage, $perPage)->values(),
            $hierarchyRows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        $categoriesHierarchy->withQueryString();

        $subcategoriesByParent = Category::subcategoriesGroupedByCanonicalParent();

        return Inertia::render('Admin/Categories/CreateSubcategory', [
            'categories' => $categories->map(fn (Category $c): array => [
                'category_id' => (int) $c->category_id,
                'name' => $c->name,
            ])->values()->all(),
            'subcategoriesByParent' => $subcategoriesByParent,
            'hierarchy' => $categoriesHierarchy->getCollection()->map(fn ($row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'parent_name' => optional($row->parent)->name,
                'is_parent' => $row->parent_category_id === null,
            ])->values()->all(),
            'pagination' => ListPaginationPayload::from($categoriesHierarchy),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Category::class);

        $validated = $request->validated();

        Category::create($validated);
        AdminDashboardCache::forget();

        return redirect()->back()->with('status', 'Subcategoría creada correctamente.');
    }
}
