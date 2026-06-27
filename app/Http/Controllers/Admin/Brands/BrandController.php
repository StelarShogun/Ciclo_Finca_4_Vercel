<?php

namespace App\Http\Controllers\Admin\Brands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brands\StoreBrandRequest;
use App\Http\Requests\Admin\Brands\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Brand::class);

        $query = Brand::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
        }

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $brands = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands->getCollection()
                ->map(fn (Brand $brand): array => ['id' => $brand->id, 'name' => $brand->name])
                ->values()
                ->all(),
            'pagination' => ListPaginationPayload::from($brands),
            'filters' => ['name' => (string) $request->input('name', '')],
        ]);
    }

    public function store(StoreBrandRequest $request)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Brand::class);

        $validated = $request->validated();
        $name = $validated['name'];

        // Exact case-sensitive match
        $exactMatch = $this->exactNameQuery($name)->first();
        if ($exactMatch) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'exact' => true,
                'existing' => ['id' => $exactMatch->id, 'name' => $exactMatch->name],
            ], 422);
        }

        // Case-insensitive match (different capitalization)
        $existing = Brand::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'exact' => false,
                'existing' => ['id' => $existing->id, 'name' => $existing->name],
            ], 422);
        }

        $brand = Brand::create(['name' => $name]);
        ClientStorefrontCache::forgetAfterBrandMutation();

        return response()->json([
            'success' => true,
            'message' => 'Marca creada correctamente.',
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $brand);

        $validated = $request->validated();
        $name = $validated['name'];

        // Exact case-sensitive match (excluding self)
        $exactMatch = $this->exactNameQuery($name)
            ->where('id', '!=', $brand->id)
            ->first();
        if ($exactMatch) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'exact' => true,
                'existing' => ['id' => $exactMatch->id, 'name' => $exactMatch->name],
            ], 422);
        }

        // Case-insensitive match excluding self (different capitalization)
        $existing = Brand::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $brand->id)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'exact' => false,
                'existing' => ['id' => $existing->id, 'name' => $existing->name],
            ], 422);
        }

        $brand->update(['name' => $name]);
        ClientStorefrontCache::forgetAfterBrandMutation();

        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada correctamente.',
            'brand' => $brand,
        ]);
    }

    public function destroy(Brand $brand)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $brand);

        try {
            $productCount = $brand->products()->count();
        } catch (\Throwable $e) {
            Log::error('brand_delete_product_count_failed', SensitiveDataMasker::exceptionContext($e, [
                'brand_id' => $brand->id,
                'admin_id' => auth('admin')->id(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'No fue posible verificar los productos asociados. Inténtelo nuevamente.',
            ], 500);
        }

        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'blocked' => true,
                'message' => "No se puede eliminar \"{$brand->name}\" porque está asociada a {$productCount} ".($productCount === 1 ? 'producto' : 'productos').'.',
            ], 422);
        }

        $brand->delete();
        ClientStorefrontCache::forgetAfterBrandMutation();

        return response()->json([
            'success' => true,
            'message' => 'Marca eliminada correctamente.',
        ]);
    }

    private function exactNameQuery(string $name): Builder
    {
        $query = Brand::query();

        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw('BINARY name = ?', [$name]);
        }

        return $query->where('name', $name);
    }
}
