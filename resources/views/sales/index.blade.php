<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ventas - Ciclo Pérez Admin</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .expiry-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
        .expiry-ok { background: #e8f5e9; color: #2e7d32; }
        .expiry-warning { background: #fff3e0; color: #e65100; }
        .expiry-expired { background: #ffebee; color: #c62828; }
        /* Mini label (tooltip) al pasar el ratón sobre el icono de peligro */
        .expiry-warning-trigger { position: relative; cursor: help; display: inline-flex; align-items: center; }
        .expiry-warning-trigger .expiry-tooltip-label {
            visibility: hidden; opacity: 0;
            position: absolute; z-index: 100;
            bottom: 100%; left: 50%; transform: translateX(-50%) translateY(-6px);
            background: #bf360c; color: #fff;
            font-size: 0.75rem; font-weight: 500; white-space: normal; max-width: 220px;
            padding: 8px 10px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            pointer-events: none; transition: opacity 0.2s, visibility 0.2s;
        }
        .expiry-warning-trigger .expiry-tooltip-label::after {
            content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
            border: 6px solid transparent; border-top-color: #bf360c;
        }
        .expiry-warning-trigger:hover .expiry-tooltip-label,
        .expiry-warning-trigger:focus-within .expiry-tooltip-label { visibility: visible; opacity: 1; }
    </style>
</head>
<body class="admin-layout">
    @include('partes.aside')

    <main class="admin-main">
        <div class="sales-container">
            <header class="sales-header">
                <div>
                    <h1>Gestión de Ventas</h1>
                    <p>Registra y administra las ventas del sistema</p>
                </div>
                <div class="sales-actions">
                    <button class="btn btn-primary" onclick="openNewSaleModal()">
                        <i class="fas fa-plus"></i> Nueva Venta
                    </button>
                </div>
            </header>

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

            <div class="filters-section">
                <h2 class="filters-title">Filtros de Búsqueda</h2>
                <form method="GET" action="{{ route('sales.index') }}" id="filters-form">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="status">Estado</label>
                            <select id="status" name="status">
                                <option value="">Todos los estados</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Reembolsada</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date-range">Rango de Fechas</label>
                            <select id="date-range" name="date_range">
                                <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Hoy</option>
                                <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>Esta semana</option>
                                <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>Este mes</option>
                                <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="payment-method">Método de Pago</label>
                            <select id="payment-method" name="payment_method">
                                <option value="">Todos los métodos</option>
                                <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="sinpe" {{ request('payment_method') == 'sinpe' ? 'selected' : '' }}>SINPE Móvil</option>
                                <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>Transferencia Bancaria</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search-sale">Buscar</label>
                            <input type="text" id="search-sale" name="search" placeholder="Buscar por cliente..." value="{{ request('search') }}">
                        </div>
                        <div class="filter-group filter-buttons">
                            <button type="submit" class="btn btn-primary filter-btn"><i class="fas fa-search"></i> Aplicar Filtros</button>
                            <a href="{{ route('sales.index') }}" class="btn btn-primary filter-btn"><i class="fas fa-times"></i> Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

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
                        @php
                    $statusLabels = ['pending' => 'Pendiente', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'refunded' => 'Reembolsada'];
                    $paymentLabels = ['cash' => 'Efectivo', 'sinpe' => 'SINPE Móvil', 'transfer' => 'Transferencia'];
                @endphp
                        @forelse($sales as $sale)
                        <tr>
                            <td><strong>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</strong></td>
                            <td>{{ $sale->customer->nombre ?? 'N/A' }} {{ $sale->customer->apellido ?? '' }}</td>
                            <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                            <td><span class="status-badge {{ $sale->status }}">{{ $statusLabels[$sale->status] ?? $sale->status }}</span></td>
                            <td>
                                @php
                                    $days = $sale->days_remaining_until_expiration;
                                    $warn = $sale->is_expiry_warning;
                                @endphp
                                @if($days <= 0)
                                    <span class="expiry-badge expiry-expired" title="Pedido fuera de vigencia (será eliminado)">
                                        <i class="fas fa-clock"></i> Expirado
                                    </span>
                                @elseif($warn)
                                    <span class="expiry-badge expiry-warning">
                                        <span class="expiry-warning-trigger" tabindex="0" role="button" aria-label="Ver aviso de vigencia">
                                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                            <span class="expiry-tooltip-label">¡Atención! Este pedido será eliminado automáticamente en {{ $days }} día(s). Realice las acciones necesarias antes de la fecha límite.</span>
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
                                    <button class="action-btn view" onclick="viewSale('{{ $sale->sale_id }}')" title="Ver detalles"><i class="fas fa-eye"></i></button>
                                    @if($sale->status === 'pending')
                                    <button class="action-btn success" onclick="completeSale('{{ $sale->sale_id }}')" title="Completar venta"><i class="fas fa-check"></i></button>
                                    <button class="action-btn danger" onclick="cancelSale('{{ $sale->sale_id }}')" title="Cancelar venta"><i class="fas fa-times"></i></button>
                                    @endif
                                    @if($sale->status === 'completed')
                                    <button class="action-btn warning" onclick="refundSale('{{ $sale->sale_id }}')" title="Reembolsar"><i class="fas fa-undo"></i></button>
                                    @endif
                                    <button class="action-btn secondary" onclick="printSale('{{ $sale->sale_id }}')" title="Imprimir"><i class="fas fa-print"></i></button>
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

            <x-pagination :paginator="$sales" label="de ventas" />
        </div>

        <!-- Modal Nueva Venta -->
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
                                <label for="customer_id">Cliente *</label>
                                <select id="customer_id" name="customer_id" required>
                                    <option value="">Seleccionar cliente</option>
                                    @foreach(\App\Models\Usuario::where('rol', 'cliente')->get() as $c)
                                    <option value="{{ $c->usuario_id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Método de Pago *</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="">Seleccionar método</option>
                                    <option value="cash">Efectivo</option>
                                    <option value="sinpe">SINPE Móvil</option>
                                    <option value="transfer">Transferencia Bancaria</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_reference">Referencia de Pago</label>
                            <input type="text" id="payment_reference" name="payment_reference" placeholder="Número de referencia (opcional)">
                        </div>

                        <div id="productos-container">
                            <h4>Productos</h4>
                            <div class="product-row">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select name="items[0][product_id]" class="product-select" required>
                                        <option value="">Seleccionar producto</option>
                                        @foreach(\App\Models\Product::where('status', 'active')->get() as $product)
                                        <option value="{{ $product->product_id }}" data-precio="{{ $product->sale_price }}" data-stock="{{ $product->stock_current }}">
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
                                    <button type="button" class="remove-product" onclick="removeProduct(this)" style="display: none;"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="addProduct()"><i class="fas fa-plus"></i> Agregar Producto</button>

                        <div class="sale-totals">
                            <div class="total-row"><span>Subtotal:</span><span id="subtotal">₡0.00</span></div>
                            <div class="total-row"><span>Descuento:</span><input type="number" id="discount" name="discount" value="0" step="0.01" min="0"></div>
                            <div class="total-row">
                                <span>IVA (%):</span>
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <select id="iva_percentage" name="iva_percentage" style="width: 80px; padding: 6px 8px;">
                                        @for($p = 0; $p <= 13; $p++)
                                        <option value="{{ $p }}" {{ $p == 0 ? 'selected' : '' }}>{{ $p }}%</option>
                                        @endfor
                                    </select>
                                    <span id="iva">₡0.00</span>
                                </span>
                            </div>
                            <div class="total-row total-final"><span>Total:</span><span id="total">₡0.00</span></div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notas</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Notas adicionales (opcional)"></textarea>
                        </div>

                        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="closeNewSaleModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear Venta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Detalles de Venta -->
        <div id="view-sale-modal" class="modal-overlay">
            <div class="modal-content modal-auto-size">
                <div class="modal-header">
                    <h3><i class="fas fa-eye"></i> Detalles de Venta</h3>
                    <button class="modal-close" onclick="closeViewSaleModal()">&times;</button>
                </div>
                <div class="modal-body" id="view-sale-body">
                    <div class="loading-spinner" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--color-primary);"></i>
                        <p>Cargando detalles...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeViewSaleModal()"><i class="fas fa-times"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function getCSRFToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function openNewSaleModal() { document.getElementById('new-sale-modal').classList.add('active'); }
        function closeNewSaleModal() { document.getElementById('new-sale-modal').classList.remove('active'); }

        let productIndex = 1;
        function addProduct() {
            const container = document.getElementById('productos-container');
            const newRow = document.createElement('div');
            newRow.className = 'product-row';
            newRow.innerHTML = `
                <div class="form-group">
                    <label>Producto</label>
                    <select name="items[${productIndex}][product_id]" class="product-select" required>
                        <option value="">Seleccionar producto</option>
                        @foreach(\App\Models\Product::where('status', 'active')->get() as $product)
                        <option value="{{ $product->product_id }}" data-precio="{{ $product->sale_price }}" data-stock="{{ $product->stock_current }}">
                            {{ $product->name }} - ₡{{ number_format((float)$product->sale_price, 0, ',', '.') }} (Stock: {{ $product->stock_current }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="items[${productIndex}][quantity]" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Precio Unitario</label>
                    <input type="number" name="items[${productIndex}][precio_unitario]" step="0.01" readonly>
                </div>
                <input type="hidden" name="items[${productIndex}][total]" value="0">
                <div class="form-group">
                    <button type="button" class="remove-product" onclick="removeProduct(this)"><i class="fas fa-trash"></i></button>
                </div>
            `;
            container.appendChild(newRow);
            productIndex++;
            updateRemoveButtons();
        }

        function removeProduct(button) {
            button.closest('.product-row').remove();
            updateRemoveButtons();
            calculateTotals();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach((row) => {
                const removeBtn = row.querySelector('.remove-product');
                removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const option = e.target.selectedOptions[0];
                if (option && option.dataset.precio) {
                    const row = e.target.closest('.product-row');
                    const precioInput = row.querySelector('input[name*="[precio_unitario]"]');
                    precioInput.value = option.dataset.precio;
                    calculateProductTotal(row);
                }
            }
            if (e.target.name && e.target.name.includes('[quantity]')) {
                const row = e.target.closest('.product-row');
                calculateProductTotal(row);
            }
            if (e.target.id === 'discount' || e.target.id === 'iva_percentage') calculateTotals();
        });

        function calculateProductTotal(row) {
            const precio = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;
            const cantidad = parseInt(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const total = precio * cantidad;
            row.querySelector('input[name*="[total]"]').value = total.toFixed(2);
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('input[name*="[total]"]').forEach(input => { subtotal += parseFloat(input.value) || 0; });
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const subtotalAfterDiscount = subtotal - discount;
            const ivaPercent = parseInt(document.getElementById('iva_percentage').value, 10) || 0;
            const iva = subtotalAfterDiscount * (ivaPercent / 100);
            const total = subtotalAfterDiscount + iva;
            document.getElementById('subtotal').textContent = '₡' + subtotal.toFixed(2);
            document.getElementById('iva').textContent = '₡' + iva.toFixed(2);
            document.getElementById('total').textContent = '₡' + total.toFixed(2);
        }

        function viewSale(id) {
            const modal = document.getElementById('view-sale-modal');
            const body = document.getElementById('view-sale-body');
            body.innerHTML = '<div class="loading-spinner" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin fa-3x" style="color: var(--color-primary);"></i><p>Cargando detalles...</p></div>';
            modal.classList.add('active');

            fetch(`/sales/${id}`, {
                headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sale) {
                    const sale = data.sale;
                    const fecha = new Date(sale.sale_date).toLocaleString('es-CR');
                    const items = sale.sale_items || sale.saleItems || [];
                    let productsHtml = items.map(item => {
                        const prod = item.product || {};
                        const qty = item.quantity;
                        const up = parseFloat(item.unit_price || 0);
                        const tot = parseFloat(item.total || 0);
                        return `<tr><td>${prod.name || 'N/A'}</td><td class="text-center">${qty}</td><td class="text-right">₡${up.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td><td class="text-right"><strong>₡${tot.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong></td></tr>`;
                    }).join('');

                    const customerName = sale.customer ? (sale.customer.nombre || '') + ' ' + (sale.customer.apellido || '') : 'N/A';
                    const statusLabels = { pending: 'Pendiente', completed: 'Completada', cancelled: 'Cancelada', refunded: 'Reembolsada' };
                    const paymentLabels = { cash: 'Efectivo', sinpe: 'SINPE Móvil', transfer: 'Transferencia' };
                    const statusText = statusLabels[sale.status] || sale.status;
                    const paymentText = paymentLabels[sale.payment_method] || sale.payment_method;
                    body.innerHTML = `
                        <div class="sale-details">
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Información General</h4>
                                <div class="detail-grid">
                                    <div class="detail-item"><label>Factura:</label><span><strong>${sale.invoice_number || '#' + sale.sale_id}</strong></span></div>
                                    <div class="detail-item"><label>Fecha creación:</label><span>${fecha}</span></div>
                                    <div class="detail-item"><label>Cliente:</label><span>${customerName}</span></div>
                                    <div class="detail-item"><label>Estado:</label><span class="status-badge ${sale.status}">${statusText}</span></div>
                                    <div class="detail-item"><label>Método de Pago:</label><span>${paymentText}</span></div>
                                    <div class="detail-item"><label>Días restantes:</label><span>${typeof sale.days_remaining_until_expiration !== 'undefined' ? sale.days_remaining_until_expiration : '—'} día(s) ${sale.is_expiry_warning ? `<span class="expiry-badge expiry-warning"><span class="expiry-warning-trigger" tabindex="0" role="button" aria-label="Ver aviso"><i class="fas fa-exclamation-triangle"></i><span class="expiry-tooltip-label">¡Atención! Este pedido será eliminado automáticamente en ${sale.days_remaining_until_expiration} día(s). Realice las acciones necesarias antes de la fecha límite.</span></span> Próximo a expirar</span>` : ''} ${(typeof sale.days_remaining_until_expiration !== 'undefined' && sale.days_remaining_until_expiration <= 0) ? '<span class="expiry-badge expiry-expired">Expirado</span>' : ''}</span></div>
                                    ${sale.payment_reference ? `<div class="detail-item"><label>Referencia:</label><span>${sale.payment_reference}</span></div>` : ''}
                                </div>
                            </div>
                            ${productsHtml ? `
                            <div class="detail-section">
                                <h4><i class="fas fa-shopping-cart"></i> Productos</h4>
                                <table class="sale-products-table">
                                    <thead><tr><th>Producto</th><th class="text-center">Cantidad</th><th class="text-right">Precio Unit.</th><th class="text-right">Total</th></tr></thead>
                                    <tbody>${productsHtml}</tbody>
                                </table>
                            </div>` : ''}
                            <div class="detail-section">
                                <h4><i class="fas fa-calculator"></i> Totales</h4>
                                <div class="totals-summary">
                                    <div class="total-item"><span>Subtotal:</span><span>₡${parseFloat(sale.subtotal || 0).toLocaleString('es-CR', {minimumFractionDigits: 2})}</span></div>
                                    ${(sale.discount || 0) > 0 ? `<div class="total-item"><span>Descuento:</span><span>-₡${parseFloat(sale.discount).toLocaleString('es-CR', {minimumFractionDigits: 2})}</span></div>` : ''}
                                    <div class="total-item"><span>IVA:</span><span>₡${parseFloat(sale.iva || 0).toLocaleString('es-CR', {minimumFractionDigits: 2})}</span></div>
                                    <div class="total-item total-final"><span><strong>Total:</strong></span><span><strong>₡${parseFloat(sale.total || 0).toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong></span></div>
                                </div>
                            </div>
                            ${sale.notes ? `<div class="detail-section"><h4><i class="fas fa-sticky-note"></i> Notas</h4><p class="sale-notes">${sale.notes}</p></div>` : ''}
                        </div>
                    `;
                } else {
                    body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar los detalles de la venta</div>';
                }
            })
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al cargar los detalles</div>';
            });
        }

        function closeViewSaleModal() { document.getElementById('view-sale-modal').classList.remove('active'); }

        function completeSale(id) {
            Swal.fire({ title: '¿Completar venta?', text: 'Esta acción marcará la venta como completada.', icon: 'question', showCancelButton: true, confirmButtonColor: '#2e7d32', cancelButtonColor: '#d33', confirmButtonText: 'Sí, completar', cancelButtonText: 'Cancelar' })
            .then((result) => {
                if (result.isConfirmed) {
                    fetch(`/sales/${id}/complete`, { method: 'POST', headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' } })
                    .then(r => r.json()).then(data => {
                        if (data.success) Swal.fire({ title: 'Éxito', text: data.message || 'Venta completada exitosamente', icon: 'success', confirmButtonColor: '#2e7d32' }).then(() => location.reload());
                        else Swal.fire({ title: 'Error', text: data.message || 'Error al completar la venta', icon: 'error' });
                    }).catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' }));
                }
            });
        }

        function cancelSale(id) {
            Swal.fire({ title: '¿Cancelar venta?', text: 'Esta acción cancelará la venta y liberará el stock de los productos.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, cancelar', cancelButtonText: 'No' })
            .then((result) => {
                if (result.isConfirmed) {
                    fetch(`/sales/${id}/cancel`, { method: 'POST', headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' } })
                    .then(r => r.json()).then(data => {
                        if (data.success) Swal.fire({ title: 'Éxito', text: data.message || 'Venta cancelada exitosamente', icon: 'success', confirmButtonColor: '#2e7d32' }).then(() => location.reload());
                        else Swal.fire({ title: 'Error', text: data.message || 'Error al cancelar la venta', icon: 'error' });
                    }).catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' }));
                }
            });
        }

        function refundSale(id) {
            Swal.fire({ title: '¿Reembolsar venta?', text: 'Esta acción marcará la venta como reembolsada.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#f57c00', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, reembolsar', cancelButtonText: 'Cancelar' })
            .then((result) => {
                if (result.isConfirmed) {
                    fetch(`/sales/${id}/refund`, { method: 'POST', headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' } })
                    .then(r => r.json()).then(data => {
                        if (data.success) Swal.fire({ title: 'Éxito', text: data.message || 'Reembolso procesado exitosamente', icon: 'success', confirmButtonColor: '#2e7d32' }).then(() => location.reload());
                        else Swal.fire({ title: 'Error', text: data.message || 'Error al procesar el reembolso', icon: 'error' });
                    }).catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' }));
                }
            });
        }

        function printSale(id) { window.open(`/sales/${id}/print`, '_blank'); }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('new-sale-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('{{ route("sales.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            closeNewSaleModal();
                            Swal.fire({ title: 'Éxito', text: 'Venta creada exitosamente', icon: 'success', confirmButtonText: 'Entendido' }).then(() => location.reload());
                        } else {
                            Swal.fire({ title: 'Error', text: data.message || 'Error al crear la venta', icon: 'error', confirmButtonText: 'Entendido' });
                        }
                    })
                    .catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error', confirmButtonText: 'Entendido' }));
                });
            }
            updateRemoveButtons();
        });

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
        });
    </script>
</body>
</html>
