<?php

namespace App\Actions\Admin\Products;

use App\Services\Admin\Products\ProductAdminPayloadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class ListProducts
{
    public function __construct(private ProductAdminPayloadService $payloads) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->payloads->paginatedIndex($request);
    }
}
