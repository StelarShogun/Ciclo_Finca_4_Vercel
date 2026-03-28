@extends('admin.layouts.sales')

@section('Titulo pagina', 'Factura ' . ($sale->invoice_number ?? $sale->sale_id) . ' — Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/admin/sales/invoice-document.css'])
@endpush

@section('aside')@endsection

@php
    $invoiceNo = $sale->invoice_number ?: ('#' . $sale->sale_id);
    $customerName = $sale->client
        ? trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?: ''))
        : ($sale->buyer_name ?: 'Mostrador / sin datos');
    $customerEmail = $sale->client ? ($sale->client->gmail ?: null) : ($sale->buyer_email ?: null);
    $paymentLabel = match ($sale->payment_method) {
        'cash' => 'Efectivo',
        'sinpe' => 'SINPE móvil',
        'transfer' => 'Transferencia bancaria',
        default => ucfirst((string) $sale->payment_method),
    };
    $statusLabel = match ($sale->status) {
        'pending' => 'Pendiente',
        'completed' => 'Confirmada',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
        default => ucfirst((string) $sale->status),
    };
    $orderSourceLabel = match ($sale->order_source) {
        'web_cart' => 'Pedido en línea (carrito)',
        'walk_in' => 'Mostrador',
        default => $sale->order_source ? ucfirst(str_replace('_', ' ', (string) $sale->order_source)) : '—',
    };
    $fmtColones = fn ($n) => '₡' . number_format((float) $n, 0, ',', '.');
@endphp

@section('contenido')
    <div class="invoice-doc">

        <div class="invoice-doc__toolbar no-print">
            <button type="button" class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <article class="invoice-doc__sheet">
            <div class="invoice-doc__accent" aria-hidden="true"></div>

            <header class="invoice-doc__head">
                <div>
                    <div class="invoice-doc__logo">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Finca 4">
                    </div>
                    <p class="invoice-doc__brand">Sarapiquí, Costa Rica · info@cicloperez.com</p>
                </div>
                <div class="invoice-doc__ref">
                    <div class="invoice-doc__ref-label">Factura de venta</div>
                    <div class="invoice-doc__ref-num">{{ $invoiceNo }}</div>
                    <div class="invoice-doc__ref-meta">
                        <div><strong>Pedido</strong> #{{ $sale->sale_id }}</div>
                        <div><strong>Emitida</strong> {{ $sale->sale_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </header>

            <div class="invoice-doc__parties">
                <div>
                    <div class="invoice-doc__party-title">Emisor</div>
                    <div class="invoice-doc__party-body">
                        <strong>Ciclo Finca 4</strong>
                        Sarapiquí, Costa Rica<br>
                        info@cicloperez.com
                    </div>
                </div>
                <div>
                    <div class="invoice-doc__party-title">Cliente</div>
                    <div class="invoice-doc__party-body">
                        <strong>{{ $customerName }}</strong>
                        @if($customerEmail)
                            {{ $customerEmail }}
                        @else
                            <span style="color:var(--inv-muted, #64748b);">Sin correo registrado</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="invoice-doc__chips">
                <span class="invoice-doc__chip"><span>Estado</span> {{ $statusLabel }}</span>
                <span class="invoice-doc__chip"><span>Pago</span> {{ $paymentLabel }}</span>
                <span class="invoice-doc__chip"><span>Origen</span> {{ $orderSourceLabel }}</span>
                @if($sale->payment_reference)
                    <span class="invoice-doc__chip"><span>Ref. pago</span> {{ $sale->payment_reference }}</span>
                @endif
            </div>

            <div class="invoice-doc__lines-wrap">
                <h2 class="invoice-doc__lines-title">Detalle de productos</h2>
                <table class="invoice-doc__table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th class="col-money">Cant.</th>
                            <th class="col-money">P. unit.</th>
                            <th class="col-money">Total línea</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->saleItems as $i => $item)
                            <tr>
                                <td class="col-num">{{ $i + 1 }}</td>
                                <td class="col-desc">
                                    <strong>{{ $item->product->name ?? 'Producto' }}</strong>
                                    @if($item->product && $item->product->product_id)
                                        <small>Ref. interna #{{ $item->product->product_id }}</small>
                                    @endif
                                </td>
                                <td class="col-money">{{ $item->quantity }}</td>
                                <td class="col-money">{{ $fmtColones($item->unit_price) }}</td>
                                <td class="col-money">{{ $fmtColones($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="invoice-doc__totals">
                <div class="invoice-doc__totals-inner">
                    <div class="invoice-doc__total-row">
                        <span>Subtotal</span>
                        <span>{{ $fmtColones($sale->subtotal) }}</span>
                    </div>
                    @if((float) $sale->discount > 0)
                        <div class="invoice-doc__total-row">
                            <span>Descuento</span>
                            <span>−{{ $fmtColones($sale->discount) }}</span>
                        </div>
                    @endif
                    <div class="invoice-doc__total-row">
                        <span>IVA</span>
                        <span>{{ $fmtColones($sale->iva) }}</span>
                    </div>
                    <div class="invoice-doc__total-row invoice-doc__total-row--grand">
                        <span>Total</span>
                        <span>{{ $fmtColones($sale->total) }}</span>
                    </div>
                </div>
            </div>

            @if($sale->notes)
                <div class="invoice-doc__notes">
                    <div class="invoice-doc__notes-title">Notas</div>
                    {{ $sale->notes }}
                </div>
            @endif

            <footer class="invoice-doc__footer">
                Documento generado por el sistema Ciclo Finca 4.
                Conserve este comprobante para retiro en tienda o para su control interno.
            </footer>
        </article>
    </div>
@endsection
