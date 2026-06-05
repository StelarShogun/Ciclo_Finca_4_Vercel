<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Support\Facades\DB;

final class ActivateProduct
{
    public function __construct(
        private ProductAuditLogger $productAudit,
    ) {}

    /**
     * @return array{success: bool, status: string, already_active?: bool, message: string}
     */
    public function handle(int $id): array
    {
        $product = Product::findOrFail($id);
        $productName = $product->name;
        $wasActive = $product->status === 'active';

        DB::transaction(function () use ($product) {
            $product->update(['status' => 'active']);
        });

        if (! $wasActive) {
            $this->productAudit->log('product_activate', 'Producto reactivado.', [
                'product_id' => $id,
                'name' => $productName,
            ]);
            ClientStorefrontCache::forgetAfterProductMutation();
        }

        return [
            'success' => true,
            'already_active' => $wasActive,
            'message' => $wasActive ? 'Product is already active' : 'Product activated successfully',
            'status' => 'active',
        ];
    }
}
