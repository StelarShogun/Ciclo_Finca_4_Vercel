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
            Swal.fire({
                title:             'Devolución registrada',
                text:              data.message || 'La devolución fue procesada correctamente.',
                icon:              'success',
                confirmButtonText: 'Entendido',
            }).then(() => location.reload());
        } else {
            Swal.fire({
                title:             'Error',
                text:              data.message || 'No se pudo registrar la devolución.',
                icon:              'error',
                confirmButtonText: 'Cerrar',
            });
        }
    })
    .catch(() => {
        closeReturnModal();
        Swal.fire({
            title:             'Error de conexión',
            text:              'No se pudo conectar con el servidor. Intente nuevamente.',
            icon:              'error',
            confirmButtonText: 'Cerrar',
        });
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
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
            <p>Cargando detalles...</p>
        </div>`;
    modal.classList.add('active');

    fetch('/sales/' + id, {
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.sale) {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles</div>';
            return;
        }

        const sale          = data.sale;
        const fecha         = new Date(sale.sale_date).toLocaleString('es-CR');
        const items         = sale.sale_items || sale.saleItems || [];
        const statusLabels  = {
            pending:   'Pendiente',
            completed: 'Confirmado',
            cancelled: 'Rechazado',
            refunded:  'Reembolsado (histórico)',
            returned:  'Devuelta',
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

        // Expiration badge with warning tooltip
        const daysLeft    = sale.days_remaining_until_expiration;
        let expiryBadge;
        if (typeof daysLeft !== 'undefined' && daysLeft <= 0) {
            expiryBadge = '<span class="expiry-badge expiry-expired">Expirado</span>';
        } else if (sale.is_expiry_warning) {
            expiryBadge = '<span class="expiry-badge expiry-warning">'
                + '<span class="expiry-warning-trigger" tabindex="0" role="button">'
                + '<i class="fas fa-exclamation-triangle"></i>'
                + '<span class="expiry-tooltip-label">Atencion! Este pedido se eliminara automaticamente en ' + daysLeft + ' dia(s).</span>'
                + '</span>' + daysLeft + ' dia(s)</span>';
        } else {
            expiryBadge = (typeof daysLeft !== 'undefined') ? daysLeft + ' dia(s)' : '-';
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
                + '<div class="detail-item"><label>Fecha:</label><span>' + returnedAt + '</span></div>'
                + '<div class="detail-item"><label>Registrado por:</label><span>' + returnedBy + '</span></div>'
                + '</div></div>';
        }

        body.innerHTML = '<div class="sale-details">'
            + '<div class="detail-section">'
            + '<h4><i class="fas fa-info-circle"></i> Informacion general</h4>'
            + '<div class="detail-grid">'
            + '<div class="detail-item"><label>Factura:</label><span><strong>' + (sale.invoice_number || '#' + sale.sale_id) + '</strong></span></div>'
            + '<div class="detail-item"><label>Fecha:</label><span>' + fecha + '</span></div>'
            + '<div class="detail-item"><label>Cliente:</label><span>' + customerName + '</span></div>'
            + '<div class="detail-item"><label>Estado:</label><span class="status-badge ' + sale.status + '">' + (statusLabels[sale.status] || sale.status) + '</span></div>'
            + '<div class="detail-item"><label>Metodo de pago:</label><span>' + (paymentLabels[sale.payment_method] || sale.payment_method) + '</span></div>'
            + '<div class="detail-item"><label>Dias restantes:</label><span>' + expiryBadge + '</span></div>'
            + refRow
            + '</div></div>'
            + '<div class="detail-section"><h4><i class="fas fa-shopping-cart"></i> Productos</h4>'
            + (productsHtml
                ? '<table class="sale-products-table"><thead><tr>'
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
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexion al cargar los detalles</div>';
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
            Swal.fire({ title: 'Listo', text, icon: 'success', confirmButtonText: 'Entendido' })
                .then(() => location.reload());
        } else {
            Swal.fire({ title: 'Error', text: data.message || 'No se pudo completar la acción.', icon: 'error', confirmButtonText: 'Cerrar' });
        }
    })
    .catch(() => Swal.fire({ title: 'Error de conexión', text: 'Intente nuevamente.', icon: 'error', confirmButtonText: 'Cerrar' }));
}

function completeSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || ('#' + id);
    Swal.fire({
        title: 'Confirmar encargo con factura: ' + invoiceLabel + '?',
        text: 'El encargo pasara a confirmado y quedara registrado como venta con su factura.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Si, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(r => r.isConfirmed && _saleAction('/sales/' + id + '/complete', 'Encargo confirmado correctamente.'));
}

function cancelSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || ('#' + id);
    Swal.fire({
        title: 'Rechazar encargo con factura: ' + invoiceLabel + '?',
        text: 'Ingrese el motivo de cancelación. El stock reservado se devolverá al inventario.',
        input: 'textarea',
        inputPlaceholder: 'Motivo de cancelación',
        inputAttributes: { maxlength: 500 },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'No',
        inputValidator: value => {
            if (! value || value.trim().length < 3) {
                return 'Debe ingresar un motivo de al menos 3 caracteres.';
            }
            return null;
        },
    }).then(r => {
        if (! r.isConfirmed) return;

        _saleAction(
            '/sales/' + id + '/cancel',
            'Encargo: ' + invoiceLabel + ' eliminado.',
            { reason: r.value.trim() }
        );
    });
}

function printSale(id) {
    window.open('/sales/' + id + '/print', '_blank');
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

    // Auto-print
    if (meta('auto-print') === '1') {
        window.print();
    }

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

    // Heartbeat: reload if new purchases arrive
    const latestEl = document.getElementById('cf4-latest-purchase-sale-id');
    if (latestEl) {
        let latestPurchaseSaleId = parseInt(latestEl.dataset.value, 10) || 0;

        async function heartbeatCheck() {
            if (document.visibilityState === 'hidden') return;
            const heartbeatUrl = ROUTES.heartbeat;
            if (!heartbeatUrl) return;
            try {
                const res  = await fetch(heartbeatUrl + '?since=' + latestPurchaseSaleId, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (typeof data.latestSaleId !== 'undefined') latestPurchaseSaleId = data.latestSaleId;
                if (data.hasNew) window.location.reload();
            } catch (_) { /* fail silently */ }
        }

        setInterval(heartbeatCheck, 20000);
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
                Swal.fire({
                    title: 'Descuento invalido',
                    text: 'El descuento no puede ser mayor que el subtotal (CRC' + sub.toFixed(2) + ').',
                    icon: 'warning',
                    confirmButtonText: 'Corregir'
                });
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
                    Swal.fire({
                        title: 'Venta creada',
                        text: data.message || 'La venta se registro correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Entendido'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo crear la venta',
                        icon: 'error',
                        confirmButtonText: 'Cerrar'
                    });
                }
            })
            .catch(() => Swal.fire({
                title: 'Error',
                text: 'Error de conexion',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            }));
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