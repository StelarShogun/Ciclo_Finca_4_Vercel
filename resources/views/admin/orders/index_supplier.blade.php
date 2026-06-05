@extends('admin.layouts.sales')

@section('Titulo pagina', 'Pedidos a Proveedores - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css', 'resources/css/admin/orders/supplier-order-create.css'])
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
        $openCardActive = request('state') === 'open';
        $openCardUrl = $openCardActive
            ? route('admin.supplier-orders.index')
            : route('admin.supplier-orders.index', ['state' => 'open']);
        $openCardCta = $openCardActive ? 'Ver todo' : 'Ver pedidos no finales';
    @endphp

    <div class="sales-container cf4-orders-module cf4-supplier-orders-module">

        @component('admin.partials.page-header', ['title' => 'Pedidos a Proveedores'])
            <p>
                Gestiona los pedidos de compra realizados a proveedores: confirma la recepción,
                cancela pedidos y consulta el detalle de los productos solicitados.
                Los pedidos de clientes en línea están en
                <a href="{{ route('admin.orders.index') }}">Órdenes</a>.
            </p>

            @slot('actions')
                <div class="sales-header-actions">
                    <a href="{{ route('admin.supplier-orders.xml-deviation.upload') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-file-import"></i> Analizar XML de proveedor
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateOrderModal()">
                        <i class="fas fa-plus"></i>
                        Nuevo pedido
                    </button>
                </div>
            @endslot
        @endcomponent

        <section class="kpi-grid cf4-orders-kpi-grid" aria-label="Resumen de pedidos a proveedores">
            <a class="kpi-card cf4-orders-kpi-card-link" href="{{ $openCardUrl }}">
                <div class="kpi-header">
                    <h3 class="kpi-title">Pedidos abiertos</h3>
                    <div class="kpi-icon info"><i class="fas fa-truck-loading"></i></div>
                </div>
                <p class="kpi-value">{{ number_format((int) ($openSupplierOrdersCount ?? 0), 0, ',', '.') }}</p>
                <div class="kpi-trend {{ $openCardActive ? 'trend-down cf4-kpi-reset-text' : 'trend-up' }}">
                    <i class="fas fa-arrow-right"></i> {{ $openCardCta }}
                </div>
            </a>
        </section>

        @php
            $base = request()->except('state', 'page');
            $activeState = (string) request('state', '');
            $pill = function (string $value, string $label) use ($base, $activeState) {
                $qs = array_merge($base, $value !== '' ? ['state' => $value] : []);
                $url = route('admin.supplier-orders.index', $qs);
                $isActive = $activeState === $value;

                return '<a href="'.$url.'" class="btn btn-sm '.($isActive ? 'btn-primary' : 'btn-secondary').'">'.$label.'</a>';
            };
        @endphp

        @component('admin.partials.filters', [
            'action' => route('admin.supplier-orders.index'),
            'clearUrl' => route('admin.supplier-orders.index'),
            'formId' => 'supplier-orders-filters-form',
        ])
            @slot('fields')
                <div class="filter-group">
                    <label for="supplier-orders-state">Estado</label>
                    <select id="supplier-orders-state" name="state">
                        <option value="">Todos</option>
                        <option value="open" {{ request('state') === 'open' ? 'selected' : '' }}>Abiertas (no finales)</option>
                        <option value="draft" {{ request('state') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="pending" {{ request('state') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="confirmed" {{ request('state') === 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                        <option value="partial_received" {{ request('state') === 'partial_received' ? 'selected' : '' }}>Recepción parcial</option>
                        <option value="delivered" {{ request('state') === 'delivered' ? 'selected' : '' }}>Entregado</option>
                        <option value="cancelled" {{ request('state') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_from">Fecha inicial</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="filter-group">
                    <label for="date_to">Fecha final</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="filter-group">
                    <label for="supplier-orders-search">Buscar</label>
                    <input type="text" id="supplier-orders-search" name="search" value="{{ request('search') }}"
                        placeholder="PO, proveedor o producto…" autocomplete="off">
                </div>
            @endslot

            @slot('after')
                <div class="filters-quick">
                    <span class="filters-quick-label">Filtros rápidos</span>
                    <div class="filters-quick-pills">
                        @php
                            $todayQs = array_merge($base, ['date_range' => 'today']);
                            $todayActive = request('date_range') === 'today';
                            $todayUrl = route('admin.supplier-orders.index', $todayQs);
                        @endphp
                        <a href="{{ $todayUrl }}" class="btn btn-sm {{ $todayActive ? 'btn-primary' : 'btn-secondary' }}">Hoy</a>
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
            @endslot
        @endcomponent

        <div class="orders-table-card table-section" data-cf4-ajax-pagination data-cf4-ajax-scroll>
            <div id="cf4-list-fragment">
            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table admin-table">
                    <thead>
                        <tr>
                            <th>Nº Pedido (PO)</th>
                            <th>Proveedor</th>
                            <th>Fecha de pedido</th>
                            <th>Fecha de entrega estimada</th>
                            <th>Fecha de entrega</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th class="admin-table__col--actions">Acciones</th>
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
                                <td class="admin-table__col--actions">
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
                                <td colspan="8">
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

            <div class="pagination-wrapper">
                <x-admin.pagination :paginator="$orders" label="pedidos" />
            </div>
            </div>
        </div>
    </div>

    {{-- Modal: Order details --}}
    <div id="view-order-modal" class="edit-modal">
        <div class="modal-backdrop" onclick="closeViewOrderModal()"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Detalles del pedido</h3>
                <button type="button" class="modal-close" onclick="closeViewOrderModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-order-body">
                <div class="loading-spinner" role="status">
                    <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
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
    <div id="view-supplier-modal" class="edit-modal">
        <div class="modal-backdrop" onclick="closeViewSupplierModal()"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i> Datos del proveedor</h3>
                <button type="button" class="modal-close" onclick="closeViewSupplierModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-supplier-body">
                <div class="loading-spinner" role="status">
                    <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
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

    {{-- Modal: Nuevo pedido a proveedor --}}
    <div id="create-supplier-order-modal" class="edit-modal">
        <div class="modal-backdrop" onclick="closeCreateOrderModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Nuevo pedido a proveedor</h3>
                <button type="button" class="modal-close" onclick="closeCreateOrderModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="supplier-order-create-form" method="POST" action="{{ route('admin.supplier-orders.store') }}" class="supplier-order-create">
                    @csrf

                    <div class="create-grid">
                        {{-- Proveedor --}}
                        <section class="create-card" aria-labelledby="supplier-card-title">
                            <div class="create-card-head">
                                <h2 id="supplier-card-title"><i class="fas fa-truck"></i> Proveedor</h2>
                                <span class="required-pill">Obligatorio</span>
                            </div>
                            <div class="form-group">
                                <label for="supplier-search">Proveedor</label>
                                <div class="product-combobox" id="supplier-combobox">
                                    <input type="text" id="supplier-search" class="product-combobox-input"
                                           placeholder="Escribe para buscar un proveedor…" autocomplete="off"
                                           aria-label="Proveedor del pedido">
                                    <span class="product-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                    <div class="product-combobox-dropdown" id="supplier-dropdown" role="listbox"></div>
                                </div>
                                <input type="hidden" id="supplier_id" name="supplier_id" value="{{ old('supplier_id') }}" required>
                                @error('supplier_id')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div id="supplier-preview" class="supplier-preview" hidden></div>
                        </section>

                        {{-- Productos --}}
                        <section class="create-card create-card-wide" aria-labelledby="items-card-title">
                            <div class="create-card-head">
                                <h2 id="items-card-title"><i class="fas fa-box"></i> Productos</h2>
                                <span class="required-pill">Obligatorio</span>
                            </div>
                            <div class="items-toolbar">
                                <div class="product-combobox" id="product-combobox">
                                    <input id="product-search" type="text" class="product-combobox-input"
                                           placeholder="Selecciona un proveedor primero…" autocomplete="off" disabled>
                                    <span class="product-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                    <div class="product-combobox-dropdown" id="product-search-dropdown"></div>
                                </div>
                            </div>
                            <div class="items-table-wrap">
                                <table class="items-table admin-table" aria-label="Líneas del pedido">
                                    <thead>
                                        <tr>
                                            <th style="width:46%;">Producto</th>
                                            <th class="num" style="width:16%;">Cantidad</th>
                                            <th class="num" style="width:19%;">Precio unit.</th>
                                            <th class="num" style="width:19%;">Total</th>
                                            <th style="width:1%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-body">
                                        {{-- JS renderiza las filas --}}
                                    </tbody>
                                </table>
                            </div>
                            <div class="items-footer">
                                <div class="items-errors" id="items-errors" aria-live="polite">
                                    @error('items')
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="items-summary">
                                    <div class="summary-line">
                                        <span>Líneas</span>
                                        <strong id="summary-lines">0</strong>
                                    </div>
                                    <div class="summary-line">
                                        <span>Total</span>
                                        <strong id="summary-total">₡0</strong>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" form="supplier-order-create-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar borrador
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeCreateOrderModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/admin/shell.ts', 'resources/js/admin/orders/supplier-orders.ts'])
    <script>
        window.__CF4_SUPPLIERS__ = @json($suppliers);
        @if ($errors->has('supplier_id') || $errors->has('items'))
            document.addEventListener('DOMContentLoaded', function () { openCreateOrderModal(); });
        @endif
    </script>
    @vite(['resources/js/admin/orders/supplier-order-create.ts'])
@endpush