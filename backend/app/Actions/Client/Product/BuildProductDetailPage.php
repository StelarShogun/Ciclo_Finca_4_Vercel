<?php

namespace App\Actions\Client\Product;

use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Client\Inertia\ProductDetailPayloadBuilder;
use App\Services\Client\Inertia\ProductDetailPayloadContext;
use App\Services\Client\Product\ProductDetailPageSupport;
use App\Services\Client\Product\ProductReviewSummaryBuilder;
use App\Services\Client\Product\RelatedProductFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class BuildProductDetailPage
{
    public function __construct(
        private RelatedProductFinder $relatedProducts,
        private ProductReviewSummaryBuilder $reviewSummary,
        private ProductDetailPageSupport $pageSupport,
        private ProductDetailPayloadBuilder $payloadBuilder,
    ) {}

    public function handle(Request $request, int $id, ?string $slug = null): Response|HttpResponse
    {
        $product = Product::with(['category.parent', 'brands', 'classificationValues.dimension'])->findOrFail($id);

        $canonicalSlug = $product->clientPublicSlug();
        if ($slug !== $canonicalSlug) {
            return redirect()->route('clients.product', array_merge(
                ['id' => $product->product_id, 'slug' => $canonicalSlug],
                $request->only(['reviews_sort', 'page', 'review_filter'])
            ), 301);
        }

        $related = $this->relatedProducts->forProduct($product);

        $favoriteProductIds = collect();
        if (Auth::guard('clients')->check()) {
            $favoriteProductIds = FavoriteProduct::query()
                ->where('user_id', (int) Auth::guard('clients')->id())
                ->pluck('product_id')
                ->map(fn ($pid) => (int) $pid);
        }

        $isProductFavorite = $favoriteProductIds->contains((int) $product->product_id);
        $taxonomy = $this->pageSupport->taxonomy($product);
        $primaryBrand = $product->brands->first();
        $catalogBrandUrl = $primaryBrand
            ? route('clients.catalog', ['brand_id' => $primaryBrand->id])
            : null;

        $reviews = $this->reviewSummary->build($product, $request);

        $productReviewStats = ProductReview::aggregatesForProductIds(
            array_values(array_unique(array_merge(
                [(int) $product->product_id],
                $related->pluck('product_id')->map(fn ($pid) => (int) $pid)->all()
            )))
        );

        $product->load(['classificationValues.dimension']);

        $context = new ProductDetailPayloadContext(
            product: $product,
            relatedProducts: $related,
            favoriteProductIds: $favoriteProductIds,
            taxonomy: $taxonomy,
            primaryBrand: $primaryBrand,
            catalogBrandUrl: $catalogBrandUrl,
            isNoveltyProduct: $this->pageSupport->isNoveltyProduct($product),
            whatsappConsultUrl: $this->pageSupport->whatsappConsultUrl($product),
            orderReservationHours: max(1, (int) config('sales.ready_to_pickup_expiration_hours', 72)),
            clientCanReview: $reviews->clientCanReview,
            clientReview: $reviews->clientReview,
            myHighlightedReview: $reviews->myHighlightedReview,
            showMyHighlightedReview: $reviews->showMyHighlightedReview,
            productReviewsPaginated: $reviews->productReviewsPaginated,
            totalReviewsCount: $reviews->totalReviewsCount,
            averageStars: $reviews->averageStars,
            starDistribution: $reviews->starDistribution,
            verifiedPurchaserIds: $reviews->verifiedPurchaserIds,
            reviewsSort: $reviews->reviewsSort,
            reviewFilter: $reviews->reviewFilter,
            productReviewStats: $productReviewStats,
            isProductFavorite: $isProductFavorite,
        );

        return Inertia::render(
            'Client/Products/Show',
            $this->payloadBuilder->build($context),
        );
    }
}
