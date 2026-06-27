<?php

namespace App\Actions\Admin\SupplierOrders;

use App\Http\Requests\Admin\SupplierOrders\SearchSupplierProductsRequest;
use App\Services\Admin\SupplierOrders\SupplierOrderQuery;

final readonly class SearchSupplierProducts
{
    public function __construct(private SupplierOrderQuery $query) {}

    public function handle(SearchSupplierProductsRequest $request): array
    {
        return $this->query->searchProductsPayload($request);
    }
}
