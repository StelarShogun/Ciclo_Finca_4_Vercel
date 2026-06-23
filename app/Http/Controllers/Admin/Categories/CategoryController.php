<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Client\Inertia\ListPaginationPayload;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function createParentCategory()
    {
        return Inertia::render('Admin/Categories/CreateParent');
    }

    public function storeParentCategory(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($query) => $query->whereNull('parent_category_id')
                ),
            ],
            'description' => 'nullable|string',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_category_id' => null,
        ]);

        return redirect()
            ->route('categories.parents.create')
            ->with('status', 'Categoría creada correctamente.');
    }

    public function createSubcategory(Request $request)
    {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('parent_category_id', $request->parent_category_id);
                }),
            ],
            'description' => 'nullable|string',
            'parent_category_id' => 'required|exists:categories,category_id',
        ], [
            'parent_category_id.required' => 'Debe seleccionar una categoría padre.',
            'name.unique' => 'Ya existe una subcategoría con ese nombre bajo la categoría padre seleccionada.',
        ]);

        Category::create($validated);

        return redirect()->back()->with('status', 'Subcategoría creada correctamente.');
    }
}
