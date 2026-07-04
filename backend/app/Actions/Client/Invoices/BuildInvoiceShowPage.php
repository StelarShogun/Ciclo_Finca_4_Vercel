<?php

namespace App\Actions\Client\Invoices;

use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Invoices\ClientInvoicePresentation;

final class BuildInvoiceShowPage
{
    public function __construct(
        private readonly CartManager $cartManager,
        private readonly ClientInvoicePresentation $presentation,
    ) {}

    /**
     * Props del detalle de factura (sin Inertia ni gate). El llamador valida la
     * propiedad con la InvoicePolicy. Reusado por el SPA Next.
     *
     * @return array<string, mixed>
     */
    public function props(Sale $sale, Client $client): array
    {
        $sale->load(['saleItems.product.category.parent', 'client', 'sellerAdmin']);

        $items = $sale->saleItems ?? collect();
        $clientId = (int) $client->user_id;

        return [
            'invoiceCount' => Sale::countActiveClientInvoices($clientId),
            'backUrl' => '/invoices?tab='.$sale->clientInvoicesBackTab(),
            'cartCount' => $this->cartManager->totalItemCount(),
            'documentTitle' => $this->presentation->documentTitle($sale),
            'invoiceNumber' => $sale->invoice_number ? (string) $sale->invoice_number : null,
            'orderMeta' => $this->presentation->showOrderMeta($sale)->toArray(),
            'totals' => $this->presentation->showTotals($sale, $items)->toArray(),
            'items' => collect($items)
                ->map(fn (SaleItem $item) => $this->presentation->showLineItem($item)->toArray())
                ->values()
                ->all(),
            'printUrl' => route('clients.invoices.print', $sale, false),
        ];
    }
}
