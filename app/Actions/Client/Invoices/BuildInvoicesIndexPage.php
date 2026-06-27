<?php

namespace App\Actions\Client\Invoices;

use App\DTOs\Client\Invoices\ClientPendingReviewProduct;
use App\Models\Client;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Client\Invoices\ClientInvoicePresentation;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class BuildInvoicesIndexPage
{
    public function __construct(
        private readonly CartManager $cartManager,
        private readonly ClientInvoicePresentation $presentation,
    ) {}

    public function handle(Request $request): Response
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $tab = $request->query('tab', 'facturas');
        $clientId = (int) $client->user_id;
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));

        $pendingReviewProducts = [];

        if ($tab === 'historial') {
            $orders = Sale::query()
                ->where('client_id', $clientId)
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();

            Sale::markClientHistorySeen($clientId);

            $pendingReviewProducts = ProductReview::query()
                ->with('product:product_id,name')
                ->where('client_id', $clientId)
                ->whereNull('stars')
                ->whereHas('product')
                ->get()
                ->map(fn (ProductReview $review) => (new ClientPendingReviewProduct(
                    productId: (int) $review->product_id,
                    name: (string) ($review->product->name ?? 'Producto'),
                ))->toArray())
                ->values()
                ->all();
        } elseif ($tab === 'canceladas') {
            $orders = Sale::query()
                ->where('client_id', $clientId)
                ->whereIn('status', $this->cancelledClientInvoiceStatuses())
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $tab = 'facturas';

            $orders = Sale::query()
                ->where('client_id', $clientId)
                ->whereIn('status', Sale::activeClientInvoiceStatuses())
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        }

        $ordersRows = collect($orders->items())
            ->map(fn (Sale $sale) => $this->presentation->orderRow($sale)->toArray())
            ->values()
            ->all();

        return Inertia::render('Client/Invoices/Index', [
            'tab' => $tab,
            'orders' => $ordersRows,
            'pagination' => ListPaginationPayload::from($orders),
            'cartCount' => $this->cartManager->totalItemCount(),
            'invoiceCount' => Sale::countActiveClientInvoices($clientId),
            'unseenHistoryCount' => Sale::countUnseenInClientHistory($clientId),
            'invoicesRevision' => Sale::clientInvoicesRevision($clientId),
            'readyToPickupCount' => (int) Sale::query()
                ->where('client_id', $clientId)
                ->where('status', 'ready_to_pickup')
                ->count(),
            'heartbeatUrl' => route('clients.invoices.heartbeat', [], false),
            'pendingReviewProducts' => $pendingReviewProducts,
        ]);
    }

    /**
     * @return list<string>
     */
    private function cancelledClientInvoiceStatuses(): array
    {
        return ['cancelled', 'canceled'];
    }
}
