<?php

namespace App\Services\Client\Product;

use App\DTOs\Client\Product\ProductReviewSummaryData;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ProductReviewSummaryBuilder
{
    public function build(Product $product, Request $request): ProductReviewSummaryData
    {
        $clientCanReview = false;
        $clientReview = null;
        $myHighlightedReview = null;

        if (Auth::guard('clients')->check()) {
            $clientId = (int) Auth::guard('clients')->id();
            $clientCanReview = SaleItem::query()
                ->where('product_id', $product->product_id)
                ->whereHas('sale', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId)
                        ->where('status', 'completed');
                })
                ->exists();

            $clientReview = ProductReview::query()
                ->where('product_id', $product->product_id)
                ->where('client_id', $clientId)
                ->first();

            $myHighlightedReview = ProductReview::query()
                ->with(['client:user_id,name,first_surname,second_surname'])
                ->where('product_id', $product->product_id)
                ->where('client_id', $clientId)
                ->publiclyListed()
                ->first();
        }

        $aggregate = ProductReview::query()
            ->where('product_id', $product->product_id)
            ->publiclyListed()
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as review_count')
            ->first();

        $totalReviewsCount = (int) ($aggregate->review_count ?? 0);
        $averageStars = $totalReviewsCount > 0
            ? round((float) $aggregate->avg_stars, 2)
            : null;

        $distributionCounts = ProductReview::query()
            ->where('product_id', $product->product_id)
            ->publiclyListed()
            ->selectRaw('stars, COUNT(*) as c')
            ->groupBy('stars')
            ->pluck('c', 'stars');

        $starDistribution = [];
        for ($s = 1; $s <= 5; $s++) {
            $starDistribution[$s] = (int) ($distributionCounts[$s] ?? 0);
        }

        $verifiedPurchaserIds = Sale::query()
            ->completed()
            ->whereHas('saleItems', function ($q) use ($product) {
                $q->where('product_id', $product->product_id);
            })
            ->distinct()
            ->pluck('client_id')
            ->map(fn ($id) => (int) $id);

        $reviewsSort = $request->query('reviews_sort', 'recent');
        if (! in_array($reviewsSort, ['recent', 'stars_high', 'stars_low'], true)) {
            $reviewsSort = 'recent';
        }

        $reviewFilter = $request->query('review_filter', 'all');
        if ($reviewFilter !== 'all' && (! ctype_digit((string) $reviewFilter) || ! in_array((int) $reviewFilter, [1, 2, 3, 4, 5], true))) {
            $reviewFilter = 'all';
        }

        $showMyHighlightedReview = $myHighlightedReview !== null
            && ($reviewFilter === 'all' || (int) $myHighlightedReview->stars === (int) $reviewFilter);

        $othersQuery = ProductReview::query()
            ->with(['client:user_id,name,first_surname,second_surname'])
            ->where('product_id', $product->product_id)
            ->publiclyListed();

        if ($myHighlightedReview !== null) {
            $othersQuery->where('review_id', '!=', $myHighlightedReview->review_id);
        }

        if ($reviewFilter !== 'all') {
            $othersQuery->where('stars', (int) $reviewFilter);
        }

        match ($reviewsSort) {
            'stars_high' => $othersQuery->orderByDesc('stars')->orderByDesc('created_at'),
            'stars_low' => $othersQuery->orderBy('stars')->orderByDesc('created_at'),
            default => $othersQuery->orderByDesc('created_at'),
        };

        $productReviewsPaginated = $othersQuery
            ->paginate(10)
            ->withQueryString();

        return new ProductReviewSummaryData(
            clientCanReview: $clientCanReview,
            clientReview: $clientReview,
            myHighlightedReview: $myHighlightedReview,
            showMyHighlightedReview: $showMyHighlightedReview,
            productReviewsPaginated: $productReviewsPaginated,
            totalReviewsCount: $totalReviewsCount,
            averageStars: $averageStars,
            starDistribution: $starDistribution,
            verifiedPurchaserIds: $verifiedPurchaserIds,
            reviewsSort: $reviewsSort,
            reviewFilter: $reviewFilter,
        );
    }
}
