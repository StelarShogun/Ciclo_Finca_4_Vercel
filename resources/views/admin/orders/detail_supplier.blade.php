@extends('admin.layouts.sales')

@section('Titulo pagina', 'Detalle Pedido a Proveedor - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css', 'resources/css/admin/orders/supplier-order-detail.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $stateLabels = [
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ];
        $label = $stateLabels[$order->state] ?? ucfirst((string) $order->state);
        $po = $order->po_number ?: ('#'.$order->num_order);
        $supplierName = $order->supplier?->name ?? '—';
    @endphp

    <div class="sales-container cf4-orders-module cf4-supplier-orders-module">
        <header class="sales-header">
            <div>
                <h1>Pedido {{ $po }}</h1>
                <p>Detalle del pedido de compra al proveedor.</p>
            </div>
            <div class="sales-actions">
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>

                @if($order->state === 'draft')
                    <button type="button" class="btn btn-primary"
                            onclick="sendOrder('{{ $order->num_order }}')"
                            title="Enviar pedido (pasar a Pendiente)">
                        <i class="fas fa-paper-plane"></i>
                        Enviar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                @elseif($order->state === 'pending')
                    <button type="button" class="btn btn-primary"
                            onclick="confirmOrder('{{ $order->num_order }}')"
                            title="Confirmar pedido">
                        <i class="fas fa-check"></i>
                        Confirmar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                @elseif($order->state === 'confirmed')
                    <button type="button" class="btn btn-primary"
                            onclick="deliverOrder('{{ $order->num_order }}')"
                            title="Marcar como entregado">
                        <i class="fas fa-truck"></i>
                        Entregado
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                @endif
            </div>
        </header>

        <div class="detail-grid">
            <section class="detail-card">
                <h2><i class="fas fa-info-circle"></i> Información</h2>
                <div class="kv">
                    <div class="kv-row"><span>Nº pedido (PO)</span><strong>{{ $po }}</strong></div>
                    <div class="kv-row"><span>Proveedor</span><strong>{{ $supplierName }}</strong></div>
                    <div class="kv-row"><span>Fecha en que se realizó el pedido</span><strong>{{ $order->date?->format('d/m/Y H:i') ?? '—' }}</strong></div>
                    <div class="kv-row"><span>Entrega estimada</span><strong>{{ $order->estimated_delivery_date?->format('d/m/Y') ?? '—' }}</strong></div>
                    <div class="kv-row">
                        <span>Entregado</span>
                        <strong>
                            @if($order->state === 'cancelled')
                                <span style="color:#9ca3af;">Nunca</span>
                            @elseif($order->delivered_at)
                                {{ $order->delivered_at->format('d/m/Y H:i') }}
                            @else
                                <span style="color:#f59e0b;">En proceso</span>
                            @endif
                        </strong>
                    </div>
                    <div class="kv-row"><span>Estado</span><strong><span class="order-status-pill {{ $order->state }}">{{ $label }}</span></strong></div>
                </div>
            </section>

            <section class="detail-card detail-card-wide">
                <h2><i class="fas fa-box"></i> Productos</h2>
                <div class="items-table-wrap">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Cantidad</th>
                                <th class="num">Precio unit.</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->orderItems ?? [] as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="num">{{ (int) $item->quantity }}</td>
                                    <td class="num">₡{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                    <td class="num"><strong>₡{{ number_format((float) $item->total, 2, ',', '.') }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-cell">Sin productos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="detail-summary">
                    <span>Total</span>
                    <strong>₡{{ number_format((float) $order->total, 2, ',', '.') }}</strong>
                </div>
            </section>

            <section class="detail-card detail-card-wide">
                <h2><i class="fas fa-history"></i> Historial de estados</h2>
                @if($order->stateTimeline->isEmpty())
                    <p class="empty-cell" style="text-align:left; padding:0.5rem 0;">Sin registros de historial.</p>
                @else
                    @php
                        $tlLabels = [
                            'draft'     => ['label' => 'Borrador',   'icon' => 'fa-pencil-alt', 'color' => '#64748b'],
                            'pending'   => ['label' => 'Pendiente',  'icon' => 'fa-clock',      'color' => '#f59e0b'],
                            'confirmed' => ['label' => 'Confirmado', 'icon' => 'fa-check',      'color' => '#3b82f6'],
                            'delivered' => ['label' => 'Entregado',  'icon' => 'fa-truck',      'color' => '#22c55e'],
                            'cancelled' => ['label' => 'Cancelado',  'icon' => 'fa-times',      'color' => '#ef4444'],
                        ];
                    @endphp
                    <ol class="order-timeline">
                        @foreach($order->stateTimeline as $entry)
                            @php
                                $tl = $tlLabels[$entry->state] ?? ['label' => ucfirst($entry->state), 'icon' => 'fa-circle', 'color' => '#94a3b8'];
                                $adminName = $entry->admin
                                    ? trim($entry->admin->name.' '.($entry->admin->first_surname ?? ''))
                                    : 'Sistema';
                            @endphp
                            <li class="tl-item">
                                <div class="tl-dot" style="background:{{ $tl['color'] }};">
                                    <i class="fas {{ $tl['icon'] }}"></i>
                                </div>
                                <div class="tl-body">
                                    <span class="tl-state" style="color:{{ $tl['color'] }};">{{ $tl['label'] }}</span>
                                    <span class="tl-meta">
                                        <i class="fas fa-user-circle"></i> {{ $adminName }}
                                        &nbsp;·&nbsp;
                                        <i class="fas fa-calendar-alt"></i> {{ $entry->changed_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/orders/supplier-orders.js'])
@endpush

