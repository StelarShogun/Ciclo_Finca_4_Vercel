<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;

final class ToggleProductFeatured
{
    public function __construct(
        private ProductAuditLogger $audit,
    ) {}

    /**
     * @return array{success: bool, is_featured?: bool, message: string}
     */
    public function handle(int $id): array
    {
        $product = Product::findOrFail($id);
        $product->is_featured = ! $product->is_featured;
        $product->save();

        $this->audit->log(
            'product_toggle_featured',
            $product->is_featured ? 'Producto marcado como destacado.' : 'Producto removido de destacados.',
            [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'is_featured' => (bool) $product->is_featured,
            ]
        );
        ClientStorefrontCache::forgetAfterProductMutation();

        return [
            'success' => true,
            'is_featured' => (bool) $product->is_featured,
            'message' => $product->is_featured
                ? 'Producto marcado como destacado en la tienda (inicio y catálogo).'
                : 'Producto quitado de destacados en la tienda.',
        ];
    }
}
