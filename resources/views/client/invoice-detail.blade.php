@extends('client.layouts.app')

@section('hideFooter')
@endsection

@section('title', 'Detalle de pedido - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')

    <meta name="cf4-invoice-count" content="{{ $invoiceCount }}">

    @php
        $isCompleted = $sale->status === 'completed';
        $statusLabel = $isCompleted ? 'Confirmada' : ($sale->status === 'pending' ? 'Pendiente' : ucfirst((string) $sale->status));
        $statusPillClass = $isCompleted ? 'confirmed' : 'pending';
        $items = $sale->saleItems ?? collect();
        $subtotalCalc = $items->sum(function ($item) {
            $line = $item->total !== null ? (float) $item->total : ((float) $item->unit_price * (int) $item->quantity);
            return $line;
        });
        $subtotalDisplay = $sale->subtotal !== null ? (float) $sale->subtotal : $subtotalCalc;
        $ivaDisplay = (float) ($sale->iva ?? 0);
        $discountDisplay = (float) ($sale->discount ?? 0);
        $totalDisplay = $sale->total !== null
            ? (float) $sale->total
            : ($subtotalDisplay + $ivaDisplay - $discountDisplay);
        $backUrl = route('clients.invoices', ['tab' => $isCompleted ? 'historial' : 'facturas']);
    @endphp

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Detalle del pedido</h1>
            <p>
                Pedido <strong>#{{ $sale->sale_id }}</strong>
                @if($sale->invoice_number)
                    — Factura <strong>{{ $sale->invoice_number }}</strong>
                @endif
            </p>
        </div>
        <div class="cf4-invoices-tab-selector">
            <a href="{{ $backUrl }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Mis Facturas
            </a>
        </div>
    </div>

    <div class="cf4-invoices-wrapper">

        <div class="cf4-invoices-card">
            <div class="cf4-invoice-detail-meta" style="display:flex; flex-wrap:wrap; gap:24px; padding:20px 24px; border-bottom:1px solid #e5e7eb;">
                <div>
                    <div style="font-size:12px; text-transform:uppercase; color:#6b7280;">Estado</div>
                    <span class="cf4-invoice-status-pill {{ $statusPillClass }}">{{ $statusLabel }}</span>
                </div>
                <div>
                    <div style="font-size:12px; text-transform:uppercase; color:#6b7280;">Fecha</div>
                    <strong>{{ optional($sale->sale_date)->format('d/m/Y H:i') }}</strong>
                </div>
                @if($sale->payment_method)
                    <div>
                        <div style="font-size:12px; text-transform:uppercase; color:#6b7280;">Método de pago</div>
                        <strong>{{ ucfirst($sale->payment_method) }}</strong>
                    </div>
                @endif
                @if($sale->invoice_number)
                    <div>
                        <div style="font-size:12px; text-transform:uppercase; color:#6b7280;">N.º de factura</div>
                        <strong>{{ $sale->invoice_number }}</strong>
                    </div>
                @endif
            </div>

            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table" aria-label="Líneas del pedido">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align:center;">Cantidad</th>
                            <th style="text-align:right;">Precio unitario</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $unitPrice = (float) ($item->unit_price ?? 0);
                                $qty = (int) ($item->quantity ?? 0);
                                $lineTotal = $item->total !== null ? (float) $item->total : ($unitPrice * $qty);
                            @endphp
                            <tr>
                                <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                                <td style="text-align:center;">{{ $qty }}</td>
                                <td style="text-align:right;">&#8353;{{ number_format($unitPrice, 0, ',', '.') }}</td>
                                <td style="text-align:right;"><strong>&#8353;{{ number_format($lineTotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="cf4-invoices-empty">
                                        <div class="cf4-invoices-empty-icon"><i class="fas fa-box-open"></i></div>
                                        <p>Este pedido no tiene productos asociados.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($items->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align:right;">Subtotal</td>
                                <td style="text-align:right;">&#8353;{{ number_format($subtotalDisplay, 0, ',', '.') }}</td>
                            </tr>
                            @if($discountDisplay > 0)
                                <tr>
                                    <td colspan="3" style="text-align:right;">Descuento</td>
                                    <td style="text-align:right;">-&#8353;{{ number_format($discountDisplay, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if($ivaDisplay > 0)
                                <tr>
                                    <td colspan="3" style="text-align:right;">IVA</td>
                                    <td style="text-align:right;">&#8353;{{ number_format($ivaDisplay, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3" style="text-align:right;"><strong>Total</strong></td>
                                <td style="text-align:right;"><strong>&#8353;{{ number_format($totalDisplay, 0, ',', '.') }}</strong></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>

@endsection
