@extends('admin.layouts.sales')

@section('Titulo pagina', 'Encargos - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

    @php
        $orderLabels = [
            'pending' => 'Pendiente',
            'ready_to_pickup' => 'Por recoger',
            'completed' => 'Confirmado',
            'cancelled' => 'Rechazado',
            'refunded' => 'Reembolsado',
        ];
        $pendingCardActive = request('status') === 'pending';
        $pendingCardUrl = $pendingCardActive
            ? route('admin.orders.index')
            : route('admin.orders.index', ['status' => 'pending']);
        $pendingCardCta = $pendingCardActive ? 'Ver todo' : 'Ver encargos pendientes';
    @endphp

    <div class="sales-container cf4-orders-module">

        @if (session('status'))
            <div class="cf4-orders-flash-success" role="status">{{ session('status') }}</div>
        @endif

        @component('admin.partials.page-header', ['title' => 'Encargos en línea'])
            <p>
                Gestiona los encargos del carrito web: márcalos como listos para recoger, confirma ventas o rechaza pedidos.
                Las ventas confirmadas se registran en
                <a href="{{ route('sales.index') }}">Ventas</a>.
            </p>

            @slot('actions')
                <div class="sales-header-actions">
                    <button type="button" class="btn btn-secondary btn-sm orders-settings-link" id="btn-open-order-expiration-modal">
                        <i class="fas fa-clock"></i> Plazo de cancelación
                    </button>
                </div>
            @endslot
        @endcomponent

        <section class="kpi-grid cf4-orders-kpi-grid" aria-label="Resumen de encargos">
            <a class="kpi-card cf4-orders-kpi-card-link" href="{{ $pendingCardUrl }}">
                <div class="kpi-header">
                    <h3 class="kpi-title">Pendientes web</h3>
                    <div class="kpi-icon info"><i class="fas fa-clock"></i></div>
                </div>
                <p class="kpi-value">{{ number_format((int) ($pendingWebOrdersCount ?? 0), 0, ',', '.') }}</p>
                <div class="kpi-trend {{ $pendingCardActive ? 'trend-down cf4-kpi-reset-text' : 'trend-up' }}">
                    <i class="fas fa-arrow-right"></i> {{ $pendingCardCta }}
                </div>
            </a>
        </section>

        @component('admin.partials.filters', [
            'action' => route('admin.orders.index'),
            'clearUrl' => route('admin.orders.index'),
            'formId' => 'orders-filters-form',
        ])
            @slot('fields')
                <div class="filter-group">
                    <label for="orders-status">Estado</label>
                    <select id="orders-status" name="status">
                        <option value="">Todos</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>
                            Pendiente
                        </option>
                        <option value="ready_to_pickup" {{ request('status') === 'ready_to_pickup' ? 'selected' : '' }}>
                            Listo para recoger
                        </option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>
                            Confirmado
                        </option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>
                            Rechazado
                        </option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>
                            Reembolsado
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="orders-date-range">Rango de fecha</label>
                    <select id="orders-date-range" name="date_range">
                        <option value="" {{ request('date_range', '') === '' ? 'selected' : '' }}>Todas las fechas</option>
                        <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Hoy</option>
                        <option value="week" {{ request('date_range') === 'week' ? 'selected' : '' }}>Esta semana</option>
                        <option value="month" {{ request('date_range') === 'month' ? 'selected' : '' }}>Este mes</option>
                        <option value="custom" {{ request('date_range') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </div>

                <div class="filter-group filter-group--date-from" id="orders-custom-date-from-group"
                    style="{{ request('date_range') === 'custom' ? '' : 'display:none;' }}">
                    <label for="orders-date-from">Fecha inicial</label>
                    <input type="date" id="orders-date-from" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="filter-group filter-group--date-to" id="orders-custom-date-to-group"
                    style="{{ request('date_range') === 'custom' ? '' : 'display:none;' }}">
                    <label for="orders-date-to">Fecha final</label>
                    <input type="date" id="orders-date-to" name="date_to" value="{{ request('date_to') }}">
                </div>

                <div class="filter-group">
                    <label for="orders-search">Buscar</label>
                    <input type="text" id="orders-search" name="search" value="{{ request('search') }}"
                        placeholder="Nº encargo, factura o cliente" autocomplete="off">
                </div>
            @endslot
        @endcomponent

        <div class="orders-table-card" data-cf4-ajax-pagination data-cf4-ajax-scroll>
            <div id="cf4-list-fragment">
            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table admin-table">
                    <thead>
                        <tr>
                            <th>Encargos / Factura</th>
                            <th>Cliente</th>
                            <th>Fecha de pedido</th>
                            <th>Fecha listo para recoger</th>
                            <th>Fecha de confirmación</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th class="admin-table__col--actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $sale)
                            <tr>
                                <td>
                                    <strong>#{{ $sale->sale_id }}</strong>
                                    @if ($sale->invoice_number)
                                        <div class="text-muted" style="font-size: 0.85rem;">
                                            {{ $sale->invoice_number }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if ($sale->client_id && $sale->client)
                                        {{ $sale->client->name }} {{ $sale->client->first_surname }}
                                        {{ $sale->client->second_surname ?: '' }}
                                        <span class="text-muted">({{ $sale->client->gmail }})</span>
                                    @elseif($sale->buyer_name)
                                        {{ $sale->buyer_name }}
                                        @if ($sale->buyer_email)
                                            <span class="text-muted">({{ $sale->buyer_email }})</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Sin datos de cliente</span>
                                    @endif
                                </td>

                                <td>{{ $sale->adminOrderPlacedAtLabel() }}</td>
                                <td>{{ $sale->adminReadyAtLabel() }}</td>
                                <td>{{ $sale->adminConfirmedAtLabel() }}</td>

                                <td>
                                    @php
                                        $label = $orderLabels[$sale->status] ?? ucfirst($sale->status);
                                    @endphp
                                    <span class="order-status-pill {{ $sale->status }}">{{ $label }}</span>
                                </td>

                                <td>
                                    <strong>₡{{ number_format($sale->total, 0, ',', '.') }}</strong>
                                </td>

                                <td class="admin-table__col--actions">
                                    <div class="actions-container">
                                        @php
                                            $saleReference = $sale->invoice_number ?? '#' . $sale->sale_id;
                                        @endphp

                                        <button class="action-btn secondary" type="button"
                                            onclick="viewSale(@js($sale->sale_id))" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        @if ($sale->status === 'pending')
                                            <button class="action-btn warning" type="button"
                                                onclick="markReadyToPickup(@js($sale->sale_id), @js($saleReference))"
                                                title="Marcar como listo para recoger">
                                                <i class="fas fa-box"></i>
                                            </button>

                                            <button class="action-btn danger" type="button"
                                                onclick="cancelSale(@js($sale->sale_id), @js($saleReference))"
                                                title="Rechazar encargo">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif

                                        @if ($sale->status === 'ready_to_pickup')
                                            <button class="action-btn success" type="button"
                                                onclick="completeSale(@js($sale->sale_id), @js($saleReference))"
                                                title="Confirmar encargo">
                                                <i class="fas fa-check"></i>
                                            </button>

                                            <button class="action-btn danger" type="button"
                                                onclick="cancelSale(@js($sale->sale_id), @js($saleReference))"
                                                title="Rechazar encargo">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif

                                        @if ($sale->status === 'completed')
                                            <a href="{{ route('sales.invoice', $sale->sale_id) }}" target="_blank"
                                                rel="noopener noreferrer" class="action-link-invoice"
                                                data-confirm-invoice
                                                data-invoice-label="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}"
                                                title="Ver factura en formato estructurado">
                                                <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                                Ver factura
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="orders-empty">
                                        <div class="orders-empty-icon">
                                            <i class="fas fa-inbox"></i>
                                        </div>

                                        <p style="margin:0; font-size:1rem;">
                                            No hay pedidos que coincidan con los filtros.
                                        </p>

                                        @if (request()->filled('status') || request()->filled('search'))
                                            <p style="margin:0.75rem 0 0 0; font-size:0.9rem;">
                                                <a href="{{ route('admin.orders.index') }}">Limpiar filtros</a>
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="orders-pagination-wrap">
                <x-admin.pagination :paginator="$orders" label="pedidos" />
            </div>
            </div>
        </div>
    </div>

    <meta name="sales-route-heartbeat" content="{{ route('sales.history.heartbeat') }}">

    <div id="cf4-latest-purchase-sale-id" data-value="{{ $latestPurchaseSaleId ?? 0 }}" style="display:none;"></div>

    <div id="order-expiration-modal" class="modal-overlay" aria-hidden="true" data-order-expiration-modal>
        <div class="modal-content modal-auto-size cf4-order-expiry-modal-panel">
            <div class="modal-header">
                <h3><i class="fas fa-clock"></i> Plazo para cancelación automática</h3>
                <button type="button" class="modal-close" data-close-order-expiration-modal
                    aria-label="Cerrar">&times;</button>
            </div>

            <div class="modal-body">
                <p class="cf4-order-expiry-modal-intro">
                    Cuántas horas tiene el cliente para retirar un pedido marcado como
                    <strong>Listo para recoger</strong>. Si pasa ese tiempo sin recogerlo,
                    el sistema lo cancela automáticamente.
                </p>

                @if ($usesEnvDefaultForExpiry)
                    <p class="cf4-order-expiry-modal-hint">
                        Si no guarda un valor aquí, se usa el valor por defecto del sistema.
                    </p>
                @endif

                <form id="order-expiration-form" class="orders-settings-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="ready_to_pickup_expiration_hours">Horas para recoger el pedido</label>
                        <input type="number" id="ready_to_pickup_expiration_hours"
                            name="ready_to_pickup_expiration_hours" min="1" max="8760" step="1"
                            required value="{{ old('ready_to_pickup_expiration_hours', $readyToPickupExpirationHours) }}">
                        <p id="order-expiration-form-error" class="form-error" style="display:none;" role="alert"></p>
                        <p class="form-help">Ejemplo: 72 horas (3 días). Mínimo 1, máximo 8760.</p>
                    </div>

                    <div class="modal-footer cf4-order-expiry-modal-footer">
                        <button type="button" class="btn btn-secondary" data-close-order-expiration-modal>
                            <i class="fas fa-times"></i> Cerrar
                        </button>

                        <button type="submit" class="btn btn-primary" id="order-expiration-submit">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <meta name="order-expiration-update-url" content="{{ route('admin.orders.settings.order-expiration.update') }}">

    <div id="view-sale-modal" class="edit-modal">
        <div class="modal-backdrop" onclick="closeViewSaleModal()"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del pedido</h3>
                <button type="button" class="modal-close" onclick="closeViewSaleModal()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-sale-body">
                <div class="loading-spinner" role="status">
                    <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                    <p>Cargando detalles…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewSaleModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/orders/orders.js'])
@endpush
