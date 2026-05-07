<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function createParentCategory()
    {
        return view('categories.parents.create');
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

    public function createSubcategory()
    {
        // Avoid duplicated names in the parent selector (seeders may have inserted repeated roots).
        $categories = Category::query()
            ->selectRaw('MIN(category_id) as category_id, name')
            ->whereNull('parent_category_id')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $categoriesHierarchy = Category::hierarchyRowsForAdminDisplay();

        $subcategoriesByParent = Category::subcategoriesGroupedByCanonicalParent();

        return view('categories.subcategories.create', compact('categories', 'categoriesHierarchy', 'subcategoriesByParent'));
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
