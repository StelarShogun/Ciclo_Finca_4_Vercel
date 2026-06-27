<?php

namespace App\Actions\Admin\Products;

use App\Models\Product;
use App\Services\Admin\Products\ProductAuditLogger;
use App\Services\Client\Storefront\ClientStorefrontCache;
use App\Support\AdminDashboardCache;
use Illuminate\Support\Facades\DB;

final class ForceDeleteProduct
{
    public function __construct(
        private ProductAuditLogger $productAudit,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function handle(int $id): array
    {
        $productName = null;
        DB::transaction(function () use ($id, &$productName) {
            $product = Product::findOrFail($id);
            $productName = $product->name;
            $product->delete();
        });

        $this->productAudit->log('product_force_delete', 'Producto eliminado permanentemente.', [
            'product_id' => $id,
            'name' => $productName,
        ]);
        ClientStorefrontCache::forgetAfterProductMutation();
        AdminDashboardCache::forget();

        return [
            'success' => true,
            'message' => 'Product permanently deleted',
        ];
    }
}
