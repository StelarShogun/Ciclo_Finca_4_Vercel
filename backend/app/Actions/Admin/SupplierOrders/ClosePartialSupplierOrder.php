<?php

namespace App\Actions\Admin\SupplierOrders;

use App\Models\Order;
use App\Services\Admin\SupplierOrders\SupplierOrderWorkflowService;
use Illuminate\Http\JsonResponse;

final readonly class ClosePartialSupplierOrder
{
    public function __construct(private SupplierOrderWorkflowService $workflow) {}

    public function handle(Order $order, string $reason): JsonResponse
    {
        return $this->workflow->closePartial($order, $reason);
    }
}
