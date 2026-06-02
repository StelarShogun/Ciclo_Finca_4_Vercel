<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductMediaService;

final class RemoveProductGalleryImage
{
    public function __construct(
        private ProductMediaService $media,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function handle(int $productId, int $mediaId): array
    {
        $product = Product::findOrFail($productId);

        return $this->media->removeGalleryImage($product, $mediaId);
    }
}
