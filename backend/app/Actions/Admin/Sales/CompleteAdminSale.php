<?php

namespace App\Actions\Admin\Sales;

use App\Models\Sale;
use App\Services\Admin\Sales\AdminSalesWorkflowService;
use Illuminate\Http\JsonResponse;

final readonly class CompleteAdminSale
{
    public function __construct(private AdminSalesWorkflowService $workflow) {}

    public function handle(Sale $sale): JsonResponse
    {
        return $this->workflow->complete($sale);
    }
}
