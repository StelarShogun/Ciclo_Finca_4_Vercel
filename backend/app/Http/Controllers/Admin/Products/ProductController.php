<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\CreateProduct;
use App\Actions\Admin\Products\ListProducts;
use App\Actions\Admin\Products\ToggleProductFeatured;
use App\Actions\Admin\Products\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Admin\Products\ProductAdminPayloadService;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminDashboardCache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private ProductAuditLogger $productAudit,
    ) {}

    public function index(Request $request, ListProducts $products)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Product::class);

        if (! $request->wantsJson() && ! $request->ajax()) {
            return redirect()->route('inventory');
        }

        return response()->json($products->handle($request));
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct, ProductAdminPayloadService $payloads)
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', Product::class);

        try {
            $result = $createProduct->handle($request);
            $product = $result->product;

            $this->productAudit->log('product_create', 'Producto creado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();
            AdminDashboardCache::forget();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($payloads->mutationPayload($product, 'Producto creado con éxito'));
            }

            return redirect()->route('inventory')->with('status', 'Producto creado con éxito');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Revisa los errores en el formulario.',
                ], 422);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            Log::error('Product store failed.', SensitiveDataMasker::exceptionContext($e));

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo crear el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function show(Request $request, ProductAdminPayloadService $payloads, $id)
    {
        try {
            $product = $payloads->detail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('view', $product);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($payloads->detailPayload($product, $request));
            }

            return redirect()->route('inventory')->with('error', 'Usa el inventario para ver productos.');
        } catch (ModelNotFoundException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                ], 404);
            }

            return redirect()->route('inventory')->with('error', 'Producto no encontrado');
        } catch (\Throwable $e) {
            Log::error('Product show failed.', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
            ]));

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo cargar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->route('inventory')->with('error', 'No se pudo cargar el producto. Inténtalo de nuevo.');
        }
    }

    public function toggleFeatured(int $id, ToggleProductFeatured $toggleProductFeatured)
    {
        try {
            $product = Product::query()->findOrFail($id);
            Gate::forUser(Auth::guard('admin')->user())->authorize('toggle', $product);

            $payload = $toggleProductFeatured->handle($id);

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el destacado. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id, UpdateProduct $updateProduct, ProductAdminPayloadService $payloads)
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        try {
            $result = $updateProduct->handle($request, $id);
            $product = $result->product;

            $this->productAudit->log('product_update', 'Producto actualizado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();
            AdminDashboardCache::forget();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($payloads->mutationPayload($product, 'Producto actualizado con éxito'));
            }

            return redirect()->route('inventory')->with('status', 'Producto actualizado con éxito');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Revisa los errores en el formulario.',
                ], 422);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            Log::error('product_update_failed', SensitiveDataMasker::exceptionContext($e, [
                'product_id' => $id,
            ]));

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo actualizar el producto. Inténtalo de nuevo.')->withInput();
        }
    }
}
