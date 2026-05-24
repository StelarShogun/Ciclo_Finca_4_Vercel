import { loadListFragment } from '../../shared/ajax-pagination.js';
import { syncAllKpiValueScales, syncKpiValueScale, initKpiValueScaleObserver } from '../shared/kpi-value-scale.js';
import {
    cf4Confirm,
    cf4PromptTextarea,
    cf4Toast,
    cf4Error,
    cf4Warning,
} from '../shared/swal.js';

function formatColones(amount) {
    const value = Math.round(parseFloat(amount) || 0);
    return '₡' + value.toLocaleString('es-CR', { maximumFractionDigits: 0 });
}

function updateSalesDailyKpis(data) {
    const totalEl = document.getElementById('sales-daily-total');
    if (totalEl && data.dailySales !== undefined) {
        totalEl.textContent = formatColones(data.dailySales);
        syncKpiValueScale(totalEl);
    }

    const totalTrendEl = document.getElementById('sales-daily-total-trend');
    if (totalTrendEl && data.dailySalesTrend !== undefined) {
        const trend = parseFloat(data.dailySalesTrend) || 0;
        const positive = trend >= 0;
        totalTrendEl.classList.toggle('trend-up', positive);
        totalTrendEl.classList.toggle('trend-down', !positive);
        totalTrendEl.innerHTML = `<i class="fas fa-arrow-${positive ? 'up' : 'down'}"></i> ${Math.abs(trend)}%`;
    }

    const txEl = document.getElementById('sales-daily-transactions');
    if (txEl && data.dailyTransactions !== undefined) {
        txEl.textContent = String(data.dailyTransactions);
        syncKpiValueScale(txEl);
    }

    const txTrendEl = document.getElementById('sales-daily-transactions-trend');
    if (txTrendEl && data.dailyTransactionsTrend !== undefined) {
        const trend = parseFloat(data.dailyTransactionsTrend) || 0;
        const positive = trend >= 0;
        txTrendEl.classList.toggle('trend-up', positive);
        txTrendEl.classList.toggle('trend-down', !positive);
        txTrendEl.innerHTML = `<i class="fas fa-arrow-${positive ? 'up' : 'down'}"></i> ${Math.abs(trend)}%`;
    }
}

async function refreshSalesListFragment() {
    const root = document.querySelector('[data-cf4-ajax-pagination]');
    if (!root) {
        return;
    }

    const url = `${window.location.pathname}${window.location.search}`;
    await loadListFragment(url, root, { pushState: false, scroll: false });
}

async function refreshSalesDailyKpis() {
    const heartbeatUrl = ROUTES.heartbeat;
    if (!heartbeatUrl) {
        return;
    }

    try {
        const res = await fetch(`${heartbeatUrl}?since=0`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            return;
        }

        updateSalesDailyKpis(await res.json());
    } catch {
        /* fail silently */
    }
}

async function afterSalesListMutation() {
    await Promise.all([
        refreshSalesListFragment(),
        refreshSalesDailyKpis(),
    ]);
}

function resetNewSaleForm() {
    const form = document.getElementById('new-sale-form');
    if (!form) {
        return;
    }

    form.reset();

    const container = document.getElementById('productos-container');
    container?.querySelectorAll('.product-row').forEach((row, index) => {
        if (index > 0) {
            row.remove();
        }
    });

    productIndex = 1;

    const firstRow = container?.querySelector('.product-row');
    if (firstRow) {
        firstRow.querySelectorAll('input').forEach((input) => {
            if (input.name?.includes('[quantity]')) {
                input.value = '1';
            } else if (input.name?.includes('[total]')) {
                input.value = '0';
            } else if (input.name?.includes('[precio_unitario]')) {
                input.value = '';
            }
        });

        const select = firstRow.querySelector('.product-select');
        if (select) {
            select.selectedIndex = 0;
        }
    }

    calculateTotals();
    updateRemoveButtons();
}

