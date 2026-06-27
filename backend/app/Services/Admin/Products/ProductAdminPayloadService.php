<?php

namespace App\Services\Admin\Products;

use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Support\AdminPerPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class ProductAdminPayloadService
{
    public function paginatedIndex(Request $request): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'supplier'])
            ->orderByDesc('product_id')
            ->paginate(AdminPerPage::resolve($request->get('per_page', 10)));
    }

    public function detail(int|string $id): Product
    {
        return Product::query()
            ->with(['category.parent', 'supplier', 'brands', 'classificationValues.dimension', 'variants'])
            ->findOrFail($id);
    }

    /**
     * @return array<string,mixed>
     */
    public function detailPayload(Product $product, Request $request): array
    {
        return [
            'success' => true,
            'data' => ProductResource::make($product)->resolve($request),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function mutationPayload(Product $product, string $message): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => ProductResource::make(
                $product->loadMissing(['category.parent', 'supplier', 'brands', 'classificationValues.dimension', 'variants'])
            )->resolve(request()),
        ];
    }
}
