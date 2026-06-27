<?php

namespace App\Services\Client\Invoices;

use App\DTOs\Client\Invoices\ClientInvoiceOrderRow;
use App\DTOs\Client\Invoices\InvoiceShowLineItem;
use App\DTOs\Client\Invoices\InvoiceShowOrderMeta;
use App\DTOs\Client\Invoices\InvoiceShowTotals;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Shared\Media\ProductImageUrls;
use Illuminate\Support\Collection;

final class ClientInvoicePresentation
{
    public function orderRow(Sale $sale): ClientInvoiceOrderRow
    {
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

        return new ClientInvoiceOrderRow(
            id: (int) $sale->sale_id,
            invoiceNumber: $sale->invoice_number ? (string) $sale->invoice_number : null,
            saleDateLabel: $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
            statusLabel: $statusLabel,
            statusTone: $statusTone,
            totalFormatted: '₡'.number_format((float) $sale->total, 0, ',', '.'),
            showUrl: route('clients.invoices.show', $sale, false),
        );
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     */
    public function showTotals(Sale $sale, Collection $items): InvoiceShowTotals
    {
        $itemsCount = (int) $items->sum(fn ($item) => (int) $item->quantity);

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

        return new InvoiceShowTotals(
            subtotalFormatted: '₡'.number_format($subtotalDisplay, 0, ',', '.'),
            ivaFormatted: '₡'.number_format($ivaDisplay, 0, ',', '.'),
            discountFormatted: '₡'.number_format($discountDisplay, 0, ',', '.'),
            totalFormatted: '₡'.number_format($totalDisplay, 0, ',', '.'),
            itemsCount: $itemsCount,
        );
    }

    public function showOrderMeta(Sale $sale): InvoiceShowOrderMeta
    {
        return new InvoiceShowOrderMeta(
            saleId: (int) $sale->sale_id,
            saleDateLabel: $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha',
            statusLabel: $sale->clientStatusLabel(),
            statusPillClass: $sale->clientStatusPillClass(),
            statusIconClass: $sale->clientStatusIconClass(),
            cancellationReason: $sale->clientCancellationReason(),
            paymentDisplay: $this->paymentDisplay($sale),
            sourceDisplay: $this->sourceDisplay($sale),
        );
    }

    public function showLineItem(SaleItem $item): InvoiceShowLineItem
    {
        $total = $item->total !== null
            ? (float) $item->total
            : ((float) $item->unit_price * (int) $item->quantity);

        /** @var Product|null $product */
        $product = $item->product;

        return new InvoiceShowLineItem(
            productId: (int) $item->product_id,
            name: (string) ($product->name ?? 'Producto'),
            quantity: (int) $item->quantity,
            unitPriceFormatted: '₡'.number_format((float) $item->unit_price, 0, ',', '.'),
            totalFormatted: '₡'.number_format($total, 0, ',', '.'),
            image: $this->lineItemImage($product),
        );
    }

    /**
     * @return array{usesPlaceholder: bool, fallback: string, mobileWebp: ?string, placeholderIconClass: string}
     */
    private function lineItemImage(?Product $product): array
    {
        if ($product === null) {
            return [
                'usesPlaceholder' => true,
                'fallback' => asset('assets/images/products/default.png'),
                'mobileWebp' => null,
                'placeholderIconClass' => 'fas fa-box',
            ];
        }

        $presentation = ProductImageUrls::clientPresentation($product);
        $media = $product->getFirstMedia('main_image');

        return [
            'usesPlaceholder' => $presentation['uses_placeholder_image'],
            'fallback' => $presentation['image_url'] ?? ProductImageUrls::fallbackUrl($product),
            'mobileWebp' => ProductImageUrls::webpMobileUrl($media),
            'placeholderIconClass' => $presentation['placeholder_icon_class'],
        ];
    }

    public function documentTitle(Sale $sale): string
    {
        return $sale->clientInvoiceDocumentKind() === 'invoice' ? 'Factura' : 'Comprobante';
    }

    private function paymentDisplay(Sale $sale): string
    {
        $paymentLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'sinpe' => 'SINPE Móvil',
        ];

        if (! $sale->payment_method) {
            return 'No registrado';
        }

        $key = strtolower((string) $sale->payment_method);

        return $paymentLabels[$key] ?? ucfirst((string) $sale->payment_method);
    }

    private function sourceDisplay(Sale $sale): string
    {
        $sourceLabels = [
            'web_cart' => 'Tienda web',
            'pos' => 'Punto de venta',
            'in_store' => 'Tienda física',
        ];

        if (! $sale->order_source) {
            return 'Tienda web';
        }

        $key = strtolower((string) $sale->order_source);

        return $sourceLabels[$key] ?? ucfirst((string) $sale->order_source);
    }
}