// Helper to get meta tag content safely
const meta   = name => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
// Route definitions used for API calls
const ROUTES = {
    get store()     { return meta('sales-route-store');     },
    get heartbeat() { return meta('sales-route-heartbeat'); },
    get returnBase(){ return meta('sales-route-return');    },
};

// Retrieve CSRF token from meta tag
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

// Open/close modal for creating a new sale
function openNewSaleModal()   { document.getElementById('new-sale-modal')?.classList.add('active'); }
function closeNewSaleModal()  { document.getElementById('new-sale-modal')?.classList.remove('active'); }
function closeViewSaleModal() { document.getElementById('view-sale-modal')?.classList.remove('active'); }

// Internal state for the return modal
let _returnSaleId = null;

// Opens the return modal for a completed sale (CA-01).
function openReturnModal(saleId, invoiceLabel) {
    _returnSaleId = saleId;

    const label = document.getElementById('return-sale-label');
    if (label) {
        label.textContent = `Venta: ${invoiceLabel}. Complete el motivo para continuar.`;
    }

    // Reset textarea and error state each time the modal opens.
    const textarea = document.getElementById('return-reason-input');
    if (textarea) textarea.value = '';

    const errorMsg = document.getElementById('return-reason-error');
    if (errorMsg) errorMsg.style.display = 'none';

    document.getElementById('return-sale-modal')?.classList.add('active');
}

function closeReturnModal() {
    _returnSaleId = null;
    document.getElementById('return-sale-modal')?.classList.remove('active');
}

function confirmReturn() {
    if (! _returnSaleId) return;

    const textarea = document.getElementById('return-reason-input');
    const reason   = (textarea?.value ?? '').trim();
    const errorMsg = document.getElementById('return-reason-error');

    if (reason.length < 3) {
        if (errorMsg) errorMsg.style.display = '';
        textarea?.focus();
        return;
    }

    if (errorMsg) errorMsg.style.display = 'none';

    const btn = document.getElementById('confirm-return-btn');
    if (btn) {
        btn.disabled    = true;
        btn.innerHTML   = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    }

    fetch(`${ROUTES.returnBase}/${_returnSaleId}/return`, {
        method:  'POST',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept':       'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reason: reason }),
    })
    .then(r => r.json())
    .then(data => {
        closeReturnModal();

        if (data.success) {
            void cf4Toast({
                icon: 'success',
                title: 'Devolución registrada',
                text: data.message || 'La devolución fue procesada correctamente.',
                timer: 3000,
            }).then(() => location.reload());
        } else {
            void cf4Error(data.message || 'No se pudo registrar la devolución.', 'Error');
        }
    })
    .catch(() => {
        closeReturnModal();
        void cf4Error('No se pudo conectar con el servidor. Intente nuevamente.', 'Error de conexión');
    })
    .finally(() => {
        if (btn) {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-rotate-left"></i> Confirmar devolución';
        }
    });
}

// ==================== END RETURN MODAL ====================

// Counter for dynamic product rows
let productIndex = 1;

// Add a new product row to the sale form
function addProduct() {
    const container   = document.getElementById('productos-container');
    const firstSelect = container?.querySelector('.product-select');
    const optionsHTML = firstSelect
        ? firstSelect.innerHTML
        : '<option value="">Select product</option>';

    const newRow = document.createElement('div');
    newRow.className = 'product-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>Product</label>
                <select name="items[${productIndex}][product_id]" class="product-select" required>
                    ${optionsHTML}
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="items[${productIndex}][quantity]" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label>Unit Price</label>
                <input type="number" name="items[${productIndex}][precio_unitario]" step="0.01" readonly>
            </div>
            <input type="hidden" name="items[${productIndex}][total]" value="0">
            <div class="form-group">
                <button type="button" class="remove-product" onclick="removeProduct(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;

    container.appendChild(newRow);
    productIndex++;
    updateRemoveButtons();
}

// Remove a product row from the sale form
function removeProduct(button) {
    button.closest('.product-row').remove();
    updateRemoveButtons();
    calculateTotals();
}

