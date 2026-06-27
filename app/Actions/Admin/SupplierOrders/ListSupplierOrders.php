<?php

namespace App\Actions\Admin\SupplierOrders;

use App\Http\Requests\Admin\SupplierOrders\SupplierOrderIndexRequest;
use App\Services\Admin\SupplierOrders\SupplierOrderQuery;

final readonly class ListSupplierOrders
{
    public function __construct(private SupplierOrderQuery $query) {}

    public function handle(SupplierOrderIndexRequest $request): array
    {
        return $this->query->indexPayload($request);
    }
}
