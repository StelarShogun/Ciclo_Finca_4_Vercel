<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Support\AdminDashboardCache;
use Illuminate\Support\Facades\DB;

final class DeactivateProduct
{
    public function __construct(
        private ProductAuditLogger $productAudit,
    ) {}

    /**
     * @return array{success: bool, status: string, already_inactive?: bool, message: string}
     */
    public function handle(int $id): array
    {
        $product = Product::findOrFail($id);

        if ($product->status === 'inactive') {
            return [
                'success' => true,
                'already_inactive' => true,
                'message' => 'Product is already inactive',
                'status' => 'inactive',
            ];
        }

        $productName = $product->name;
        DB::transaction(function () use ($product) {
            $product->update(['status' => 'inactive']);
        });

        $this->productAudit->log('product_delete', 'Producto desactivado.', [
            'product_id' => $id,
            'name' => $productName,
        ]);
        ClientStorefrontCache::forgetAfterProductMutation();
        AdminDashboardCache::forget();

        return [
            'success' => true,
            'message' => 'Product deactivated successfully',
            'status' => 'inactive',
        ];
    }
}