// Show/hide remove button based on number of rows (minimum one row)
function updateRemoveButtons() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        const btn = row.querySelector('.remove-product');
        if (btn) btn.style.display = rows.length > 1 ? 'block' : 'none';
    });
}

/** Coincide con SalesController::roundMoney (2 decimales). */
function roundMoney(n) {
    return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
}

// Calculate total for a single product row (price * quantity)
function calculateProductTotal(row) {
    const precio   = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;
    const cantidad = parseInt(row.querySelector('input[name*="[quantity]"]').value, 10) || 0;
    const lineTotal = roundMoney(precio * cantidad);
    row.querySelector('input[name*="[total]"]').value = lineTotal.toFixed(2);
    calculateTotals();
}

// Recalculate all totals (subtotal, discount, final total)
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('input[name*="[total]"]').forEach(i => {
        subtotal = roundMoney(subtotal + (parseFloat(i.value) || 0));
    });

    const discountRaw     = roundMoney(parseFloat(document.getElementById('discount')?.value) || 0);
    const discountApplied = roundMoney(Math.min(Math.max(0, discountRaw), subtotal));
    const taxableBase     = roundMoney(subtotal - discountApplied);
    const total           = taxableBase;

    const el = id => document.getElementById(id);
    if (el('subtotal')) el('subtotal').textContent = 'CRC' + subtotal.toFixed(2);
    if (el('total'))    el('total').textContent    = 'CRC' + total.toFixed(2);

    const totalsBox = document.querySelector('.sale-totals');
    if (totalsBox) {
        totalsBox.classList.toggle('sale-totals--discount-over', subtotal > 0 && discountRaw > subtotal);
    }
}

// Show or hide custom date range fields based on the selected option
function toggleCustomDateFields(value) {
    const fromGroup = document.getElementById('custom-date-from-group');
    const toGroup   = document.getElementById('custom-date-to-group');
    if (!fromGroup || !toGroup) return;

    const show = value === 'custom';
    fromGroup.style.display = show ? '' : 'none';
    toGroup.style.display   = show ? '' : 'none';

    if (!show) {
        const fromInput = document.getElementById('date-from');
        const toInput   = document.getElementById('date-to');
        if (fromInput) fromInput.value = '';
        if (toInput)   toInput.value   = '';
    }
}

