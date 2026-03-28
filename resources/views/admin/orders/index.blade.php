@extends('admin.layouts.sales')

@section('Titulo pagina', 'Pedidos - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

    @php
        $orderLabels = [
            'pending' => 'Pendiente',
            'completed' => 'Confirmado',
            'cancelled' => 'Rechazado',
            'refunded' => 'Reembolsado',
        ];
    @endphp

    <div class="sales-container cf4-orders-module">

        <header class="sales-header">
            <div>
                <h1>Pedidos en línea</h1>
                <p>
                    Gestione pedidos del carrito web: confirme la venta, rechace el pedido o consulte la factura.
                    Solo los pedidos pendientes pueden confirmarse o rechazarse. Las ventas ya confirmadas están en
                    <a href="{{ route('sales.index') }}">Ventas</a>.
                </p>
            </div>
        </header>

        <div class="orders-table-card">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="orders-toolbar" id="orders-filters-form">
                <div class="filter-group">
                    <label for="orders-status">Estado</label>
                    <select id="orders-status" name="status">
                        <option value="">Todos</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Confirmado</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Rechazado</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                    </select>
                </div>
                <div class="filter-group orders-search-wrap">
                    <label for="orders-search">Buscar</label>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="text" id="orders-search" name="search" value="{{ request('search') }}"
                           placeholder="Nº pedido, factura o cliente" autocomplete="off">
                </div>
                <div class="orders-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table">
                    <thead>
                        <tr>
                            <th>Pedido / Factura</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $sale)
                            <tr>
                                <td>
                                    <strong>#{{ $sale->sale_id }}</strong>
                                    @if($sale->invoice_number)
                                        <div class="text-muted" style="font-size: 0.85rem;">{{ $sale->invoice_number }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->client_id && $sale->client)
                                        {{ $sale->client->name }} {{ $sale->client->first_surname }}
                                        {{ $sale->client->second_surname ?: '' }}
                                        <span class="text-muted">({{ $sale->client->gmail }})</span>
                                    @elseif($sale->buyer_name)
                                        {{ $sale->buyer_name }}
                                        @if($sale->buyer_email)
                                            <span class="text-muted">({{ $sale->buyer_email }})</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Sin datos de cliente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->saleItems && $sale->saleItems->count() > 0)
                                        <div style="display:flex; flex-direction:column; gap:6px;">
                                            @foreach($sale->saleItems as $item)
                                                <div>{{ $item->quantity }} × {{ $item->product->name ?? 'Producto' }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">Sin productos</span>
                                    @endif
                                </td>
                                <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php $label = $orderLabels[$sale->status] ?? ucfirst($sale->status); @endphp
                                    <span class="order-status-pill {{ $sale->status }}">{{ $label }}</span>
                                </td>
                                <td><strong>₡{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                                <td>
                                    <div class="actions-container">
                                        <button class="action-btn secondary" type="button"
                                                onclick="viewSale('{{ $sale->sale_id }}')"
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($sale->status === 'pending')
                                            <button class="action-btn success" type="button"
                                                    onclick="completeSale('{{ $sale->sale_id }}')"
                                                    title="Confirmar pedido">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="action-btn danger" type="button"
                                                    onclick="cancelSale('{{ $sale->sale_id }}')"
                                                    title="Rechazar pedido">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                        <button class="action-btn secondary" type="button"
                                                onclick="openSaleInvoice('{{ $sale->sale_id }}')"
                                                title="Ver factura">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="orders-empty">
                                        <div class="orders-empty-icon"><i class="fas fa-inbox"></i></div>
                                        <p style="margin:0; font-size:1rem;">No hay pedidos que coincidan con los filtros.</p>
                                        @if(request()->filled('status') || request()->filled('search'))
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

            @if($orders->count() > 0)
                <div class="orders-pagination-wrap">
                    <x-pagination :paginator="$orders" label="pedidos" />
                </div>
            @endif
        </div>
    </div>

    <meta name="sales-route-heartbeat" content="{{ route('sales.history.heartbeat') }}">

    <div id="cf4-latest-purchase-sale-id"
         data-value="{{ $latestPurchaseSaleId ?? 0 }}"
         style="display:none;"></div>

    <div id="view-sale-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del pedido</h3>
                <button type="button" class="modal-close" onclick="closeViewSaleModal()">&times;</button>
            </div>
            <div class="modal-body" id="view-sale-body">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
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
