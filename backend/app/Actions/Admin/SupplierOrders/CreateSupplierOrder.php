<?php

namespace App\Actions\Admin\SupplierOrders;

use App\Services\Admin\SupplierOrders\SupplierOrderWorkflowService;
use Illuminate\Http\RedirectResponse;

final readonly class CreateSupplierOrder
{
    public function __construct(private SupplierOrderWorkflowService $workflow) {}

    public function handle(array $data): RedirectResponse
    {
        return $this->workflow->create($data);
    }
}
