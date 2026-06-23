<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\CreateProduct;
use App\Actions\Admin\Products\ToggleProductFeatured;
use App\Actions\Admin\Products\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Services\Media\ProductImageUrls;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private ProductAuditLogger $productAudit,
    ) {}

    public function index(Request $request)
    {
        if (! $request->wantsJson() && ! $request->ajax()) {
            return redirect()->route('inventory');
        }

        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $products = Product::with(['category', 'supplier'])
            ->orderBy('product_id', 'desc')
            ->paginate($perPage);

        return response()->json($products);
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct)
    {
        try {
            $result = $createProduct->handle($request);
            $product = $result->product;

            $this->productAudit->log('product_create', 'Producto creado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto creado con éxito',
                    'data' => $product->load(['category.parent', 'supplier', 'classificationValues.dimension']),
                ]);
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
            Log::error('Product store failed.', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear el producto. Inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->back()->with('error', 'No se pudo crear el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['category.parent', 'supplier', 'brands', 'classificationValues.dimension', 'variants'])->findOrFail($id);

            if (request()->wantsJson() || request()->ajax()) {
                $productData = $product->toArray();
                $firstBrand = $product->brands->first();
                $productData['brand_id'] = $firstBrand instanceof Brand ? $firstBrand->id : null;
                $productData['classification_value_ids'] = $product->classificationValues->pluck('id')->values()->all();
                // La generación de URLs puede fallar si el media vive en un disco
                // no disponible (p. ej. el disco local 'public' es de solo lectura en
                // Vercel). Degradamos a vacío para que el producto siga cargando.
                $productData['media_main'] = self::safeMediaUrl(fn () => $product->getFirstMediaUrl('main_image'));
                $productData['media_gallery'] = $product->getMedia('gallery')
                    ->map(fn ($m) => self::safeMediaUrl(fn () => $m->getUrl()))
                    ->filter()
                    ->values()
                    ->toArray();
                $productData['uses_placeholder_image'] = ProductImageUrls::usesPlaceholder($product);
                $productData['placeholder_icon_class'] = ProductImageUrls::placeholderIconClass($product);
                $variantIds = $product->variants->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

                $lockedVariantIds = [];
                if ($variantIds !== []) {
                    $lockedVariantIds = SaleItem::query()
                        ->whereIn('product_id', $variantIds)
                        ->distinct()
                        ->pluck('product_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }
                $lockedSet = array_fill_keys($lockedVariantIds, true);

                $productData['variants'] = $product->variants
                    ->map(function (Product $v) use ($lockedSet) {
                        return [
                            'product_id' => (int) $v->product_id,
                            'name' => (string) $v->name,
                            'status' => (string) $v->status,
                            'stock_current' => (int) $v->stock_current,
                            'sale_price' => (string) $v->sale_price,
                            'sku' => $v->displaySku(),
                            'sku_custom' => $v->sku,
                            'sku_locked' => isset($lockedSet[(int) $v->product_id]),
                        ];
                    })
                    ->values()
                    ->all();

                return response()->json([
                    'success' => true,
                    'data' => $productData,
                ]);
            }

            return redirect()->route('inventory')->with('error', 'Usa el inventario para ver productos.');
        } catch (ModelNotFoundException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                ], 404);
            }

            return redirect()->route('inventory')->with('error', 'Producto no encontrado');
        } catch (\Throwable $e) {
            Log::error('Product show failed.', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'at' => $e->getFile().':'.$e->getLine(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                $payload = [
                    'success' => false,
                    'message' => 'No se pudo cargar el producto. Inténtalo de nuevo.',
                ];

                if (config('app.debug')) {
                    $payload['error'] = $e->getMessage();
                }

                return response()->json($payload, 500);
            }

            return redirect()->route('inventory')->with('error', 'No se pudo cargar el producto. Inténtalo de nuevo.');
        }
    }

    /**
     * Resuelve una URL de media tolerando fallos de disco (devuelve '' si falla).
     */
    private static function safeMediaUrl(callable $resolver): string
    {
        try {
            return (string) $resolver();
        } catch (\Throwable) {
            return '';
        }
    }

    public function toggleFeatured(int $id, ToggleProductFeatured $toggleProductFeatured)
    {
        try {
            $payload = $toggleProductFeatured->handle($id);

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el destacado. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id, UpdateProduct $updateProduct)
    {
        try {
            $result = $updateProduct->handle($request, $id);
            $product = $result->product;

            $this->productAudit->log('product_update', 'Producto actualizado.', $result->auditContext);
            ClientStorefrontCache::forgetAfterProductMutation();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto actualizado con éxito',
                    'data' => $product->load(['category.parent', 'supplier', 'classificationValues.dimension']),
                ]);
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
            Log::error('Error updating product: '.$e->getMessage(), [
                'product_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

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
