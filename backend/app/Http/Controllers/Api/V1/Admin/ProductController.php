<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Products\ActivateProduct;
use App\Actions\Admin\Products\CreateProduct;
use App\Actions\Admin\Products\DeactivateProduct;
use App\Actions\Admin\Products\ForceDeleteProduct;
use App\Actions\Admin\Products\ListProducts;
use App\Actions\Admin\Products\ToggleProductFeatured;
use App\Actions\Admin\Products\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Admin\Products\ProductAdminPayloadService;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Admin\Products\ProductPayloadBuilder;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Productos admin para el SPA Next. Reusa las Actions (List/Create/Update),
 * ProductAdminPayloadService y ProductPayloadBuilder, replicando auditoría e
 * invalidación de caché del controller web. Galería/variantes/clasificaciones
 * y filtros de búsqueda: próximos slices.
 */
final class ProductController extends Controller
{
    public function __construct(private ProductAuditLogger $productAudit) {}

    public function index(Request $request, ListProducts $products): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        return response()->json($products->handle($request));
    }

    /** Datos de referencia para el formulario (categorías, subcategorías, marcas, proveedores, estados). */
    public function formOptions(ProductPayloadBuilder $builder): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Product::class);

        return response()->json(['data' => $builder->formOptions()]);
    }

    public function show(int|string $id, ProductAdminPayloadService $payloads, Request $request): JsonResponse
    {
        try {
            $product = $payloads->detail($id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $product);

        // detailPayload ya devuelve {success, data: ProductResource}.
        return response()->json($payloads->detailPayload($product, $request));
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct, ProductAdminPayloadService $payloads): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Product::class);

        try {
            $result = $createProduct->handle($request);

            $this->productAudit->log('product_create', 'Producto creado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();
            AdminDashboardCache::forget();

            return response()->json($payloads->mutationPayload($result->product, 'Producto creado con éxito'), 201);
        } catch (\Throwable $e) {
            Log::error('api_product_store_failed', SensitiveDataMasker::exceptionContext($e));

            return response()->json(['message' => 'No se pudo crear el producto. Inténtalo de nuevo.'], 500);
        }
    }

    public function update(UpdateProductRequest $request, int|string $id, UpdateProduct $updateProduct, ProductAdminPayloadService $payloads): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        try {
            $result = $updateProduct->handle($request, $id);

            $this->productAudit->log('product_update', 'Producto actualizado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();
            AdminDashboardCache::forget();

            return response()->json($payloads->mutationPayload($result->product, 'Producto actualizado con éxito'));
        } catch (\Throwable $e) {
            Log::error('api_product_update_failed', SensitiveDataMasker::exceptionContext($e, ['product_id' => $id]));

            return response()->json(['message' => 'No se pudo actualizar el producto. Inténtalo de nuevo.'], 500);
        }
    }

    public function activate(int $id, ActivateProduct $action): JsonResponse
    {
        return $this->runMutation($id, 'update', fn () => $action->handle($id), 'No se pudo activar el producto.');
    }

    /** "Soft delete" = desactivar (deja inactivo, no borra la fila). */
    public function deactivate(int $id, DeactivateProduct $action): JsonResponse
    {
        return $this->runMutation($id, 'delete', fn () => $action->handle($id), 'No se pudo desactivar el producto.');
    }

    public function toggleFeatured(int $id, ToggleProductFeatured $action): JsonResponse
    {
        return $this->runMutation($id, 'update', fn () => $action->handle($id), 'No se pudo actualizar el producto.');
    }

    /** Eliminación permanente. */
    public function forceDelete(int $id, ForceDeleteProduct $action): JsonResponse
    {
        return $this->runMutation($id, 'forceDelete', fn () => $action->handle($id), 'No se pudo eliminar el producto.');
    }

    /**
     * Orquesta una acción rápida: autoriza sobre el producto, ejecuta, invalida
     * cachés y devuelve el payload de la Action. Maneja errores de forma segura.
     *
     * @param  callable():array<string,mixed>  $run
     */
    private function runMutation(int $id, string $ability, callable $run, string $failMessage): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize($ability, $product);

        try {
            $payload = $run();
            ClientStorefrontCache::forgetAfterProductMutation();
            AdminDashboardCache::forget();

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::error('api_product_action_failed', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
                'ability' => $ability,
            ]));

            return response()->json(['message' => $failMessage], 500);
        }
    }
}
