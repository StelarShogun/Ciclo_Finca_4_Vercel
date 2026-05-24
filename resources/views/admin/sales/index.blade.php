@extends('admin.layouts.sales')

@section('Titulo pagina', 'Ventas - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/sales.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/sales/sales.js'])
@endpush

{{-- Sidebar provided by the layout via @yield('aside') --}}
@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

    {{-- ==================== SALES MANAGEMENT ==================== --}}
    <div class="sales-container">

        {{-- Page header --}}
        @component('admin.partials.page-header', ['title' => 'Gestión de ventas'])
            <p>
                Administra las ventas confirmadas, registra devoluciones y crea nuevas ventas desde el
                módulo administrativo.
                Los encargos pendientes del carrito web se gestionan en
                <a href="{{ route('admin.orders.index') }}">Encargos</a>.
            </p>

            @slot('actions')
                <button class="btn btn-primary" onclick="openNewSaleModal()">
                    <i class="fas fa-plus"></i> Nueva venta
                </button>
            @endslot
        @endcomponent

        {{-- ==================== KPI CARDS ==================== --}}
        <div class="kpi-grid">

            {{-- Daily revenue with trend indicator --}}
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Ventas del Día</h3>
                    <div class="kpi-icon success"><i class="fas fa-chart-line"></i></div>
                </div>
                <p class="kpi-value" id="sales-daily-total">₡{{ number_format($dailySales, 0, ',', '.') }}</p>
                <div class="kpi-trend {{ $dailySalesTrend >= 0 ? 'trend-up' : 'trend-down' }}" id="sales-daily-total-trend">
                    <i class="fas fa-arrow-{{ $dailySalesTrend >= 0 ? 'up' : 'down' }}"></i>
                    {{ abs($dailySalesTrend) }}%
                </div>
            </div>

            {{-- Daily transaction count with trend indicator --}}
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Transacciones</h3>
                    <div class="kpi-icon info"><i class="fas fa-receipt"></i></div>
                </div>
                <p class="kpi-value" id="sales-daily-transactions">{{ $dailyTransactions }}</p>
                <div class="kpi-trend {{ $dailyTransactionsTrend >= 0 ? 'trend-up' : 'trend-down' }}" id="sales-daily-transactions-trend">
                    <i class="fas fa-arrow-{{ $dailyTransactionsTrend >= 0 ? 'up' : 'down' }}"></i>
                    {{ abs($dailyTransactionsTrend) }}%
                </div>
            </div>

            {{-- Refund/return count with trend indicator --}}
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Devoluciones</h3>
                    <div class="kpi-icon danger"><i class="fas fa-rotate-left"></i></div>
                </div>
                <p class="kpi-value">{{ $refunds }}</p>
                <div class="kpi-trend {{ $refundsTrend >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $refundsTrend >= 0 ? 'up' : 'down' }}"></i>
                    {{ abs($refundsTrend) }}
                </div>
            </div>
        </div>

        {{-- ==================== FILTERS ==================== --}}
        @component('admin.partials.filters', [
            'action' => route('sales.index'),
            'clearUrl' => route('sales.index'),
            'formId' => 'filters-form',
        ])
            @slot('fields')
                <div class="filter-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="completed"
                                {{ ($salesStatusUi ?? 'completed') === 'completed' ? 'selected' : '' }}>Confirmada
                                (completada)</option>
                            <option value="returned" {{ ($salesStatusUi ?? '') === 'returned' ? 'selected' : '' }}>
                                Devuelta</option>
                            <option value="cancelled" {{ ($salesStatusUi ?? '') === 'cancelled' ? 'selected' : '' }}>
                                Cancelada / rechazada</option>
                            <option value="all" {{ ($salesStatusUi ?? '') === 'all' ? 'selected' : '' }}>Todas las
                                cerradas</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date-range">Rango de Fecha</label>
                        <select id="date-range" name="date_range">
                            <option value="today" {{ request('date_range', 'today') == 'today' ? 'selected' : '' }}>Hoy</option>
                            <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>Esta semana</option>
                            <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>Este mes</option>
                            <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="payment-method">Método de Pago</label>
                        <select id="payment-method" name="payment_method">
                            <option value="">Todos los métodos</option>
                            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Efectivo
                            </option>
                            <option value="sinpe" {{ request('payment_method') == 'sinpe' ? 'selected' : '' }}>SINPE
                                Móvil</option>
                            <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>
                                Transferencia Bancaria</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="search-sale">Buscar</label>
                        <input type="text" id="search-sale" name="search" placeholder="Buscar por cliente o factura..."
                            value="{{ request('search') }}">
                    </div>

            @endslot

            @slot('footer')
                <div id="date-range-custom-row" class="date-range-custom-row"
                    style="{{ request('date_range') == 'custom' ? '' : 'display:none;' }}">
                    <div class="filter-group">
                        <label for="date-from">Fecha inicial</label>
                        <input type="date" id="date-from" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="filter-group">
                        <label for="date-to">Fecha final</label>
                        <input type="date" id="date-to" name="date_to" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div id="date-range-error" class="alert alert-danger" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="date-range-error-msg">El rango de fechas no es válido.</span>
                </div>
            @endslot
        @endcomponent

        {{-- ==================== SALES TABLE ==================== --}}
        @php
            $statusLabels = [
                'pending' => 'Pendiente',
                'ready_to_pickup' => 'Por recoger',
                'completed' => 'Confirmada',
                'cancelled' => 'Rechazado',
                'refunded' => 'Reembolsada',
                'returned' => 'Devuelta',
            ];
            $paymentLabels = ['cash' => 'Efectivo', 'sinpe' => 'SINPE Móvil', 'transfer' => 'Transferencia'];

            $isCustomRange = request('date_range') == 'custom';
            $hasDateFrom = request('date_from');
            $hasDateTo = request('date_to');
            $isDateFiltered =
                in_array(request('date_range'), ['today', 'week', 'month']) ||
                ($isCustomRange && ($hasDateFrom || $hasDateTo));
        @endphp

        <div class="table-section" data-cf4-ajax-pagination data-cf4-ajax-scroll>
        <div id="cf4-list-fragment">
        <div class="sales-table-container">
            <table class="sales-table admin-table">
                <thead>
                    <tr>
                        <th>Número de factura</th>
                        <th>Cliente</th>
                        <th>Fecha de venta</th>
                        <th>Estado</th>
                        <th>Método de Pago</th>
                        <th>Total</th>
                        <th class="admin-table__col--actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td><strong>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</strong></td>

                            {{-- Registered client or walk-in buyer --}}
                            <td>
                                @if ($sale->client_id && $sale->client)
                                    {{ $sale->client->name }} {{ $sale->client->first_surname }}
                                    {{ $sale->client->second_surname ?: '' }}
                                    <span class="text-muted">({{ $sale->client->gmail }})</span>
                                @else
                                    {{ $sale->buyer_name ?: 'Mostrador / Sin datos' }}
                                    @if ($sale->buyer_email)
                                        <span class="text-muted">({{ $sale->buyer_email }})</span>
                                    @endif
                                @endif
                            </td>

                            <td>{{ $sale->adminSaleDateLabel() }}</td>

                            <td>
                                <span class="status-badge {{ $sale->status }}">
                                    {{ $statusLabels[$sale->status] ?? $sale->status }}
                                </span>
                            </td>

                            <td>{{ $paymentLabels[$sale->payment_method] ?? $sale->payment_method }}</td>

                            <td><strong>₡{{ number_format($sale->total, 0, ',', '.') }}</strong></td>

                            {{-- Row actions vary by sale status (CA-01) --}}
                            <td class="admin-table__col--actions">
                                <div class="actions-container">
                                    <button class="action-btn view" onclick="viewSale('{{ $sale->sale_id }}')"
                                        title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if ($sale->status === 'completed')
                                        <a href="{{ route('sales.invoice', ['id' => $sale->sale_id, 'from' => 'sales']) }}" target="_blank"
                                            rel="noopener noreferrer" class="action-link-invoice"
                                            data-confirm-invoice
                                            data-invoice-label="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}"
                                            title="Ver factura en formato estructurado">
                                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                            Ver factura
                                        </a>
                                        {{-- CA-01: Devolución visible únicamente para ventas completadas --}}
                                        <button class="action-btn info"
                                            onclick="openReturnModal('{{ $sale->sale_id }}', '{{ $sale->invoice_number ?? '#' . $sale->sale_id }}')"
                                            title="Registrar devolución">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    @endif
                                    @if ($sale->status !== 'cancelled')
                                        <button class="action-btn secondary"
                                            onclick="printSale(@js($sale->sale_id), @js($sale->invoice_number ?? '#' . $sale->sale_id))"
                                            title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="table-empty-state">
                                    <i class="fas fa-shopping-cart table-empty-icon"></i>
                                    <p>No hay ventas para los filtros seleccionados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination-wrapper">
                <x-admin.pagination :paginator="$sales" label="ventas" />
            </div>
        </div>
        </div>
        </div>

    </div>

    <div id="cf4-latest-purchase-sale-id" data-value="{{ $latestHistorySaleId ?? 0 }}" style="display:none;"></div>

    {{-- Route URLs exposed via meta tags; read by sales.js (avoids inline JS) --}}
    <meta name="sales-route-store" content="{{ route('sales.store') }}">
    <meta name="sales-route-heartbeat" content="{{ route('sales.history.heartbeat') }}">
    <meta name="sales-route-return" content="{{ url('/sales') }}">

    {{-- ==================== MODAL: NEW SALE ==================== --}}
    <div id="new-sale-modal" class="modal-overlay">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nueva venta</h3>
                <button class="modal-close" onclick="closeNewSaleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="new-sale-form" method="POST" action="{{ route('sales.store') }}">
                    @csrf

                    {{-- Optional buyer info (walk-in / counter sales) --}}
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
                                <option value="">Selecciona un método</option>
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

                    {{-- Dynamic product rows; managed by addProduct() / removeProduct() --}}
                    <div id="productos-container">
                        <h4>Productos</h4>
                        <div class="product-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select name="items[0][product_id]" class="product-select" required>
                                        <option value="">Selecciona un producto</option>
                                        @foreach (\App\Models\Product::where('status', 'active')->get() as $product)
                                            <option value="{{ $product->product_id }}"
                                                data-precio="{{ $product->sale_price }}"
                                                data-stock="{{ $product->stock_current }}">
                                                {{ $product->name }} -
                                                ₡{{ number_format((float) $product->sale_price, 0, ',', '.') }} (Stock:
                                                {{ $product->stock_current }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" name="items[0][quantity]" min="1" value="1"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    {{-- Read-only; auto-filled when a product is selected --}}
                                    <input type="number" name="items[0][precio_unitario]" step="0.01" readonly>
                                </div>
                                <input type="hidden" name="items[0][total]" value="0">
                                <div class="form-group">
                                    <button type="button" class="remove-product" onclick="removeProduct(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="addProduct()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>

                    {{-- Order totals: subtotal, discount, final total --}}
                    <div class="sale-totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">₡0.00</span>
                        </div>
                        <div class="total-row">
                            <span>Descuento:</span>
                            <input type="number" id="discount" name="discount" value="0" step="0.01"
                                min="0">
                        </div>
                        <div class="total-row total-final">
                            <span>Total:</span>
                            <span id="total">₡0.00</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Notas adicionales (opcional)"></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeNewSaleModal()">
                    Cancelar
                </button>
                <button type="submit" form="new-sale-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Venta
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: VIEW SALE DETAILS ==================== --}}
    <div id="view-sale-modal" class="edit-modal">
        <div class="modal-backdrop" onclick="closeViewSaleModal()"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles de la Venta</h3>
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
                <button type="button" id="view-sale-print-btn" class="btn btn-primary" style="display:none;">
                    <i class="fas fa-print"></i> Imprimir factura
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeViewSaleModal()">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: RETURN SALE ==================== --}}
    {{-- Shown only for completed sales; requires a mandatory reason before confirming. --}}
    <div id="return-sale-modal" class="modal-overlay">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header">
                <h3><i class="fas fa-rotate-left"></i> Registrar Devolución</h3>
                <button class="modal-close" onclick="closeReturnModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="return-sale-label" class="sale-notes" style="margin-bottom: 1rem;"></p>

                {{-- CA-02: Motivo obligatorio --}}
                <div class="form-group">
                    <label for="return-reason-input">
                        Motivo de la devolución <span style="color: var(--color-danger);">*</span>
                    </label>
                    <textarea id="return-reason-input" rows="4" maxlength="500"
                        placeholder="Describa el motivo de la devolución (obligatorio)..." style="width: 100%; resize: vertical;"></textarea>
                    <small id="return-reason-error" class="text-danger" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i>
                        El motivo es obligatorio y debe tener al menos 3 caracteres.
                    </small>
                </div>

                <div class="alert alert-warning" style="margin-top: 0.75rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Al confirmar, la venta pasará a estado <strong>Devuelta</strong> y el stock de todos
                    los productos será reintegrado al inventario automáticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReturnModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="confirm-return-btn" onclick="confirmReturn()">
                    <i class="fas fa-rotate-left"></i> Confirmar devolución
                </button>
            </div>
        </div>
    </div>

@endsection
