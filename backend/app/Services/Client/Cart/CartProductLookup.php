<?php

namespace App\Services\Client\Cart;

use App\Models\Product;
use Illuminate\Support\Collection;

final class CartProductLookup
{
    /**
     * @param  array<int, array<string, mixed>>  $cartLines
     * @return Collection<int, Product>
     */
    public function indexedByProductId(array $cartLines): Collection
    {
        $productIds = collect($cartLines)
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
    }
}
