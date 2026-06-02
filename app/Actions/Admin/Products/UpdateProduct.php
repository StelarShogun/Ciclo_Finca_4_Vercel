<?php

namespace App\Actions\Admin\Products;

use App\Data\Admin\Products\ProductMutationResult;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Admin\Products\ProductMediaService;
use App\Services\ProductClassificationAssignmentService;
use Illuminate\Support\Facades\DB;

final class UpdateProduct
{
    public function __construct(
        private ProductMediaService $media,
    ) {}

    public function handle(UpdateProductRequest $request, int|string $id): ProductMutationResult
    {
        [$product, $auditContext] = DB::transaction(function () use ($request, $id) {
            $p = Product::findOrFail($id);
            $before = [
                'name' => $p->name,
                'description' => $p->description,
                'category_id' => (int) $p->category_id,
                'supplier_id' => (int) $p->supplier_id,
                'purchase_price' => (float) $p->purchase_price,
                'sale_price' => (float) $p->sale_price,
                'stock_current' => (int) $p->stock_current,
                'stock_minimum' => (int) $p->stock_minimum,
                'status' => (string) $p->status,
                'is_featured' => (bool) $p->is_featured,
            ];
            $data = $request->validated();
            $brandId = $data['brand_id'];
            unset($data['brand_id']);
            $syncClassifications = $request->has('classification_value_ids');
            $classificationIds = $syncClassifications ? ($data['classification_value_ids'] ?? []) : null;
            unset($data['classification_value_ids']);
            unset($data['image'], $data['images']);

            $p->update($data);
            $p->brands()->sync([$brandId]);
            $p->refresh();
            if ($syncClassifications) {
                app(ProductClassificationAssignmentService::class)->syncForProduct($p, $classificationIds ?? []);
            }

            $after = [
                'name' => $p->name,
                'description' => $p->description,
                'category_id' => (int) $p->category_id,
                'supplier_id' => (int) $p->supplier_id,
                'purchase_price' => (float) $p->purchase_price,
                'sale_price' => (float) $p->sale_price,
                'stock_current' => (int) $p->stock_current,
                'stock_minimum' => (int) $p->stock_minimum,
                'status' => (string) $p->status,
                'is_featured' => (bool) $p->is_featured,
            ];

            $changed = [];
            foreach ($after as $field => $value) {
                if (($before[$field] ?? null) !== $value) {
                    $changed[$field] = [
                        'from' => $before[$field] ?? null,
                        'to' => $value,
                    ];
                }
            }

            return [
                $p,
                [
                    'product_id' => (int) $p->product_id,
                    'changes' => $changed,
                ],
            ];
        });

        $this->media->syncFromUpdateRequest($product, $request);

        return new ProductMutationResult($product, $auditContext);
    }
}
