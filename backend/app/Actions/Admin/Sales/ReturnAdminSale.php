<?php

namespace App\Actions\Admin\Sales;

use App\Models\Sale;
use App\Services\Admin\Sales\AdminSalesWorkflowService;
use Illuminate\Http\JsonResponse;

final readonly class ReturnAdminSale
{
    public function __construct(private AdminSalesWorkflowService $workflow) {}

    public function handle(Sale $sale, string $reason): JsonResponse
    {
        return $this->workflow->returnSale($sale, $reason);
    }
}
