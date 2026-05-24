@php
    /** @var \App\Models\Sale $sale */
    /** @var string $documentKind invoice | receipt */
    $documentKind = $documentKind ?? 'invoice';
    $isReceipt = $documentKind === 'receipt';

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
        'ready_to_pickup' => 'Por recoger',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
        default => ucfirst((string) $sale->status),
    };
    $statusChipClass = match ($sale->status) {
        'completed' => 'invoice-doc__chip--status-ok',
        'pending', 'ready_to_pickup' => 'invoice-doc__chip--status-warn',
        'cancelled' => 'invoice-doc__chip--status-bad',
        'refunded' => 'invoice-doc__chip--status-neutral',
        default => 'invoice-doc__chip--status-neutral',
    };
    $orderSourceLabel = match ($sale->order_source) {
        'web_cart' => 'Pedido en línea (carrito)',
        'walk_in' => 'Mostrador',
        default => $sale->order_source ? ucfirst(str_replace('_', ' ', (string) $sale->order_source)) : '—',
    };
    $sellerLine = $sale->sellerAdmin
        ? trim($sale->sellerAdmin->name . ' ' . $sale->sellerAdmin->first_surname . ' ' . ($sale->sellerAdmin->second_surname ?: ''))
        : null;

    $headline = $isReceipt ? 'Comprobante de pedido' : 'Factura de venta';
    $headlineNote = $isReceipt
        ? 'Documento informativo — no constituye factura fiscal hasta confirmar el pedido.'
        : null;

    $fmtColones = fn ($n) => '₡' . number_format((float) $n, 0, ',', '.');
@endphp

<article class="invoice-doc__sheet" aria-label="{{ $headline }}">
    <div class="invoice-doc__accent" aria-hidden="true"></div>

    <header class="invoice-doc__head">
        <div class="invoice-doc__head-brand">
            <div class="invoice-doc__logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Finca 4" loading="lazy">
            </div>
            <p class="invoice-doc__brand">Sarapiquí, Costa Rica · info@cicloperez.com</p>
            @if($headlineNote)
                <p class="invoice-doc__head-note">{{ $headlineNote }}</p>
            @endif
        </div>
        <div class="invoice-doc__ref">
            <div class="invoice-doc__ref-label">{{ $headline }}</div>
            <div class="invoice-doc__ref-num">{{ $invoiceNo }}</div>
            <div class="invoice-doc__ref-meta">
                <div><strong>Pedido</strong> #{{ $sale->sale_id }}</div>
                <div><strong>Emitida</strong> {{ $sale->sale_date->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </header>

    <div class="invoice-doc__parties invoice-doc__parties--triple">
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
                    <span class="invoice-doc__party-email">{{ $customerEmail }}</span>
                @else
                    <span class="invoice-doc__party-muted">Sin correo registrado</span>
                @endif
            </div>
        </div>
        <div>
            <div class="invoice-doc__party-title">Atendido por</div>
            <div class="invoice-doc__party-body">
                @if($sellerLine)
                    <strong>{{ $sellerLine }}</strong>
                    <span class="invoice-doc__party-muted">Personal autorizado</span>
                @else
                    <strong>—</strong>
                    <span class="invoice-doc__party-muted">Sin vendedor asignado</span>
                @endif
            </div>
        </div>
    </div>

    <div class="invoice-doc__chips">
        <span class="invoice-doc__chip {{ $statusChipClass }}"><span>Estado</span> {{ $statusLabel }}</span>
        <span class="invoice-doc__chip"><span>Pago</span> {{ $paymentLabel }}</span>
        <span class="invoice-doc__chip"><span>Origen</span> {{ $orderSourceLabel }}</span>
        @if($sale->payment_reference)
            <span class="invoice-doc__chip"><span>Ref. pago</span> {{ $sale->payment_reference }}</span>
        @endif
    </div>

    <div class="invoice-doc__lines-wrap">
        <h2 class="invoice-doc__lines-title">Detalle de productos</h2>
        <div class="invoice-doc__table-scroll">
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
                    @forelse($sale->saleItems as $i => $item)
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
                    @empty
                        <tr>
                            <td colspan="5" class="invoice-doc__empty-lines">No hay líneas registradas en este documento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="invoice-doc__totals">
        <div class="invoice-doc__totals-card">
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
    </div>

    @if($sale->notes)
        <div class="invoice-doc__notes">
            <div class="invoice-doc__notes-title">Notas</div>
            {{ $sale->notes }}
        </div>
    @endif

    <footer class="invoice-doc__footer">
        @if($isReceipt)
            <p><strong>Ciclo Finca 4</strong> — comprobante de pedido generado el {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
            <p>Conserve este documento; al confirmar el pedido podrá obtener la factura formal desde el panel de ventas.</p>
        @else
            <p>Documento generado por el sistema <strong>Ciclo Finca 4</strong>.</p>
            <p>Conserve este comprobante para retiro en tienda o para su control interno.</p>
        @endif
    </footer>
</article>
