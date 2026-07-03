<?php

namespace App\Services\Client\Inertia;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Shared\Media\ProductImageUrls;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductDetailPayloadBuilder
{
    private const PRODUCT_NOVELTY_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function build(ProductDetailPayloadContext $context): array
    {
        $product = $context->product;
        $relatedProducts = $context->relatedProducts;
        $favoriteProductIds = $context->favoriteProductIds;
        $taxonomy = $context->taxonomy;
        $primaryBrand = $context->primaryBrand;
        $catalogBrandUrl = $context->catalogBrandUrl;
        $isNoveltyProduct = $context->isNoveltyProduct;
        $whatsappConsultUrl = $context->whatsappConsultUrl;
        $orderReservationHours = $context->orderReservationHours;
        $clientCanReview = $context->clientCanReview;
        $clientReview = $context->clientReview;
        $myHighlightedReview = $context->myHighlightedReview;
        $showMyHighlightedReview = $context->showMyHighlightedReview;
        $productReviewsPaginated = $context->productReviewsPaginated;
        $totalReviewsCount = $context->totalReviewsCount;
        $averageStars = $context->averageStars;
        $starDistribution = $context->starDistribution;
        $verifiedPurchaserIds = $context->verifiedPurchaserIds;
        $reviewsSort = $context->reviewsSort;
        $reviewFilter = $context->reviewFilter;
        $productReviewStats = $context->productReviewStats;
        $isProductFavorite = $context->isProductFavorite;

        $carouselSlides = $this->carouselSlides($product);
        $showImagePlaceholder = ProductImageUrls::usesPlaceholder($product) && $carouselSlides === [];

        $hasDescription = filled($product->description);
        $hasSpecs = $product->classificationValues->isNotEmpty() || $hasDescription;
        $hasRelated = $relatedProducts->count() > 0;

        $defaultTab = $hasDescription
            ? 'description'
            : ($product->classificationValues->isNotEmpty() ? 'specs' : 'reviews');

        $request = request();
        if ($request->has('reviews_sort') || $request->has('review_filter') || $request->has('page')) {
            $defaultTab = 'reviews';
        }

        return [
            'product' => $this->productPayload($product, $isProductFavorite, $carouselSlides, $showImagePlaceholder),
            'taxonomy' => [
                'parentCategory' => ($taxonomy['parentCategory'] ?? null) instanceof Category ? [
                    'id' => (int) $taxonomy['parentCategory']->category_id,
                    'name' => (string) $taxonomy['parentCategory']->name,
                    'url' => (string) ($taxonomy['catalogParentUrl'] ?? ''),
                ] : null,
                'subcategory' => ($taxonomy['subcategory'] ?? null) instanceof Category ? [
                    'id' => (int) $taxonomy['subcategory']->category_id,
                    'name' => (string) $taxonomy['subcategory']->name,
                    'url' => (string) ($taxonomy['catalogSubcategoryUrl'] ?? ''),
                ] : null,
            ],
            'primaryBrand' => $primaryBrand ? [
                'id' => (int) $primaryBrand->id,
                'name' => (string) $primaryBrand->name,
                'catalogUrl' => (string) ($catalogBrandUrl ?? ''),
            ] : null,
            'isNoveltyProduct' => $isNoveltyProduct,
            'whatsappConsultUrl' => $whatsappConsultUrl,
            'orderReservationHours' => $orderReservationHours,
            'tabs' => [
                'defaultTab' => $defaultTab,
                'hasDescription' => $hasDescription,
                'hasSpecs' => $hasSpecs,
                'hasRelated' => $hasRelated,
            ],
            'specs' => $product->classificationValues
                ->map(fn ($cv): array => [
                    'dimensionLabel' => optional($cv->dimension)->label ? (string) $cv->dimension->label : null,
                    'value' => (string) $cv->value,
                ])
                ->values()
                ->all(),
            'reviews' => [
                'totalCount' => $totalReviewsCount,
                'averageStars' => $averageStars,
                'starDistribution' => $starDistribution,
                'sort' => $reviewsSort,
                'filter' => (string) $reviewFilter,
                'clientCanReview' => $clientCanReview,
                'clientReviewStars' => $clientReview?->stars,
                'myHighlighted' => $myHighlightedReview
                    ? $this->reviewRowPayload($myHighlightedReview, true, $verifiedPurchaserIds)
                    : null,
                'showMyHighlighted' => $showMyHighlightedReview,
                'items' => collect($productReviewsPaginated->items())
                    ->map(fn (ProductReview $review): array => $this->reviewRowPayload($review, false, $verifiedPurchaserIds))
                    ->values()
                    ->all(),
                'pagination' => $this->reviewsPaginationPayload($productReviewsPaginated),
            ],
            'relatedProducts' => $relatedProducts
                ->map(fn (Product $related): array => $this->relatedProductPayload(
                    $related,
                    $productReviewStats,
                    $favoriteProductIds
                ))
                ->values()
                ->all(),
            'favoriteConfig' => [
                'toggleUrl' => route('clients.favorites.toggle'),
                'loginUrl' => route('login.show'),
            ],
            'seo' => [
                'canonicalUrl' => $product->clientProductUrl(),
                'description' => $this->metaDescription($product),
                'ogImage' => $product->getFirstMediaUrl('main_image') ?: asset('assets/images/products/'.($product->image ?? 'default.png')),
                'robots' => $product->isPurchasableByClient() ? 'index, follow' : 'noindex, follow',
            ],
        ];
    }

