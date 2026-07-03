<?php

namespace App\Actions\Admin\Sales;

use App\Services\Admin\Sales\AdminSalesWorkflowService;
use Illuminate\Http\JsonResponse;

final readonly class CreateAdminSale
{
    public function __construct(private AdminSalesWorkflowService $workflow) {}

    public function handle(array $data): JsonResponse
    {
        return $this->workflow->store($data);
    }
}
