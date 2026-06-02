<?php

namespace App\Data\Admin\Products;

use App\Models\Product;

final readonly class ProductMutationResult
{
    /**
     * @param  array<string, mixed>  $auditContext
     */
    public function __construct(
        public Product $product,
        public array $auditContext,
    ) {}
}
