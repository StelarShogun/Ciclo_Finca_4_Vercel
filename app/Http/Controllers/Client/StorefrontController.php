<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Catalog\BuildCatalogPage;
use App\Http\Controllers\Client\Concerns\BuildsClientCatalogPages;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class StorefrontController extends Controller
{
    use BuildsClientCatalogPages;

    public function home(): Response
    {
        $featuredProducts = Product::with([
            'category.parent',
            'media' => static function ($q): void {
                $q->where('collection_name', 'main_image');
            },
        ])
            ->activeInClientStore()
            ->where('is_featured', true)
            ->where('stock_current', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = $this->cachedClientRootCategories();

        $productReviewStats = ProductReview::aggregatesForProductIds(
            $featuredProducts->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        );

        $showGuestRegisterCta = ! Auth::guard('clients')->check() && ! session('admin_catalog_mode');

        return Inertia::render('Client/Home/Index', [
            'featuredProducts' => $featuredProducts
                ->map(fn (Product $product): array => $this->homeProductPayload($product, $productReviewStats))
                ->values(),
            'categories' => $categories
                ->map(fn ($category): array => $this->homeCategoryPayload($category))
                ->values(),
            'showGuestRegisterCta' => $showGuestRegisterCta,
            'hero' => [
                'title' => 'Equípate para rodar',
                'emphasis' => 'con asesoría real en tienda',
                'subtitle' => 'Bicicletas, componentes y accesorios listos para encargo con retiro rápido.',
                'description' => 'Te guiamos en elección, ajuste y preparación para que retires con confianza.',
            ],
        ]);
    }

    public function catalog(Request $request, BuildCatalogPage $action)
    {
        return $action->handle($request);
    }

    public function catalogHeartbeat()
    {
        return response()
            ->json([
                'version' => ClientStorefrontCache::catalogVersion(),
            ])
            ->header('Cache-Control', 'private, no-cache, max-age=0, must-revalidate');
    }
}
