@extends('admin.layouts.sales')

@section('Titulo pagina', 'Pedidos a Proveedores - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

    @php
        $stateLabels = [
            'draft'            => 'Borrador',
            'pending'          => 'Pendiente',
            'confirmed'        => 'Confirmado',
            'partial_received' => 'Recepción parcial',
            'delivered'        => 'Entregado',
            'cancelled'        => 'Cancelado',
        ];
    @endphp

    <div class="sales-container cf4-orders-module cf4-supplier-orders-module">

        <header class="sales-header">
            <div>
                <h1>Pedidos a Proveedores</h1>
                <p>
                    Gestione los pedidos de compra realizados a los proveedores: confirme la recepción,
                    cancele un pedido o consulte el detalle de los productos solicitados.
                    Los pedidos de clientes en línea están en
                    <a href="{{ route('admin.orders.index') }}">Órdenes</a>.
                </p>
            </div>
            <div class="sales-header-actions">
                <a href="{{ route('admin.supplier-orders.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i>
                    Nuevo pedido
                </a>
            </div>
        </header>

        <section class="kpi-grid cf4-orders-kpi-grid" aria-label="Resumen de pedidos a proveedores">
            <a class="kpi-card cf4-orders-kpi-card-link" href="{{ route('admin.supplier-orders.index', ['state' => 'open']) }}">
                <div class="kpi-header">
                    <h3 class="kpi-title">Pedidos abiertos</h3>
                    <div class="kpi-icon info"><i class="fas fa-truck-loading"></i></div>
                </div>
                <p class="kpi-value">{{ number_format((int) ($openSupplierOrdersCount ?? 0), 0, ',', '.') }}</p>
                <div class="kpi-trend trend-up">
                    <i class="fas fa-arrow-right"></i> Ver pedidos no finales
                </div>
            </a>
        </section>

        <div class="orders-table-card">
            <form method="GET" action="{{ route('admin.supplier-orders.index') }}" class="orders-toolbar" id="supplier-orders-filters-form">
                <div class="filter-group">
                    <label for="supplier-orders-state">Estado</label>
                    <select id="supplier-orders-state" name="state">
                        <option value="">Todos</option>
                        <option value="open"             {{ request('state') === 'open'             ? 'selected' : '' }}>Abiertas (no finales)</option>
                        <option value="draft"            {{ request('state') === 'draft'            ? 'selected' : '' }}>Borrador</option>
                        <option value="pending"          {{ request('state') === 'pending'          ? 'selected' : '' }}>Pendiente</option>
                        <option value="confirmed"        {{ request('state') === 'confirmed'        ? 'selected' : '' }}>Confirmado</option>
                        <option value="partial_received" {{ request('state') === 'partial_received' ? 'selected' : '' }}>Recepción parcial</option>
                        <option value="delivered"        {{ request('state') === 'delivered'        ? 'selected' : '' }}>Entregado</option>
                        <option value="cancelled"        {{ request('state') === 'cancelled'        ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_from">Desde</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="filter-group">
                    <label for="date_to">Hasta</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="filter-group orders-search-wrap">
                    <label for="supplier-orders-search">Buscar (PO / proveedor / producto)</label>
                    <div class="orders-search-field">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="text" id="supplier-orders-search" name="search" value="{{ request('search') }}"
                               placeholder="Ej: PO-2026-0001, Trek, grasa…" autocomplete="off">
                    </div>
                </div>
                <div class="orders-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                    <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            @php
                $base = request()->except('state', 'page');
                $activeState = (string) request('state', '');
                $pill = function (string $value, string $label) use ($base, $activeState) {
                    $qs = array_merge($base, $value !== '' ? ['state' => $value] : []);
                    $url = route('admin.supplier-orders.index', $qs);
                    $isActive = $activeState === $value;

                    return '<a href="'.$url.'" class="btn btn-sm '.($isActive ? 'btn-primary' : 'btn-secondary').'" style="margin-right:6px; margin-bottom:6px;">'.$label.'</a>';
                };
            @endphp

            <div class="orders-toolbar" style="padding-top:0; border-top:none;">
                <div class="filter-group" style="flex:1;">
                    <label style="opacity:.8;">Filtros rápidos</label>
                    <div>
                        {!! $pill('', 'Todos') !!}
                        {!! $pill('open', 'Abiertas') !!}
                        {!! $pill('draft', 'Borrador') !!}
                        {!! $pill('pending', 'Pendiente') !!}
                        {!! $pill('confirmed', 'Confirmado') !!}
                        {!! $pill('partial_received', 'Recepción parcial') !!}
                        {!! $pill('delivered', 'Entregado') !!}
                        {!! $pill('cancelled', 'Cancelado') !!}
                    </div>
                </div>
            </div>

            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table">
                    <thead>
                        <tr>
                            <th>Nº Pedido (PO)</th>
                            <th>Proveedor</th>
                            <th>Productos</th>
                            <th>Fecha de pedido</th>
                            <th>Entrega estimada</th>
                            <th>Entrega real</th>
                            <th>Estado</th>
                            <th>Confirmación</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr data-order-id="{{ $order->num_order }}" data-order-state="{{ $order->state }}">
                                <td>
                                    @php
                                        $poFull = $order->po_number ?? ('#'.$order->num_order);
                                        $poShort = $poFull;
                                        if (is_string($order->po_number) && preg_match('/^PO-(\d{4})-(\d{4})$/', $order->po_number, $m)) {
                                            $poShort = 'PO-'.$m[2];
                                        }
                                    @endphp
                                    <strong class="po-number" title="{{ $poFull }}">{{ $poShort }}</strong>
                                </td>
                                <td>
                                    @if($order->supplier)
                                        <button class="supplier-name-btn" type="button"
                                                onclick="viewSupplier('{{ $order->supplier->supplier_id }}')"
                                                title="Ver datos del proveedor">
                                            {{ $order->supplier->name }}
                                            <i class="fas fa-external-link-alt" style="font-size:.75rem; opacity:.6;"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($order->orderItems->take(3) as $item)
                                        <div>{{ (int)$item->quantity }} × {{ $item->name }}</div>
                                    @empty
                                        <span class="text-muted">Sin productos</span>
                                    @endforelse
                                    @if($order->orderItems->count() > 3)
                                        <div style="opacity:.65; font-size:.85rem;">+{{ $order->orderItems->count() - 3 }} más</div>
                                    @endif
                                </td>
                                <td>{{ $order->date?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    @php
                                        $edd = $order->estimated_delivery_date;
                                        $eddLabel = $edd?->format('d/m/Y') ?? '—';
                                        $eddClass = '';
                                        if ($edd) {
                                            if ($edd->isPast() && $order->state !== 'delivered' && $order->state !== 'cancelled') {
                                                $eddClass = 'edd-pill edd-late';
                                            } elseif ($edd->isToday() && $order->state !== 'delivered' && $order->state !== 'cancelled') {
                                                $eddClass = 'edd-pill edd-today';
                                            }
                                        }
                                    @endphp
                                    @if($eddClass)
                                        <span class="{{ $eddClass }}">{{ $eddLabel }}</span>
                                    @else
                                        {{ $eddLabel }}
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $realDeliveredAt = $order->delivered_at ?? $order->received_at;
                                    @endphp
                                    @if($realDeliveredAt)
                                        <span title="Entrega/recepción registrada">{{ $realDeliveredAt->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php $label = $stateLabels[$order->state] ?? ucfirst($order->state); @endphp
                                    <span class="order-status-pill {{ $order->state }}" data-role="order-state-pill">{{ $label }}</span>
                                </td>
                                <td class="order-conf-cell" data-role="order-conf-cell">
                                    @if($order->confirmed_at)
                                        @php
                                            $confUser = null;
                                            if ($order->confirmedBy) {
                                                $confUser = trim(implode(' ', array_filter([
                                                    $order->confirmedBy->name,
                                                    $order->confirmedBy->first_surname,
                                                ])));
                                                if ($confUser === '') {
                                                    $confUser = $order->confirmedBy->gmail;
                                                }
                                            }
                                        @endphp
                                        <div class="order-conf-stack">
                                            <span class="order-conf-date">{{ $order->confirmed_at->format('d/m/Y H:i') }}</span>
                                            @if($confUser)
                                                <span class="order-conf-user" title="{{ $confUser }}">{{ \Illuminate\Support\Str::limit($confUser, 28) }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $initialTotal = (float) ($order->total ?? 0);

                                        $hasReceivedData = $order->orderItems->contains(fn ($it) => $it->received_quantity !== null);
                                        $hasShorts = false;
                                        $receivedTotal = 0.0;
                                        $shortsTotal = 0.0;

                                        if ($hasReceivedData) {
                                            $initialFromLines = $order->orderItems->reduce(
                                                fn ($carry, $it) => $carry + (float) ($it->total ?? 0),
                                                0.0
                                            );
                                            if ($initialFromLines > 0) {
                                                $initialTotal = $initialFromLines;
                                            }

                                            $receivedTotal = $order->orderItems->reduce(function ($carry, $it) {
                                                $unit = (float) ($it->unit_price ?? 0);
                                                $qty  = (int) ($it->received_quantity ?? 0);
                                                return $carry + round($unit * $qty, 2);
                                            }, 0.0);

                                            $hasShorts = $order->orderItems->contains(
                                                fn ($it) => (int) ($it->received_quantity ?? 0) < (int) ($it->quantity ?? 0)
                                            );
                                            $shortsTotal = max($initialTotal - $receivedTotal, 0.0);
                                        }
                                    @endphp

                                    @if($hasReceivedData && $hasShorts)
                                        <div><strong>₡{{ number_format($receivedTotal, 0, ',', '.') }}</strong></div>
                                        <div class="text-muted" style="font-size:.85rem;">Pedido: ₡{{ number_format($initialTotal, 0, ',', '.') }}</div>
                                        @if($shortsTotal > 0)
                                            <div class="text-muted" style="font-size:.85rem;">Faltante: ₡{{ number_format($shortsTotal, 0, ',', '.') }}</div>
                                        @endif
                                    @else
                                        <strong>₡{{ number_format($initialTotal, 0, ',', '.') }}</strong>
                                    @endif
                                </td>
                                <td>
                                    <div class="actions-container" data-role="order-actions">
                                        <button class="action-btn secondary" type="button"
                                                onclick="viewOrder('{{ $order->num_order }}')"
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($order->state === 'draft')
                                            <button class="action-btn success" type="button"
                                                    onclick="confirmOrder('{{ $order->num_order }}')"
                                                    title="Confirmar pedido">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="action-btn danger" type="button"
                                                    onclick="cancelOrder('{{ $order->num_order }}')"
                                                    title="Cancelar pedido">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif($order->state === 'pending')
                                            <button class="action-btn success" type="button"
                                                    onclick="confirmOrder('{{ $order->num_order }}')"
                                                    title="Confirmar pedido">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="action-btn danger" type="button"
                                                    onclick="cancelOrder('{{ $order->num_order }}')"
                                                    title="Cancelar pedido">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif($order->state === 'confirmed')
                                            <a class="action-btn view"
                                               href="{{ route('admin.supplier-orders.detail', $order->num_order) }}"
                                               title="Registrar recepción de mercancía">
                                                <i class="fas fa-truck"></i>
                                            </a>
                                            <button class="action-btn danger" type="button"
                                                    onclick="cancelOrder('{{ $order->num_order }}')"
                                                    title="Cancelar pedido">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif($order->state === 'partial_received')
                                            <a class="action-btn view"
                                               href="{{ route('admin.supplier-orders.detail', $order->num_order) }}"
                                               title="Completar recepción de mercancía">
                                                <i class="fas fa-clipboard-check"></i>
                                            </a>
                                            <button class="action-btn danger" type="button"
                                                    onclick="cancelOrder('{{ $order->num_order }}')"
                                                    title="Cancelar pedido">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="orders-empty">
                                        <div class="orders-empty-icon"><i class="fas fa-inbox"></i></div>
                                        <p style="margin:0; font-size:1rem;">No hay pedidos que coincidan con los filtros.</p>
                                        @if(request()->filled('state') || request()->filled('search') || request()->filled('date_from') || request()->filled('date_to'))
                                            <p style="margin:0.75rem 0 0 0; font-size:0.9rem;">
                                                <a href="{{ route('admin.supplier-orders.index') }}">Limpiar filtros</a>
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->count() > 0)
                <div class="orders-pagination-wrap">
                    <x-pagination :paginator="$orders" label="pedidos" />
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Order details --}}
    <div id="view-order-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Detalles del pedido</h3>
                <button type="button" class="modal-close" onclick="closeViewOrderModal()">&times;</button>
            </div>
            <div class="modal-body" id="view-order-body">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
                    <p>Cargando detalles…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewOrderModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Supplier details --}}
    <div id="view-supplier-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i> Datos del proveedor</h3>
                <button type="button" class="modal-close" onclick="closeViewSupplierModal()">&times;</button>
            </div>
            <div class="modal-body" id="view-supplier-body">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
                    <p>Cargando datos del proveedor…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewSupplierModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/admin/orders/supplier-orders.js'])
@endpush