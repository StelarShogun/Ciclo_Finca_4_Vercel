<?php

namespace App\Services\Client\Product;

use App\Models\Product;
use Illuminate\Support\Collection;

final class RelatedProductFinder
{
    /**
     * @return Collection<int, Product>
     */
    public function forProduct(Product $product, int $limit = 4): Collection
    {
        return Product::with(['category.parent', 'brands'])
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->limit($limit)
            ->get();
    }
}
