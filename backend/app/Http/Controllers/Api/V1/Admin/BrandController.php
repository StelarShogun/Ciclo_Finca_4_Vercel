<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brands\StoreBrandRequest;
use App\Http\Requests\Admin\Brands\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Marcas admin para el SPA Next. Replica la lógica JSON del controller web
 * (detección de duplicados exacto/insensible y bloqueo por productos). El
 * controller web Inertia se retira en Bloque 6.
 * ponytail: mismo dup-check que Admin/Brands/BrandController hasta esa limpieza.
 */
final class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Brand::class);

        $query = Brand::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
        }

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $brands = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $brands->getCollection()->transform(
            fn (Brand $brand): array => ['id' => $brand->id, 'name' => $brand->name]
        );

        return response()->json($brands);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Brand::class);

        $name = $request->validated()['name'];

        if ($dup = $this->findDuplicate($name)) {
            return $dup;
        }

        $brand = Brand::create(['name' => $name]);
        ClientStorefrontCache::forgetAfterBrandMutation();

        return response()->json([
            'success' => true,
            'message' => 'Marca creada correctamente.',
            'brand' => ['id' => $brand->id, 'name' => $brand->name],
        ], 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $brand);

        $name = $request->validated()['name'];

        if ($dup = $this->findDuplicate($name, $brand->id)) {
            return $dup;
        }

        $brand->update(['name' => $name]);
        ClientStorefrontCache::forgetAfterBrandMutation();

        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada correctamente.',
            'brand' => ['id' => $brand->id, 'name' => $brand->name],
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
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

        return response()->json(['success' => true, 'message' => 'Marca eliminada correctamente.']);
    }

    /** Devuelve una respuesta 422 si el nombre choca (exacto o por mayúsculas), o null. */
    private function findDuplicate(string $name, ?int $exceptId = null): ?JsonResponse
    {
        $exact = $this->exactNameQuery($name)
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->first();
        if ($exact) {
            return response()->json([
                'success' => false, 'duplicate' => true, 'exact' => true,
                'existing' => ['id' => $exact->id, 'name' => $exact->name],
            ], 422);
        }

        $ci = Brand::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->first();
        if ($ci) {
            return response()->json([
                'success' => false, 'duplicate' => true, 'exact' => false,
                'existing' => ['id' => $ci->id, 'name' => $ci->name],
            ], 422);
        }

        return null;
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
