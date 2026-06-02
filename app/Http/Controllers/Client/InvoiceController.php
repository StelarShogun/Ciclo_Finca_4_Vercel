<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Client\Cart\CartManager;
use App\Support\AdminPerPage;
use App\Support\ClientInertia\ListPaginationPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly CartManager $cartManager,
    ) {}

    public function invoices(Request $request)
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $tab = $request->query('tab', 'facturas');
        $pendingReviewProducts = collect();

        $activeStatuses = $this->activeClientInvoiceStatuses();
        $cancelledStatuses = $this->cancelledClientInvoiceStatuses();

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));

        if ($tab === 'historial') {
            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();

            Sale::markClientHistorySeen((int) $client->user_id);

            $pendingReviewProducts = ProductReview::query()
                ->with('product:product_id,name')
                ->where('client_id', $client->user_id)
                ->whereNull('stars')
                ->whereHas('product')
                ->get()
                ->map(function (ProductReview $review) {
                    return [
                        'product_id' => (int) $review->product_id,
                        'name' => (string) ($review->product->name ?? 'Producto'),
                    ];
                })
                ->values();
        } elseif ($tab === 'canceladas') {
            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->whereIn('status', $cancelledStatuses)
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $tab = 'facturas';

            $orders = Sale::query()
                ->where('client_id', $client->user_id)
                ->whereIn('status', $activeStatuses)
                ->orderByDesc('sale_date')
                ->paginate($perPage)
                ->withQueryString();
        }

        $cartCount = $this->cartManager->totalItemCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);
        $unseenHistoryCount = Sale::countUnseenInClientHistory((int) $client->user_id);
        $invoicesRevision = Sale::clientInvoicesRevision((int) $client->user_id);
        $readyToPickupCount = Sale::query()
            ->where('client_id', $client->user_id)
            ->where('status', 'ready_to_pickup')
            ->count();

        $ordersRows = collect($orders->items())->map(function (Sale $sale) {
            $statusLabel = match ($sale->status) {
                'pending' => 'Pendiente',
                'ready_to_pickup' => 'Por recoger',
                'cancelled', 'canceled' => 'Cancelada',
                'completed' => 'Confirmado',
                default => ucfirst(str_replace('_', ' ', (string) $sale->status)),
            };

            $statusTone = match ($sale->status) {
                'pending' => 'pending',
                'ready_to_pickup' => 'ready',
                'cancelled', 'canceled' => 'cancelled',
                'completed' => 'completed',
                default => 'default',
            };

            return [
                'id' => (int) $sale->sale_id,
                'invoiceNumber' => $sale->invoice_number ? (string) $sale->invoice_number : null,
                'saleDateLabel' => $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
                'statusLabel' => $statusLabel,
                'statusTone' => $statusTone,
                'totalFormatted' => '₡'.number_format((float) $sale->total, 0, ',', '.'),
                'showUrl' => route('clients.invoices.show', $sale, false),
            ];
        })->values()->all();

        return Inertia::render('Client/Invoices/Index', [
            'tab' => $tab,
            'orders' => $ordersRows,
            'pagination' => ListPaginationPayload::from($orders),
            'cartCount' => $cartCount,
            'invoiceCount' => $invoiceCount,
            'unseenHistoryCount' => $unseenHistoryCount,
            'invoicesRevision' => $invoicesRevision,
            'readyToPickupCount' => (int) $readyToPickupCount,
            'heartbeatUrl' => route('clients.invoices.heartbeat', [], false),
            'pendingReviewProducts' => $pendingReviewProducts,
        ]);
    }

    public function invoicesHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $clientId = (int) $client->user_id;

        return response()->json([
            'count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
        ]);
    }

    public function showInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'client', 'sellerAdmin']);

        $cartCount = $this->cartManager->totalItemCount();

        $invoiceCount = Sale::countActiveClientInvoices((int) $client->user_id);

        $documentKind = $sale->clientInvoiceDocumentKind();
        $documentTitle = $documentKind === 'invoice' ? 'Factura' : 'Comprobante';
        $items = $sale->saleItems ?? collect();
        $itemsCount = $items->sum(fn ($item) => (int) $item->quantity);

        $subtotalCalc = $items->sum(function ($item) {
            return $item->total !== null
                ? (float) $item->total
                : ((float) $item->unit_price * (int) $item->quantity);
        });

        $subtotalDisplay = $sale->subtotal !== null ? (float) $sale->subtotal : $subtotalCalc;
        $ivaDisplay = (float) ($sale->iva ?? 0);
        $discountDisplay = (float) ($sale->discount ?? 0);
        $totalDisplay = $sale->total !== null
            ? (float) $sale->total
            : ($subtotalDisplay + $ivaDisplay - $discountDisplay);

        $paymentLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'sinpe' => 'SINPE Móvil',
        ];
        $paymentDisplay = $sale->payment_method
            ? ($paymentLabels[strtolower((string) $sale->payment_method)] ?? ucfirst((string) $sale->payment_method))
            : 'No registrado';

        $sourceLabels = [
            'web_cart' => 'Tienda web',
            'pos' => 'Punto de venta',
            'in_store' => 'Tienda física',
        ];
        $sourceDisplay = $sale->order_source
            ? ($sourceLabels[strtolower((string) $sale->order_source)] ?? ucfirst((string) $sale->order_source))
            : 'Tienda web';

        $backUrl = route('clients.invoices', ['tab' => $sale->clientInvoicesBackTab()], false);

        return Inertia::render('Client/Invoices/Show', [
            'invoiceCount' => (int) $invoiceCount,
            'backUrl' => $backUrl,
            'cartCount' => $cartCount,
            'documentTitle' => $documentTitle,
            'invoiceNumber' => $sale->invoice_number ? (string) $sale->invoice_number : null,
            'orderMeta' => [
                'saleId' => (int) $sale->sale_id,
                'saleDateLabel' => $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
                'statusLabel' => $sale->clientStatusLabel(),
                'statusPillClass' => $sale->clientStatusPillClass(),
                'statusIconClass' => $sale->clientStatusIconClass(),
                'cancellationReason' => $sale->clientCancellationReason(),
                'paymentDisplay' => $paymentDisplay,
                'sourceDisplay' => $sourceDisplay,
            ],
            'totals' => [
                'subtotalFormatted' => '₡'.number_format($subtotalDisplay, 0, ',', '.'),
                'ivaFormatted' => '₡'.number_format($ivaDisplay, 0, ',', '.'),
                'discountFormatted' => '₡'.number_format($discountDisplay, 0, ',', '.'),
                'totalFormatted' => '₡'.number_format($totalDisplay, 0, ',', '.'),
                'itemsCount' => (int) $itemsCount,
            ],
            'items' => collect($items)->map(function (SaleItem $item) {
                $total = $item->total !== null
                    ? (float) $item->total
                    : ((float) $item->unit_price * (int) $item->quantity);

                return [
                    'productId' => (int) $item->product_id,
                    'name' => (string) ($item->product->name ?? 'Producto'),
                    'quantity' => (int) $item->quantity,
                    'unitPriceFormatted' => '₡'.number_format((float) $item->unit_price, 0, ',', '.'),
                    'totalFormatted' => '₡'.number_format($total, 0, ',', '.'),
                ];
            })->values()->all(),
            'printUrl' => route('clients.invoices.print', $sale, false),
        ]);
    }

    public function printInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if ((int) $sale->client_id !== (int) $client->user_id) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'client', 'sellerAdmin']);

        return view('client.invoice-print', compact('sale'));
    }

    private function activeClientInvoiceStatuses(): array
    {
        return Sale::activeClientInvoiceStatuses();
    }

    private function cancelledClientInvoiceStatuses(): array
    {
        return ['cancelled', 'canceled'];
    }
}
