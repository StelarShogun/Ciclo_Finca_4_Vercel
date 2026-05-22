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
        $isPending = $sale->status === 'pending';
        $statusLabel = $isCompleted
            ? 'Confirmada'
            : ($isPending ? 'Pendiente de confirmación' : ucfirst((string) $sale->status));
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

    <div class="cf4-invoice-detail-page">
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

            <nav class="breadcrumb" aria-label="Migas de pan">
                <a href="{{ route('clients.home') }}">Inicio</a>
                <span>/</span>
                <a href="{{ $backUrl }}">Mis Facturas</a>
                <span>/</span>
                <span>{{ $sale->invoice_number ?? 'Pedido #'.$sale->sale_id }}</span>
            </nav>

            @if($isPending)
                <p class="cf4-detail-status-banner">
                    <span class="cf4-invoice-status-pill pending">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        {{ $statusLabel }}
                    </span>
                </p>
            @endif

            <div class="cf4-detail-grid">

                <div>
                    <section class="cf4-detail-section" aria-labelledby="cf4-detail-info-heading">
                        <div class="cf4-detail-section-head">
                            <h2 id="cf4-detail-info-heading"><i class="fas fa-info-circle" aria-hidden="true"></i> Información general</h2>
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
                            <div class="cf4-detail-meta-item cf4-detail-meta-item--wide">
                                <span class="label">Productos</span>
                                <span class="value">{{ $items->count() }} artículo{{ $items->count() === 1 ? '' : 's' }} ({{ $itemsCount }} unidad{{ $itemsCount === 1 ? '' : 'es' }})</span>
                            </div>
                        </div>
                    </section>

                    <section class="cf4-detail-section" aria-labelledby="cf4-detail-items-heading">
                        <div class="cf4-detail-section-head">
                            <h2 id="cf4-detail-items-heading"><i class="fas fa-box-open" aria-hidden="true"></i> Productos del pedido</h2>
                            <span class="cf4-invoice-muted">{{ $items->count() }} artículo{{ $items->count() === 1 ? '' : 's' }}</span>
                        </div>

                        @if($items->isEmpty())
                            <div class="cf4-empty-items">
                                <div><i class="fas fa-box-open" aria-hidden="true"></i></div>
                                <p>Este pedido no tiene productos asociados.</p>
                            </div>
                        @else
                            <div class="cf4-order-lines">
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
                                    <article class="cf4-order-line-card">
                                        <div class="cf4-order-line-card__product">
                                            <span class="cf4-product-thumb">
                                                @if($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $productName }}" loading="lazy">
                                                @else
                                                    <i class="fas fa-bicycle" aria-hidden="true"></i>
                                                @endif
                                            </span>
                                            <div>
                                                <div class="cf4-product-name">{{ $productName }}</div>
                                                <div class="cf4-product-meta">SKU: {{ $product ? $product->displaySku() : '—' }}</div>
                                            </div>
                                        </div>
                                        <div class="cf4-order-line-card__stats">
                                            <div class="cf4-order-line-card__stat">
                                                <span class="cf4-order-line-card__stat-label">Cantidad</span>
                                                <span class="cf4-order-line-card__stat-value">{{ $qty }}</span>
                                            </div>
                                            <div class="cf4-order-line-card__stat">
                                                <span class="cf4-order-line-card__stat-label">Precio unit.</span>
                                                <span class="cf4-order-line-card__stat-value">&#8353;{{ number_format($unitPrice, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="cf4-order-line-card__stat cf4-order-line-card__stat--subtotal">
                                                <span class="cf4-order-line-card__stat-label">Subtotal</span>
                                                <span class="cf4-order-line-card__stat-value">&#8353;{{ number_format($lineTotal, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>

                <aside class="cf4-detail-section cf4-summary-card" aria-label="Resumen de totales">
                    <div class="cf4-detail-section-head">
                        <h2><i class="fas fa-receipt" aria-hidden="true"></i> Resumen</h2>
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
                        <div class="label">{{ $isCompleted ? 'Total pagado' : 'Total a pagar' }}</div>
                        <div class="amount">&#8353;{{ number_format($totalDisplay, 0, ',', '.') }}</div>
                    </div>

                    <div class="cf4-summary-actions">
                        <a href="{{ $backUrl }}" class="btn btn-outline-primary btn-sm">
                            Volver a Mis Facturas
                        </a>
                        <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-sm">
                            Seguir comprando
                        </a>
                    </div>
                </aside>

            </div>
        </div>
    </div>

@endsection
