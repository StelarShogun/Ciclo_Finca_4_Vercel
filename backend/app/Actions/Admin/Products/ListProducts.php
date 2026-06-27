<?php

namespace App\Actions\Admin\Products;

use App\Services\Admin\Products\ProductAdminPayloadService;
use Illuminate\Http\Request;

final class ListProducts
{
    public function __construct(private ProductAdminPayloadService $payloads) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request): array
    {
        return $this->payloads->paginatedIndex($request);
    }
}