    /**
     * @return list<array{fallback: string, desktopWebp: ?string, mobileWebp: ?string}>
     */
    private function carouselSlides(Product $product): array
    {
        if (ProductImageUrls::usesPlaceholder($product)) {
            return [];
        }

        $legacyFallback = ProductImageUrls::fallbackUrl($product);
        $slides = [];

        if ($mainMedia = $product->getFirstMedia('main_image')) {
            $slides[] = ProductImageUrls::carouselSlide($mainMedia, $legacyFallback);
        }

        foreach ($product->getMedia('gallery') as $galleryMedia) {
            if (ProductImageUrls::mediaIsDisplayable($galleryMedia)) {
                $slides[] = ProductImageUrls::carouselSlide($galleryMedia, $galleryMedia->getUrl());
            }
        }

        if ($slides === [] && ProductImageUrls::legacyImageIsDisplayable($product->image)) {
            $slides[] = [
                'fallback' => asset('assets/images/products/'.$product->image),
                'desktopWebp' => null,
                'mobileWebp' => null,
            ];
        }

        return $slides;
    }

    /**
     * @param  list<array{fallback: string, desktopWebp: ?string, mobileWebp: ?string}>  $carouselSlides
     * @return array<string, mixed>
     */
    private function productPayload(
        Product $product,
        bool $isProductFavorite,
        array $carouselSlides,
        bool $showImagePlaceholder,
    ): array {
        $stockLabel = $product->clientCatalogStockLabel();

        return [
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'slug' => (string) $product->clientPublicSlug(),
            'sku' => $product->clientCatalogAssignedSku(),
            'description' => $product->description,
            'price' => (float) $product->sale_price,
            'priceFormatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stockCurrent' => (int) ($product->stock_current ?? 0),
            'stockLabel' => $stockLabel,
            'isLowStock' => $product->clientShowsLowStockWarning(),
            'canBuy' => $product->isPurchasableByClient(),
            'isFeatured' => (bool) $product->is_featured,
            'isFavorite' => $isProductFavorite,
            'isNew' => $product->created_at !== null
                && $product->created_at->greaterThanOrEqualTo(now()->subDays(self::PRODUCT_NOVELTY_DAYS)),
            'carouselSlides' => $carouselSlides,
            'showImagePlaceholder' => $showImagePlaceholder || $carouselSlides === [],
            'placeholderIconClass' => ProductImageUrls::placeholderIconClass($product),
        ];
    }

    /**
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     * @param  Collection<int, int>  $favoriteProductIds
     * @return array<string, mixed>
     */
    private function relatedProductPayload(
        Product $related,
        array $productReviewStats,
        Collection $favoriteProductIds,
    ): array {
        $picture = ProductImageUrls::cardPicture($related);
        $reviewStats = $productReviewStats[(int) $related->product_id] ?? null;
        $brand = $related->brands->first();

        return [
            'id' => (int) $related->product_id,
            'name' => (string) $related->name,
            'url' => $related->clientProductUrl(),
            'sku' => $related->clientCatalogAssignedSku(),
            'priceFormatted' => '₡'.number_format((float) $related->sale_price, 0, ',', '.'),
            'price' => (float) $related->sale_price,
            'stockLabel' => $related->clientCatalogStockLabel(),
            'stockCurrent' => (int) ($related->stock_current ?? 0),
            'canBuy' => $related->isPurchasableByClient(),
            'isFavorite' => $favoriteProductIds->contains((int) $related->product_id),
            'categoryName' => (string) ($related->category->name ?? 'Sin categoría'),
            'brandName' => $brand ? (string) $brand->name : null,
            'image' => [
                'fallback' => $picture['fallback'],
                'desktopWebp' => $picture['desktopWebp'],
                'mobileWebp' => $picture['mobileWebp'],
                'usesPlaceholder' => ProductImageUrls::usesPlaceholder($related),
                'placeholderIconClass' => ProductImageUrls::placeholderIconClass($related),
            ],
            'reviews' => [
                'avg' => (float) data_get($reviewStats, 'avg', 0),
                'count' => (int) data_get($reviewStats, 'count', 0),
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $verifiedPurchaserIds
     * @return array<string, mixed>
     */
    private function reviewRowPayload(
        ProductReview $review,
        bool $mine,
        Collection $verifiedPurchaserIds,
    ): array {
        $client = $review->client;
        $author = $client
            ? trim(implode(' ', array_filter([$client->name, $client->first_surname, $client->second_surname])))
            : '';

        if ($author === '') {
            $author = 'Cliente';
        }

        return [
            'id' => (int) $review->review_id,
            'stars' => (int) $review->stars,
            'author' => $author,
            'publishedAt' => $review->created_at?->format('d/m/Y H:i'),
            'publishedAtIso' => $review->created_at?->toAtomString(),
            'verified' => $verifiedPurchaserIds->contains((int) $review->client_id),
            'mine' => $mine,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewsPaginationPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => (int) $paginator->currentPage(),
            'lastPage' => (int) $paginator->lastPage(),
            'total' => (int) $paginator->total(),
            'links' => collect($paginator->linkCollection())->map(fn (array $link): array => [
                'url' => $link['url'],
                'label' => (string) $link['label'],
                'active' => (bool) $link['active'],
            ])->values()->all(),
        ];
    }

    private function metaDescription(Product $product): string
    {
        $metaDesc = Str::limit(trim(strip_tags((string) ($product->description ?? ''))), 155);

        return $metaDesc !== '' ? $metaDesc : $product->name.' — Ciclo Finca 4';
    }
}
