<?php

namespace App\Http\Resources\Client;

use App\Models\Sale;
use App\Services\Client\Invoices\ClientInvoicePresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Sale $sale */
        $sale = $this->resource;

        return app(ClientInvoicePresentation::class)->orderRow($sale)->toArray();
    }
}
