<?php

namespace App\Actions\Client\Invoices;

use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Invoices\ClientInvoicePresentation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class BuildInvoiceShowPage
{
    public function __construct(
        private readonly CartManager $cartManager,
        private readonly ClientInvoicePresentation $presentation,
    ) {}

    public function handle(Sale $sale): Response
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        if (! Gate::forUser($client)->allows('invoices.view', $sale)) {
            abort(404);
        }

        $sale->load(['saleItems.product.category.parent', 'client', 'sellerAdmin']);

        $items = $sale->saleItems ?? collect();
        $clientId = (int) $client->user_id;

        return Inertia::render('Client/Invoices/Show', [
            'invoiceCount' => Sale::countActiveClientInvoices($clientId),
            'backUrl' => route('clients.invoices', ['tab' => $sale->clientInvoicesBackTab()], false),
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
        ]);
    }
}
