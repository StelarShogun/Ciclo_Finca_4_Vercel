<?php

namespace App\Actions\Admin\Products;

use App\DTOs\Admin\Products\ProductMutationResult;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Models\Product;
use App\Services\Admin\Classifications\ProductClassificationAssignmentService;
use App\Services\Admin\Products\ProductMediaService;
use Illuminate\Support\Facades\DB;

final class CreateProduct
{
    public function __construct(
        private ProductMediaService $media,
    ) {}

    public function handle(StoreProductRequest $request): ProductMutationResult
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $brandId = $data['brand_id'];
            unset($data['brand_id']);
            $classificationIds = $data['classification_value_ids'] ?? [];
            unset($data['classification_value_ids']);
            unset($data['image'], $data['images']);

            $product = Product::create($data);
            $product->brands()->attach($brandId);
            app(ProductClassificationAssignmentService::class)->syncForProduct($product, $classificationIds);

            return $product;
        });

        $this->media->attachFromStoreRequest($product, $request);

        return new ProductMutationResult($product, [
            'product_id' => (int) $product->product_id,
            'name' => $product->name,
            'category_id' => (int) $product->category_id,
            'supplier_id' => (int) $product->supplier_id,
            'status' => (string) $product->status,
        ]);
    }
}
