@extends('admin.layouts.sales')

@section('Titulo pagina', 'Ventas - Ciclo Finca 4 Admin')

@push('styles')
        @vite(['resources/css/sales/sales.css'])
@endpush

{{-- The sidebar is provided by the layout via @yield('aside') --}}
@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="sales-container">

        {{-- Header --}}
        <header class="sales-header">
            <div>
                <h1>Gestión de Ventas</h1>
                <p>Registre y administre las ventas del sistema</p>
            </div>
            <div class="sales-actions">
                <button class="btn btn-primary" onclick="openNewSaleModal()">
                    <i class="fas fa-plus"></i> Nueva Venta
                </button>
            </div>
        </header>

        {{-- KPIs --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Ventas del Día</h3>
                    <div class="kpi-icon success"><i class="fas fa-chart-line"></i></div>
                </div>
                <p class="kpi-value">₡{{ number_format($dailySales, 0, ',', '.') }}</p>
                <div class="kpi-trend {{ $dailySalesTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $dailySalesTrend >= 0 ? 'up' : 'down' }}"></i> {{ abs($dailySalesTrend) }}%
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Transacciones</h3>
                    <div class="kpi-icon info"><i class="fas fa-receipt"></i></div>
                </div>
                <p class="kpi-value">{{ $dailyTransactions }}</p>
                <div class="kpi-trend {{ $dailyTransactionsTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $dailyTransactionsTrend >= 0 ? 'up' : 'down' }}"></i> {{ abs($dailyTransactionsTrend) }}%
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Reembolsos</h3>
                    <div class="kpi-icon danger"><i class="fas fa-undo"></i></div>
                </div>
                <p class="kpi-value">{{ $refunds }}</p>
                <div class="kpi-trend {{ $refundsTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $refundsTrend >= 0 ? 'up' : 'down' }}"></i> {{ abs($refundsTrend) }}
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filters-section">
            <h2 class="filters-title">Filtros de Búsqueda</h2>
            <form method="GET" action="{{ route('sales.index') }}" id="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="">Todos los estados</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pendiente</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            <option value="refunded"  {{ request('status') == 'refunded'  ? 'selected' : '' }}>Reembolsado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date-range">Rango de Fecha</label>
                        <select id="date-range" name="date_range">
                            <option value="today"  {{ request('date_range') == 'today'  ? 'selected' : '' }}>Hoy</option>
                            <option value="week"   {{ request('date_range') == 'week'   ? 'selected' : '' }}>Esta semana</option>
                            <option value="month"  {{ request('date_range') == 'month'  ? 'selected' : '' }}>Este mes</option>
                            <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="payment-method">Método de Pago</label>
                        <select id="payment-method" name="payment_method">
                            <option value="">Todos los métodos</option>
                            <option value="cash"     {{ request('payment_method') == 'cash'     ? 'selected' : '' }}>Efectivo</option>
                            <option value="sinpe"    {{ request('payment_method') == 'sinpe'    ? 'selected' : '' }}>SINPE Móvil</option>
                            <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>Transferencia Bancaria</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search-sale">Buscar</label>
                        <input type="text" id="search-sale" name="search"
                               placeholder="Buscar por cliente..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-group filter-buttons">
                        <button type="submit" class="btn btn-primary filter-btn">
                            <i class="fas fa-search"></i> Aplicar Filtros
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn btn-primary filter-btn">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sales table --}}
        @php
            $statusLabels  = ['pending' => 'Pendiente', 'completed' => 'Completado', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado'];
            $paymentLabels = ['cash' => 'Efectivo', 'sinpe' => 'SINPE Móvil', 'transfer' => 'Transferencia'];
        @endphp

        <div class="sales-table-container">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Días restantes</th>
                        <th>Método de Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><strong>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</strong></td>
                        <td>
                            @if($sale->client_id && $sale->client)
                                {{ $sale->client->name }} {{ $sale->client->first_surname }} {{ $sale->client->second_surname ?: '' }}
                                <span class="text-muted">({{ $sale->client->gmail }})</span>
                            @else
                                {{ $sale->buyer_name ?: 'Mostrador / Sin datos' }}
                                @if($sale->buyer_email)
                                    <span class="text-muted">({{ $sale->buyer_email }})</span>
                                @endif
                            @endif
                        </td>
                        <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="status-badge {{ $sale->status }}">
                                {{ $statusLabels[$sale->status] ?? $sale->status }}
                            </span>
                        </td>
                        <td>
                            @php
                                $days = $sale->days_remaining_until_expiration;
                                $warn = $sale->is_expiry_warning;
                            @endphp
                            @if($days <= 0)
                                <span class="expiry-badge expiry-expired" title="El pedido ha expirado">
                                    <i class="fas fa-clock"></i> Expirado
                                </span>
                            @elseif($warn)
                                <span class="expiry-badge expiry-warning">
                                    <span class="expiry-warning-trigger" tabindex="0" role="button"
                                          aria-label="Ver aviso de expiración">
                                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                        <span class="expiry-tooltip-label">¡Atención! Este pedido será eliminado automáticamente en {{ $days }} día(s). Por favor tome las acciones necesarias antes de la fecha límite.</span>
                                    </span>
                                    {{ $days }} día(s)
                                </span>
                            @else
                                <span class="expiry-badge expiry-ok">{{ $days }} día(s)</span>
                            @endif
                        </td>
                        <td>{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</td>
                        <td><strong>₡{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                        <td>
                            <div class="actions-container">
                                <button class="action-btn view"
                                        onclick="viewSale('{{ $sale->sale_id }}')"
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($sale->status === 'pending')
                                    <button class="action-btn success"
                                            onclick="completeSale('{{ $sale->sale_id }}')"
                                            title="Completar venta">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="action-btn danger"
                                            onclick="cancelSale('{{ $sale->sale_id }}')"
                                            title="Cancelar venta">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                                @if($sale->status === 'completed')
                                    <button class="action-btn warning"
                                            onclick="refundSale('{{ $sale->sale_id }}')"
                                            title="Reembolsar">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                                <button class="action-btn secondary"
                                        onclick="printSale('{{ $sale->sale_id }}')"
                                        title="Imprimir">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <div style="padding: 40px; color: var(--color-muted);">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <p>No hay ventas registradas</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$sales" label="ventas" />
    </div>

    {{-- Historial de Compras CF4-4 --}}
    <div class="sales-container" style="margin-top: 26px;">
        <header class="sales-header">
            <div>
                <h1 style="font-size: 1.35rem; margin: 0;">Historial de Compras</h1>
                <p style="margin: 6px 0 0 0; color: rgba(255,255,255,0.78); font-weight: 500; font-size: 0.95rem;">
                    Compras realizadas desde el carrito web (pendientes y completadas)
                </p>
            </div>
        </header>

        <div class="sales-table-container" style="padding: 16px;">
            <table class="sales-table cf4-purchases-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $sale)
                    <tr>
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
                                Mostrador / Sin datos
                                @if($sale->buyer_email)
                                    <span class="text-muted">({{ $sale->buyer_email }})</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($sale->saleItems && $sale->saleItems->count() > 0)
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    @foreach($sale->saleItems as $item)
                                        <div>{{ $item->quantity }} x {{ $item->product->name ?? 'Producto' }}</div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">Sin productos</span>
                            @endif
                        </td>
                        <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $stateLabel = $sale->status === 'pending'
                                    ? 'Pendiente de retiro'
                                    : ($sale->status === 'completed' ? 'Completado' : ucfirst($sale->status));
                            @endphp
                            <span class="status-badge {{ $sale->status }}">{{ $stateLabel }}</span>
                        </td>
                        <td><strong>₡{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                        <td>
                            <div class="actions-container">
                                <button class="action-btn secondary" type="button"
                                        onclick="viewSale('{{ $sale->sale_id }}')"
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            <div style="padding: 40px; color: var(--color-muted);">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <p>No hay compras registradas</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            <x-pagination :paginator="$purchases" label="compras" />
        </div>
    </div>

    {{-- Laravel routes for sales.js (read via meta, no inline JS) --}}
    <meta name="sales-route-store"     content="{{ route('sales.store') }}">
    <meta name="sales-route-heartbeat" content="{{ route('sales.history.heartbeat') }}">

    {{-- Hidden: latest purchase ID for heartbeat --}}
    <div id="cf4-latest-purchase-sale-id"
         data-value="{{ $latestPurchaseSaleId ?? 0 }}"
         style="display:none;"></div>

    {{-- Modal: Nueva Venta --}}
    <div id="new-sale-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nueva Venta</h3>
                <button class="modal-close" onclick="closeNewSaleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="new-sale-form" method="POST" action="{{ route('sales.store') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label for="buyer_name">Nombre (opcional)</label>
                            <input type="text" id="buyer_name" name="buyer_name"
                                   placeholder="Nombre del comprador (opcional)">
                        </div>
                        <div class="form-group">
                            <label for="buyer_email">Correo electrónico (opcional)</label>
                            <input type="email" id="buyer_email" name="buyer_email"
                                   placeholder="Correo del comprador (opcional)">
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Método de Pago *</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="">Seleccione un método</option>
                                <option value="cash">Efectivo</option>
                                <option value="sinpe">SINPE Móvil</option>
                                <option value="transfer">Transferencia Bancaria</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="payment_reference">Referencia de Pago</label>
                        <input type="text" id="payment_reference" name="payment_reference"
                               placeholder="Número de referencia (opcional)">
                    </div>

                    <div id="productos-container">
                        <h4>Productos</h4>
                        <div class="product-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select name="items[0][product_id]" class="product-select" required>
                                        <option value="">Seleccione un producto</option>
                                        @foreach(\App\Models\Product::where('status', 'active')->get() as $product)
                                        <option value="{{ $product->product_id }}"
                                                data-precio="{{ $product->sale_price }}"
                                                data-stock="{{ $product->stock_current }}">
                                            {{ $product->name }} - ₡{{ number_format((float)$product->sale_price, 0, ',', '.') }} (Stock: {{ $product->stock_current }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" name="items[0][quantity]" min="1" value="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <input type="number" name="items[0][precio_unitario]" step="0.01" readonly>
                                </div>
                                <input type="hidden" name="items[0][total]" value="0">
                                <div class="form-group">
                                    <button type="button" class="remove-product"
                                            onclick="removeProduct(this)" style="display:none;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="addProduct()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>

                    <div class="sale-totals">
                        <div class="total-row">
                            <span>Subtotal:</span><span id="subtotal">₡0.00</span>
                        </div>
                        <div class="total-row">
                            <span>Descuento:</span>
                            <input type="number" id="discount" name="discount" value="0" step="0.01" min="0">
                        </div>
                        <div class="total-row">
                            <span>IVA (%):</span>
                            <span style="display:flex;align-items:center;gap:8px;">
                                <select id="iva_percentage" name="iva_percentage"
                                        style="width:80px;padding:6px 8px;">
                                    @for($p = 0; $p <= 13; $p++)
                                    <option value="{{ $p }}" {{ $p == 0 ? 'selected' : '' }}>{{ $p }}%</option>
                                    @endfor
                                </select>
                                <span id="iva">₡0.00</span>
                            </span>
                        </div>
                        <div class="total-row total-final">
                            <span>Total:</span><span id="total">₡0.00</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas</label>
                        <textarea id="notes" name="notes" rows="3"
                                  placeholder="Notas adicionales (opcional)"></textarea>
                    </div>

                    <div style="display:flex;gap:15px;justify-content:flex-end;margin-top:20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeNewSaleModal()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Ver Detalles --}}
    <div id="view-sale-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles de la Venta</h3>
                <button class="modal-close" onclick="closeViewSaleModal()">&times;</button>
            </div>
            <div class="modal-body" id="view-sale-body">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
                    <p>Cargando detalles...</p>
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