<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductMediaService;

final class PromoteProductGalleryToMain
{
    public function __construct(
        private ProductMediaService $media,
    ) {}

    /**
     * @return array{success: bool, message: string, url?: string}
     */
    public function handle(int $productId, int $mediaId): array
    {
        $product = Product::findOrFail($productId);

        return $this->media->promoteGalleryImageToMain($product, $mediaId);
    }
}
