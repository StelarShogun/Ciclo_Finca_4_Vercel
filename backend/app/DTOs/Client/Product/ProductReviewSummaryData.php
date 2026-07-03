<?php

namespace App\DTOs\Client\Product;

use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ProductReviewSummaryData
{
    /**
     * @param  array<int, int>  $starDistribution
     * @param  Collection<int, int>  $verifiedPurchaserIds
     */
    public function __construct(
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
    ) {}
}
