<?php

namespace App\Services\Client\Inertia;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ProductDetailPayloadContext
{
    /**
     * @param  Collection<int, int>  $favoriteProductIds
     * @param  array<string, mixed>  $taxonomy
     * @param  array<int, int>  $starDistribution
     * @param  Collection<int, int>  $verifiedPurchaserIds
     * @param  array<int, array{avg: float|int|string|null, count: int|string|null}>  $productReviewStats
     */
    public function __construct(
        public Product $product,
        public Collection $relatedProducts,
        public Collection $favoriteProductIds,
        public array $taxonomy,
        public ?Brand $primaryBrand,
        public ?string $catalogBrandUrl,
        public bool $isNoveltyProduct,
        public ?string $whatsappConsultUrl,
        public int $orderReservationHours,
        public bool $clientCanReview,
        public ?ProductReview $clientReview,
        public ?ProductReview $myHighlightedReview,
        public bool $showMyHighlightedReview,
        public LengthAwarePaginator $productReviewsPaginated,
        public int $totalReviewsCount,
        public ?float $averageStars,
        public array $starDistribution,
        public Collection $verifiedPurchaserIds,
        public string $reviewsSort,
        public string|int $reviewFilter,
        public array $productReviewStats,
        public bool $isProductFavorite,
    ) {}
}
