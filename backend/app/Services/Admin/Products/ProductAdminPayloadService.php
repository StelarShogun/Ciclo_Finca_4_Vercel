<?php

namespace App\Services\Admin\Products;

use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Support\AdminPerPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class ProductAdminPayloadService
{
    public function __construct(private AdminInventoryProductQuery $inventoryQuery) {}

    public function paginatedIndex(Request $request): LengthAwarePaginator
    {
        // Reusa el query de inventario admin: search, categoría, stock_status,
        // status, sort. Mismos filtros que la lista de inventario web.
        return $this->inventoryQuery->filteredQuery($request)
            ->with(['category', 'supplier'])
            ->paginate(AdminPerPage::resolve($request->get('per_page', 10)))
            ->withQueryString();
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
