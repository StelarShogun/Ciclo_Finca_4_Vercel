<?php

namespace App\Actions\Admin\SupplierOrders;

use App\Models\Order;
use App\Services\Admin\SupplierOrders\SupplierOrderWorkflowService;
use Illuminate\Http\JsonResponse;

final readonly class UpdateSupplierOrderState
{
    public function __construct(private SupplierOrderWorkflowService $workflow) {}

    public function handle(Order $order, array $data): JsonResponse
    {
        return $this->workflow->updateState($order, $data);
    }
}
