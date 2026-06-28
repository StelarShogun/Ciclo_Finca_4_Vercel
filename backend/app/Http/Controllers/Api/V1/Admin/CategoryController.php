<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\StoreParentCategoryRequest;
use App\Models\Category;
use App\Support\AdminDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Categorías admin para el SPA Next. Reusa los helpers de jerarquía del modelo
 * (deduplican raíces/sub repetidas) y las FormRequests existentes. Igual que el
 * web Inertia, la superficie es: listar jerarquía + crear padre + crear sub.
 * ponytail: sin paginación; el catálogo de categorías es pequeño (decenas).
 */
final class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Category::class);

        // Raíces canónicas (una por nombre) para el selector de padre.
        $parents = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c): array => ['category_id' => (int) $c->category_id, 'name' => $c->name])
            ->values();

        $hierarchy = Category::hierarchyRowsForAdminDisplay()
            ->map(fn (Category $row): array => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'parent_name' => $row->parent?->name,
                'is_parent' => $row->parent_category_id === null,
            ])
            ->values();

        return response()->json(['data' => [
            'parents' => $parents,
            'hierarchy' => $hierarchy,
        ]]);
    }

    public function storeParent(StoreParentCategoryRequest $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Category::class);

        $validated = $request->validated();
        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_category_id' => null,
        ]);
        AdminDashboardCache::forget();

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente.',
            'category' => ['category_id' => (int) $category->category_id, 'name' => $category->name],
        ], 201);
    }

    public function storeSubcategory(StoreCategoryRequest $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Category::class);

        $category = Category::create($request->validated());
        AdminDashboardCache::forget();

        return response()->json([
            'success' => true,
            'message' => 'Subcategoría creada correctamente.',
            'category' => ['category_id' => (int) $category->category_id, 'name' => $category->name],
        ], 201);
    }
}
