<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Admin\Products\ProductMediaService;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Support\AdminDashboardCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Galería de imágenes del producto para el SPA Next. Reusa ProductMediaService
 * (move + sanitización + colecciones spatie). Devuelve media con id+url
 * (el detalle/ProductResource sólo expone urls).
 */
final class ProductGalleryController extends Controller
{
    public function __construct(private ProductMediaService $media) {}

    public function index(int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $product);

        return response()->json(['data' => $this->payload($product)]);
    }

    public function store(int $id, Request $request): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
        ]);

        // addGalleryImage puede lanzar ValidationException (sanitización) -> 422.
        $this->media->addGalleryImage($product, $request->file('image'));
        $this->forgetCaches();

        return response()->json(['data' => $this->payload($product->refresh())], 201);
    }

    public function promote(int $id, int $mediaId): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        $this->media->promoteGalleryImageToMain($product, $mediaId);
        $this->forgetCaches();

        return response()->json(['data' => $this->payload($product->refresh())]);
    }

    public function destroy(int $id, int $mediaId): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $product);

        $this->media->removeGalleryImage($product, $mediaId);
        $this->forgetCaches();

        return response()->json(['data' => $this->payload($product->refresh())]);
    }

    /**
     * @return array{main: array{id:int,url:string}|null, gallery: list<array{id:int,url:string}>}
     */
    private function payload(Product $product): array
    {
        $main = $product->getFirstMedia('main_image');

        return [
            'main' => $main ? ['id' => (int) $main->id, 'url' => $main->getUrl()] : null,
            'gallery' => $product->getMedia('gallery')
                ->map(fn ($m) => ['id' => (int) $m->id, 'url' => $m->getUrl()])
                ->values()
                ->all(),
        ];
    }

    private function forgetCaches(): void
    {
        ClientStorefrontCache::forgetAfterProductMutation();
        AdminDashboardCache::forget();
    }
}