// Validate that the custom date range is correct before submitting the filters form
function validateDateRange() {
    const rangeSelect = document.getElementById('date-range');
    if (!rangeSelect || rangeSelect.value !== 'custom') return true;

    const fromVal = document.getElementById('date-from')?.value ?? '';
    const toVal   = document.getElementById('date-to')?.value ?? '';

    const errorBox = document.getElementById('date-range-error');
    const errorMsg = document.getElementById('date-range-error-msg');

    const today   = new Date().toISOString().split('T')[0];
    const minDate = '2020-01-01';

    if ((fromVal && fromVal < minDate) || (toVal && toVal < minDate)) {
        if (errorBox && errorMsg) {
            errorMsg.textContent = 'Las fechas no pueden ser anteriores al 1 de enero de 2020.';
            errorBox.style.display = '';
        }
        errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    if ((fromVal && fromVal > today) || (toVal && toVal > today)) {
        if (errorBox && errorMsg) {
            errorMsg.textContent = 'Las fechas no pueden ser posteriores al día de hoy.';
            errorBox.style.display = '';
        }
        errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    if (fromVal && toVal && fromVal > toVal) {
        if (errorBox && errorMsg) {
            errorMsg.textContent = 'La fecha inicial no puede ser mayor que la fecha final. Por favor corrija el rango.';
            errorBox.style.display = '';
        }
        errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    if (errorBox) errorBox.style.display = 'none';
    return true;
}

// ==================== END CUSTOM DATE RANGE ====================

// Fetch and display full sale details in a modal.
// Also renders the return metadata block when status is "returned" (CA-03).
function viewSale(id) {
    const modal = document.getElementById('view-sale-modal');
    const body  = document.getElementById('view-sale-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
            <p>Cargando detalles…</p>
        </div>`;
    modal.classList.add('active');

    fetch('/sales/' + id, {
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.sale) {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles.</div>';
            return;
        }

        const sale          = data.sale;
        const items         = sale.sale_items || sale.saleItems || [];
        const isWebOrder    = sale.order_source === 'web_cart' || sale.order_source == null;
        const saleDateLabel = isWebOrder ? 'Fecha de pedido' : 'Fecha de venta';
        const saleDateValue = isWebOrder
            ? (sale.order_placed_at_label || sale.sale_date_label || '—')
            : (sale.sale_date_label || '—');
        const readyAtRow = sale.ready_at || sale.ready_at_label
            ? '<div class="detail-item"><label>Fecha listo para recoger:</label><span>'
                + (sale.ready_at_label || '—') + '</span></div>'
            : '';
        const confirmedAtRow = sale.status === 'completed'
            ? '<div class="detail-item"><label>Fecha de confirmación:</label><span>'
                + (sale.confirmed_at_label || '—') + '</span></div>'
            : '';
        const statusLabels  = {
            pending:         'Pendiente',
            ready_to_pickup: 'Por recoger',
            completed:       'Confirmado',
            cancelled:       'Rechazado',
            refunded:        'Reembolsado (histórico)',
            returned:        'Devuelta',
        };
        const paymentLabels = { cash: 'Efectivo', sinpe: 'SINPE movil', transfer: 'Transferencia' };

        let customerName = 'Mostrador / sin datos';
        if (sale.client) {
            customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname]
                .filter(Boolean).join(' ');
            if (sale.client.gmail) customerName += ' (' + sale.client.gmail + ')';
        } else if (sale.buyer?.name) {
            customerName = sale.buyer.name;
            if (sale.buyer.email) customerName += ' (' + sale.buyer.email + ')';
        }

        // Generate HTML for products table
        const productsHtml = items.map(item => {
            const prod = item.product || {};
            const up   = parseFloat(item.unit_price || 0);
            const tot  = parseFloat(item.total || 0);
            return '<tr>'
                + '<td>' + (prod.name || 'N/A') + '</td>'
                + '<td class="text-center">' + item.quantity + '</td>'
                + '<td class="text-right">CRC' + up.toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</td>'
                + '<td class="text-right"><strong>CRC' + tot.toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</strong></td>'
                + '</tr>';
        }).join('');

        // Expiration badge.
        // - ready_to_pickup: deadline = ready_at + READY_TO_PICKUP_EXPIRATION_HOURS
        //   (exposed by the backend as pickup_time_remaining_label / is_pickup_expired).
        // - completed: confirmed sales have no deadline anymore (they are already finalized).
        // - other statuses: legacy 30-day countdown from sale_date.
        let expiryBadge;
        if (sale.status === 'ready_to_pickup') {
            const pickupLabel = (sale.pickup_time_remaining_label || '').trim();
            if (sale.is_pickup_expired || pickupLabel === 'Vencido') {
                expiryBadge = '<span class="expiry-badge expiry-expired"><i class="fas fa-clock"></i> Vencido</span>';
            } else if (pickupLabel !== '') {
                expiryBadge = '<span class="expiry-badge expiry-ok">' + pickupLabel + '</span>';
            } else {
                expiryBadge = '-';
            }
        } else if (sale.status === 'completed') {
            expiryBadge = '<span class="text-muted">—</span>';
        } else {
            const daysLeft = sale.days_remaining_until_expiration;
            if (typeof daysLeft !== 'undefined' && daysLeft <= 0) {
                expiryBadge = '<span class="expiry-badge expiry-expired">Expirado</span>';
            } else if (sale.is_expiry_warning) {
                expiryBadge = '<span class="expiry-badge expiry-warning">'
                    + '<span class="expiry-warning-trigger" tabindex="0" role="button">'
                    + '<i class="fas fa-exclamation-triangle"></i>'
                    + '<span class="expiry-tooltip-label">¡Atención! Este pedido se eliminará automáticamente en ' + daysLeft + ' día(s).</span>'
                    + '</span>' + daysLeft + ' día(s)</span>';
            } else {
                expiryBadge = (typeof daysLeft !== 'undefined') ? daysLeft + ' día(s)' : '-';
            }
        }

        const discountRow = (sale.discount || 0) > 0
            ? '<div class="total-item"><span>Descuento:</span><span>-CRC' + parseFloat(sale.discount).toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</span></div>'
            : '';

        const refRow = sale.payment_reference
            ? '<div class="detail-item"><label>Referencia:</label><span>' + sale.payment_reference + '</span></div>'
            : '';

        let returnSection = '';
        if (sale.status === 'returned') {
            const returnedAt = sale.returned_at ? new Date(sale.returned_at).toLocaleString('es-CR') : '—';
            const returnedBy = sale.returned_by ? sale.returned_by.name : 'Administrador';
            returnSection = '<div class="detail-section">'
                + '<h4><i class="fas fa-rotate-left"></i> Datos de la devolución</h4>'
                + '<div class="detail-grid">'
                + '<div class="detail-item"><label>Fecha de devolución:</label><span>' + returnedAt + '</span></div>'
                + '<div class="detail-item"><label>Registrado por:</label><span>' + returnedBy + '</span></div>'
                + '</div></div>';
        }

        body.innerHTML = '<div class="sale-details">'
            + '<div class="detail-section">'
            + '<h4><i class="fas fa-info-circle"></i> Información general</h4>'
            + '<div class="detail-grid">'
            + '<div class="detail-item"><label>Factura:</label><span><strong>' + (sale.invoice_number || '#' + sale.sale_id) + '</strong></span></div>'
            + '<div class="detail-item"><label>' + saleDateLabel + ':</label><span>' + saleDateValue + '</span></div>'
            + readyAtRow
            + confirmedAtRow
            + '<div class="detail-item"><label>Cliente:</label><span>' + customerName + '</span></div>'
            + '<div class="detail-item"><label>Estado:</label><span class="status-badge ' + sale.status + '">' + (statusLabels[sale.status] || sale.status) + '</span></div>'
            + '<div class="detail-item"><label>Metodo de pago:</label><span>' + (paymentLabels[sale.payment_method] || sale.payment_method) + '</span></div>'
            + '<div class="detail-item"><label>Dias restantes:</label><span>' + expiryBadge + '</span></div>'
            + refRow
            + '</div></div>'
            + '<div class="detail-section"><h4><i class="fas fa-shopping-cart"></i> Productos</h4>'
            + (productsHtml
                ? '<table class="sale-products-table admin-table"><thead><tr>'
                    + '<th>Producto</th><th class="text-center">Cantidad</th><th class="text-right">Precio unit.</th><th class="text-right">Total</th>'
                    + '</tr></thead><tbody>' + productsHtml + '</tbody></table>'
                : '<p class="text-muted">Sin productos registrados.</p>')
            + '</div>'
            + '<div class="detail-section"><h4><i class="fas fa-calculator"></i> Totales</h4>'
            + '<div class="totals-summary">'
            + '<div class="total-item"><span>Subtotal:</span><span>CRC' + parseFloat(sale.subtotal || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</span></div>'
            + discountRow
            + '<div class="total-item total-final"><span><strong>Total:</strong></span><span><strong>CRC' + parseFloat(sale.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</strong></span></div>'
            + '</div></div>'
            + returnSection
            + (sale.notes ? '<div class="detail-section"><h4><i class="fas fa-sticky-note"></i> Notas</h4><p class="sale-notes">' + sale.notes + '</p></div>' : '')
            + '</div>';
    })
    .catch(() => {
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al cargar los detalles.</div>';
    });
}

// Helper to perform a sale state change (complete, cancel, refund)
function _saleAction(url, successMsg, payload = null) {
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: payload ? JSON.stringify(payload) : null,
    })
    .then(r => r.json().then(data => ({ data })))
    .then(({ data }) => {
        if (data.success) {
            let text = data.message || successMsg;
            if (data.sale && data.sale.invoice_number) {
                text += '\n\nFactura: ' + data.sale.invoice_number;
            }
            void cf4Toast({ icon: 'success', title: 'Listo', text, timer: 3000 })
                .then(() => location.reload());
        } else {
            void cf4Error(data.message || 'No se pudo completar la acción.', 'Error');
        }
    })
    .catch(() => void cf4Error('Intente nuevamente.', 'Error de conexión'));
}

async function completeSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || ('#' + id);
    const result = await cf4Confirm({
        title: 'Confirmar encargo con factura: ' + invoiceLabel + '?',
        text: 'El encargo pasará a confirmado y quedará registrado como venta con su factura.',
        icon: 'question',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
    });
    if (result.isConfirmed) {
        _saleAction('/sales/' + id + '/complete', 'Encargo confirmado correctamente.');
    }
}

async function cancelSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || ('#' + id);
    const result = await cf4PromptTextarea({
        title: 'Rechazar encargo con factura: ' + invoiceLabel + '?',
        text: 'Ingrese el motivo de cancelación. El stock reservado se devolverá al inventario.',
        placeholder: 'Motivo de cancelación',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
        minLength: 3,
        maxLength: 500,
        danger: true,
    });
    if (!result.isConfirmed) return;

    _saleAction(
        '/sales/' + id + '/cancel',
        'Encargo: ' + invoiceLabel + ' eliminado.',
        { reason: result.value }
    );
}

async function confirmInvoiceAction({ title, text, confirmText, onConfirm }) {
    const result = await cf4Confirm({
        title,
        text: text || '',
        icon: 'question',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        onConfirm();
    }
}

function confirmInvoiceOpen(url, target, label) {
    const text = label ? `Factura: ${label}` : 'Se abrirá la factura en una nueva pestaña.';
    confirmInvoiceAction({
        title: '¿Deseas ver la factura?',
        text,
        confirmText: 'Ver factura',
        onConfirm: () => {
            if (!url) return;
            if (target === '_blank') {
                window.open(url, '_blank', 'noopener,noreferrer');
                return;
            }
            window.location.href = url;
        },
    });
}

function confirmInvoicePrint(label, onConfirm) {
    const text = label ? `Factura: ${label}` : 'Se abrirá el diálogo de impresión del navegador.';
    confirmInvoiceAction({
        title: '¿Deseas imprimir esta factura?',
        text,
        confirmText: 'Imprimir',
        onConfirm,
    });
}

function bindInvoiceConfirmationLinks() {
    document.querySelectorAll('[data-confirm-invoice]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href) return;
            event.preventDefault();
            const label = link.getAttribute('data-invoice-label') || '';
            const target = link.getAttribute('target') || '_self';
            confirmInvoiceOpen(href, target, label);
        });
    });
}

function bindPrintButtons() {
    document.querySelectorAll('[data-confirm-print]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const label = button.getAttribute('data-invoice-label') || meta('invoice-label') || '';
            confirmInvoicePrint(label, () => window.print());
        });
    });
}

function printSale(id, invoiceLabel = '') {
    confirmInvoicePrint(invoiceLabel, () => {
        window.open('/sales/' + id + '/print', '_blank', 'noopener,noreferrer');
    });
}

// Expose public functions on window (required by Vite/ESM)
Object.assign(window, {
    openNewSaleModal,
    closeNewSaleModal,
    closeViewSaleModal,
    addProduct,
    removeProduct,
    viewSale,
    completeSale,
    cancelSale,
    printSale,
    openReturnModal,
    closeReturnModal,
    confirmReturn,
});

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initKpiValueScaleObserver();
    syncAllKpiValueScales(document.querySelector('.kpi-grid') || document);

    // Auto-print
    if (meta('auto-print') === '1') {
        const label = meta('invoice-label') || '';
        window.addEventListener('load', () => {
            setTimeout(() => {
                confirmInvoicePrint(label, () => window.print());
            }, 300);
        });
    }

    bindInvoiceConfirmationLinks();
    bindPrintButtons();

    const dateRangeSelect = document.getElementById('date-range');
    if (dateRangeSelect) {
        toggleCustomDateFields(dateRangeSelect.value);

        dateRangeSelect.addEventListener('change', function () {
            toggleCustomDateFields(this.value);
            const errorBox = document.getElementById('date-range-error');
            if (errorBox) errorBox.style.display = 'none';
        });
    }

    const filtersForm = document.getElementById('filters-form');
    if (filtersForm) {
        filtersForm.addEventListener('submit', function (e) {
            if (!validateDateRange()) {
                e.preventDefault();
            }
        });
    }

    // Heartbeat on Ventas: refresh KPIs and list without full page reload.
    if (!document.querySelector('[data-cf4-orders-heartbeat]')) {
        const latestEl = document.getElementById('cf4-latest-purchase-sale-id');
        let salesHeartbeatReady = false;

        async function heartbeatCheck() {
            if (document.visibilityState === 'hidden') return;
            const heartbeatUrl = ROUTES.heartbeat;
            if (!heartbeatUrl) return;

            const since = parseInt(latestEl?.dataset?.value, 10) || 0;

            try {
                const res = await fetch(`${heartbeatUrl}?since=${since}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                if (typeof data.latestSaleId !== 'undefined') {
                    if (latestEl) {
                        latestEl.dataset.value = String(data.latestSaleId);
                    }
                }
                updateSalesDailyKpis(data);
                if (data.hasNew && salesHeartbeatReady) {
                    await refreshSalesListFragment();
                }
                salesHeartbeatReady = true;
            } catch {
                /* fail silently */
            }
        }

        void heartbeatCheck();
        setInterval(heartbeatCheck, 15000);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                void heartbeatCheck();
            }
        });
    }

    // New sale form submission
    const form = document.getElementById('new-sale-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const storeUrl = ROUTES.store;
            if (!storeUrl) return;

            let sub = 0;
            document.querySelectorAll('input[name*="[total]"]').forEach(i => {
                sub = roundMoney(sub + (parseFloat(i.value) || 0));
            });
            const disc = roundMoney(parseFloat(document.getElementById('discount')?.value) || 0);
            if (disc > sub) {
                void cf4Warning(
                    'El descuento no puede ser mayor que el subtotal (₡' + sub.toFixed(2) + ').',
                    'Descuento inválido'
                );
                return;
            }

            fetch(storeUrl, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeNewSaleModal();
                    resetNewSaleForm();
                    const newSaleId = data.sale?.sale_id;
                    if (newSaleId) {
                        const latestEl = document.getElementById('cf4-latest-purchase-sale-id');
                        if (latestEl) {
                            latestEl.dataset.value = String(newSaleId);
                        }
                    }
                    void cf4Toast({
                        icon: 'success',
                        title: 'Venta creada',
                        text: data.message || 'La venta se registró correctamente.',
                        timer: 3000,
                    });
                    void afterSalesListMutation();
                } else {
                    void cf4Error(data.message || 'No se pudo crear la venta', 'Error');
                }
            })
            .catch(() => void cf4Error('Error de conexión', 'Error'));
        });
    }

    // Event delegation for dynamic product rows
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('product-select')) {
            const opt = e.target.selectedOptions[0];
            if (opt?.dataset?.precio) {
                const row = e.target.closest('.product-row');
                row.querySelector('input[name*="[precio_unitario]"]').value = opt.dataset.precio;
                calculateProductTotal(row);
            }
        }
        if (e.target.name?.includes('[quantity]')) {
            calculateProductTotal(e.target.closest('.product-row'));
        }
        if (e.target.id === 'discount') {
            calculateTotals();
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.id === 'discount') {
            calculateTotals();
        }
        // Live-clear the return reason error as the user types
        if (e.target.id === 'return-reason-input') {
            const errorMsg = document.getElementById('return-reason-error');
            if (errorMsg && e.target.value.trim().length >= 3) {
                errorMsg.style.display = 'none';
            }
        }
    });

    // Close modals when clicking on overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });

    updateRemoveButtons();
});