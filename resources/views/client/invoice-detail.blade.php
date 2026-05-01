@extends('client.layouts.app')

@section('hideFooter')
@endsection

@section('title', 'Detalle de pedido - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
    <style>
        .cf4-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .cf4-detail-grid {
                grid-template-columns: 1fr;
            }
            .cf4-summary-card {
                position: static;
            }
        }

        .cf4-detail-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .cf4-detail-section + .cf4-detail-section {
            margin-top: 20px;
        }

        .cf4-detail-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 22px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .cf4-detail-section-head h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.01em;
            text-transform: uppercase;
        }

        .cf4-detail-section-head i {
            color: var(--color-primary);
        }

        .cf4-detail-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 18px 24px;
            padding: 22px;
        }

        .cf4-detail-meta-item .label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-muted);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .cf4-detail-meta-item .value {
            font-size: 0.98rem;
            color: var(--text-primary);
            font-weight: 600;
            word-break: break-word;
        }

        .cf4-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cf4-items-table thead th {
            background: rgba(46, 125, 50, 0.06);
            color: var(--color-muted);
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            text-align: left;
        }

        .cf4-items-table tbody td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            vertical-align: middle;
        }

        .cf4-items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cf4-items-table .qty-col,
        .cf4-items-table .price-col,
        .cf4-items-table .subtotal-col {
            text-align: right;
            white-space: nowrap;
        }

        .cf4-items-table .qty-col {
            text-align: center;
        }

        .cf4-product-cell {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .cf4-product-thumb {
            width: 56px;
            height: 56px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f1f5f3 0%, #e6ede9 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 1.3rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .cf4-product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cf4-product-name {
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .cf4-product-meta {
            font-size: 0.78rem;
            color: var(--color-muted);
            margin-top: 2px;
        }

        .cf4-summary-card {
            position: sticky;
            top: 100px;
        }

        .cf4-summary-rows {
            padding: 8px 22px 22px;
        }

        .cf4-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.07);
            font-size: 0.95rem;
        }

        .cf4-summary-row:last-of-type {
            border-bottom: none;
        }

        .cf4-summary-label {
            color: var(--color-muted);
        }

        .cf4-summary-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .cf4-summary-row.discount .cf4-summary-value {
            color: var(--color-warning, #c0392b);
        }

        .cf4-summary-total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 16px 22px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #fff;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .cf4-summary-total .label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.9;
        }

        .cf4-summary-total .amount {
            font-size: 1.6rem;
            font-weight: 700;
            margin-top: 2px;
        }

        .cf4-summary-actions {
            padding: 18px 22px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cf4-summary-actions .btn {
            justify-content: center;
            width: 100%;
        }

        .cf4-detail-back-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .cf4-detail-back-bar .crumbs {
            color: var(--color-muted);
            font-size: 0.9rem;
        }

        .cf4-detail-back-bar .crumbs strong {
            color: var(--text-primary);
        }

        .cf4-empty-items {
            padding: 28px 22px;
            text-align: center;
            color: var(--color-muted);
        }

        .cf4-empty-items i {
            font-size: 1.8rem;
            color: rgba(0, 0, 0, 0.2);
            margin-bottom: 6px;
        }

        /* === Tablet === */
        @media (max-width: 768px) {
            .cf4-detail-section-head {
                padding: 14px 18px;
            }
            .cf4-detail-meta-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                padding: 18px;
                gap: 14px 18px;
            }
            .cf4-items-table thead th,
            .cf4-items-table tbody td {
                padding: 12px 14px;
            }
            .cf4-product-thumb {
                width: 48px;
                height: 48px;
            }
            .cf4-summary-total .amount {
                font-size: 1.4rem;
            }
        }

        /* === Phone === */
        @media (max-width: 540px) {
            .cf4-detail-back-bar {
                gap: 8px;
            }
            .cf4-detail-back-bar .crumbs {
                font-size: 0.85rem;
            }
            .cf4-detail-meta-grid {
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }
            .cf4-detail-meta-item .value {
                font-size: 0.9rem;
            }
            /* Stack table rows as cards on small phones */
            .cf4-items-table thead {
                display: none;
            }
            .cf4-items-table,
            .cf4-items-table tbody,
            .cf4-items-table tr,
            .cf4-items-table td {
                display: block;
                width: 100%;
            }
            .cf4-items-table tbody tr {
                padding: 14px 16px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            }
            .cf4-items-table tbody tr:last-child {
                border-bottom: none;
            }
            .cf4-items-table tbody td {
                padding: 4px 0;
                border: none;
            }
            .cf4-items-table tbody td.qty-col,
            .cf4-items-table tbody td.price-col,
            .cf4-items-table tbody td.subtotal-col {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                text-align: right;
            }
            .cf4-items-table tbody td.qty-col::before    { content: 'Cantidad';        color: var(--color-muted); font-size: 0.8rem; }
            .cf4-items-table tbody td.price-col::before  { content: 'Precio unitario'; color: var(--color-muted); font-size: 0.8rem; }
            .cf4-items-table tbody td.subtotal-col::before {
                content: 'Subtotal';
                color: var(--color-muted);
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
            .cf4-summary-total {
                padding: 14px 18px;
            }
            .cf4-summary-total .amount {
                font-size: 1.3rem;
            }
        }

        /* === Small phone === */
        @media (max-width: 380px) {
            .cf4-detail-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    <meta name="cf4-invoice-count" content="{{ $invoiceCount }}">

    @php
        $isCompleted = $sale->status === 'completed';
        $isPending = $sale->status === 'pending';
        $statusLabel = $isCompleted
            ? 'Confirmada'
            : ($isPending ? 'Pendiente de confirmación' : ucfirst((string) $sale->status));
        $statusPillClass = $isCompleted ? 'confirmed' : 'pending';
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

        $backUrl = route('clients.invoices', ['tab' => $isCompleted ? 'historial' : 'facturas']);
    @endphp

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Detalle del pedido</h1>
            <p>
                @if($sale->invoice_number)
                    Factura <strong>{{ $sale->invoice_number }}</strong>
                @else
                    Pedido sin número de factura asignado
                @endif
            </p>
        </div>
    </div>

    <div class="cf4-invoices-wrapper">

        <div class="cf4-detail-back-bar">
            <div class="crumbs">
                <a href="{{ $backUrl }}" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-arrow-left"></i> Mis Facturas
                </a>
                <span>&nbsp;/&nbsp;</span>
                <strong>{{ $sale->invoice_number ?? 'Pedido #'.$sale->sale_id }}</strong>
            </div>
            @if($isPending)
                <span class="cf4-invoice-status-pill pending">
                    <i class="fas fa-clock" style="margin-right:6px;"></i>
                    {{ $statusLabel }}
                </span>
            @endif
        </div>

        <div class="cf4-detail-grid">

            {{-- Left column: meta + items --}}
            <div>

                <div class="cf4-detail-section">
                    <div class="cf4-detail-section-head">
                        <h2><i class="fas fa-info-circle"></i> Información general</h2>
                    </div>
                    <div class="cf4-detail-meta-grid">
                        <div class="cf4-detail-meta-item">
                            <span class="label">N.º de factura</span>
                            <span class="value">{{ $sale->invoice_number ?? '—' }}</span>
                        </div>
                        <div class="cf4-detail-meta-item">
                            <span class="label">Fecha</span>
                            <span class="value">{{ optional($sale->sale_date)->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                        <div class="cf4-detail-meta-item">
                            <span class="label">Método de pago</span>
                            <span class="value">{{ $paymentDisplay }}</span>
                        </div>
                        <div class="cf4-detail-meta-item">
                            <span class="label">Origen</span>
                            <span class="value">{{ $sourceDisplay }}</span>
                        </div>
                        <div class="cf4-detail-meta-item">
                            <span class="label">Productos</span>
                            <span class="value">{{ $items->count() }} artículo{{ $items->count() === 1 ? '' : 's' }} ({{ $itemsCount }} unidad{{ $itemsCount === 1 ? '' : 'es' }})</span>
                        </div>
                    </div>
                </div>

                <div class="cf4-detail-section">
                    <div class="cf4-detail-section-head">
                        <h2><i class="fas fa-box-open"></i> Productos del pedido</h2>
                        <span class="cf4-invoice-muted">{{ $items->count() }} artículo{{ $items->count() === 1 ? '' : 's' }}</span>
                    </div>

                    @if($items->isEmpty())
                        <div class="cf4-empty-items">
                            <div><i class="fas fa-box-open"></i></div>
                            <p>Este pedido no tiene productos asociados.</p>
                        </div>
                    @else
                        <div class="sales-table-container">
                            <table class="cf4-items-table" aria-label="Líneas del pedido">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="qty-col">Cantidad</th>
                                        <th class="price-col">Precio unitario</th>
                                        <th class="subtotal-col">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        @php
                                            $unitPrice = (float) ($item->unit_price ?? 0);
                                            $qty = (int) ($item->quantity ?? 0);
                                            $lineTotal = $item->total !== null ? (float) $item->total : ($unitPrice * $qty);
                                            $product = $item->product;
                                            $productName = $product->name ?? 'Producto eliminado';
                                            $imageUrl = null;
                                            if ($product && !empty($product->image) && $product->image !== 'default.png') {
                                                $imageUrl = filter_var($product->image, FILTER_VALIDATE_URL)
                                                    ? $product->image
                                                    : asset('storage/'.ltrim($product->image, '/'));
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="cf4-product-cell">
                                                    <span class="cf4-product-thumb">
                                                        @if($imageUrl)
                                                            <img src="{{ $imageUrl }}" alt="{{ $productName }}" loading="lazy">
                                                        @else
                                                            <i class="fas fa-bicycle"></i>
                                                        @endif
                                                    </span>
                                                    <div>
                                                        <div class="cf4-product-name">{{ $productName }}</div>
                                                        @if($product && !empty($product->sku))
                                                            <div class="cf4-product-meta">SKU: {{ $product->sku }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="qty-col"><strong>{{ $qty }}</strong></td>
                                            <td class="price-col">&#8353;{{ number_format($unitPrice, 0, ',', '.') }}</td>
                                            <td class="subtotal-col"><strong>&#8353;{{ number_format($lineTotal, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right column: summary --}}
            <aside class="cf4-detail-section cf4-summary-card" aria-label="Resumen de totales">
                <div class="cf4-detail-section-head">
                    <h2><i class="fas fa-receipt"></i> Resumen</h2>
                </div>

                <div class="cf4-summary-rows">
                    <div class="cf4-summary-row">
                        <span class="cf4-summary-label">Subtotal</span>
                        <span class="cf4-summary-value">&#8353;{{ number_format($subtotalDisplay, 0, ',', '.') }}</span>
                    </div>
                    @if($discountDisplay > 0)
                        <div class="cf4-summary-row discount">
                            <span class="cf4-summary-label">Descuento</span>
                            <span class="cf4-summary-value">-&#8353;{{ number_format($discountDisplay, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($ivaDisplay > 0)
                        <div class="cf4-summary-row">
                            <span class="cf4-summary-label">IVA</span>
                            <span class="cf4-summary-value">&#8353;{{ number_format($ivaDisplay, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="cf4-summary-row">
                        <span class="cf4-summary-label">Productos</span>
                        <span class="cf4-summary-value">{{ $itemsCount }} unidad{{ $itemsCount === 1 ? '' : 'es' }}</span>
                    </div>
                </div>

                <div class="cf4-summary-total">
                    <div>
                        <div class="label">{{ $isCompleted ? 'Total pagado' : 'Total a pagar' }}</div>
                    </div>
                    <div class="amount">&#8353;{{ number_format($totalDisplay, 0, ',', '.') }}</div>
                </div>

                <div class="cf4-summary-actions">
                    <a href="{{ $backUrl }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Facturas
                    </a>
                    <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-th"></i> Seguir comprando
                    </a>
                </div>
            </aside>

        </div>

    </div>

@endsection
