// Helper to get meta tag content safely
const meta   = name => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
// Route definitions used for API calls
const ROUTES = {
    get store()     { return meta('sales-route-store');     },
    get heartbeat() { return meta('sales-route-heartbeat'); },
};

// Retrieve CSRF token from meta tag
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

// Open/close modal for creating a new sale
function openNewSaleModal()   { document.getElementById('new-sale-modal')?.classList.add('active'); }
function closeNewSaleModal()  { document.getElementById('new-sale-modal')?.classList.remove('active'); }
function closeViewSaleModal() { document.getElementById('view-sale-modal')?.classList.remove('active'); }

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

// Recalculate all totals (subtotal, discount, VAT, final total)
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('input[name*="[total]"]').forEach(i => {
        subtotal = roundMoney(subtotal + (parseFloat(i.value) || 0));
    });

    const discountRaw = roundMoney(parseFloat(document.getElementById('discount')?.value) || 0);
    const discountApplied = roundMoney(Math.min(Math.max(0, discountRaw), subtotal));
    const ivaPercent = parseFloat(document.getElementById('iva_percentage')?.value) || 0;
    const pct = Math.min(13, Math.max(0, ivaPercent));
    const taxableBase = roundMoney(subtotal - discountApplied);
    const iva = roundMoney(taxableBase * (pct / 100));
    const total = roundMoney(taxableBase + iva);

    const el = id => document.getElementById(id);
    if (el('subtotal')) el('subtotal').textContent = '₡' + subtotal.toFixed(2);
    if (el('iva'))      el('iva').textContent      = '₡' + iva.toFixed(2);
    if (el('total'))    el('total').textContent    = '₡' + total.toFixed(2);

    const totalsBox = document.querySelector('.sale-totals');
    if (totalsBox) {
        totalsBox.classList.toggle('sale-totals--discount-over', subtotal > 0 && discountRaw > subtotal);
    }
}

// Fetch and display full sale details in a modal
function viewSale(id) {
    const modal = document.getElementById('view-sale-modal');
    const body  = document.getElementById('view-sale-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
            <p>Cargando detalles…</p>
        </div>`;
    modal.classList.add('active');

    fetch(`/sales/${id}`, {
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
        const statusLabels  = { pending: 'Pendiente', completed: 'Confirmado', cancelled: 'Rechazado', refunded: 'Reembolsado' };
        const paymentLabels = { cash: 'Efectivo', sinpe: 'SINPE móvil', transfer: 'Transferencia' };

        let customerName = 'Mostrador / sin datos';
        if (sale.client) {
            customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname]
                .filter(Boolean).join(' ');
            if (sale.client.gmail) customerName += ` (${sale.client.gmail})`;
        } else if (sale.buyer?.name) {
            customerName = sale.buyer.name;
            if (sale.buyer.email) customerName += ` (${sale.buyer.email})`;
        }

        // Generate HTML for products table
        const productsHtml = items.map(item => {
            const prod = item.product || {};
            const up   = parseFloat(item.unit_price || 0);
            const tot  = parseFloat(item.total || 0);
            return `
                <tr>
                    <td>${prod.name || 'N/A'}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                    <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
                </tr>`;
        }).join('');

        // Expiration badge with warning tooltip
        const daysLeft    = sale.days_remaining_until_expiration;
        const expiryBadge = (typeof daysLeft !== 'undefined' && daysLeft <= 0)
            ? '<span class="expiry-badge expiry-expired">Expirado</span>'
            : (sale.is_expiry_warning
                ? `<span class="expiry-badge expiry-warning">
                       <span class="expiry-warning-trigger" tabindex="0" role="button">
                           <i class="fas fa-exclamation-triangle"></i>
                           <span class="expiry-tooltip-label">¡Atención! Este pedido se eliminará automáticamente en ${daysLeft} día(s).</span>
                       </span>
                       ${daysLeft} día(s)
                   </span>`
                : (typeof daysLeft !== 'undefined' ? `${daysLeft} día(s)` : '—'));

        body.innerHTML = `
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Información general</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Factura:</label><span><strong>${sale.invoice_number || '#' + sale.sale_id}</strong></span></div>
                        <div class="detail-item"><label>Fecha:</label><span>${fecha}</span></div>
                        <div class="detail-item"><label>Cliente:</label><span>${customerName}</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${sale.status}">${statusLabels[sale.status] || sale.status}</span></div>
                        <div class="detail-item"><label>Método de pago:</label><span>${paymentLabels[sale.payment_method] || sale.payment_method}</span></div>
                        <div class="detail-item"><label>Días restantes:</label><span>${expiryBadge}</span></div>
                        ${sale.payment_reference ? `<div class="detail-item"><label>Referencia:</label><span>${sale.payment_reference}</span></div>` : ''}
                    </div>
                </div>
                ${productsHtml ? `
                <div class="detail-section">
                    <h4><i class="fas fa-shopping-cart"></i> Productos</h4>
                    <table class="sale-products-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-right">Precio unit.</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${productsHtml}</tbody>
                    </table>
                </div>` : ''}
                <div class="detail-section">
                    <h4><i class="fas fa-calculator"></i> Totales</h4>
                    <div class="totals-summary">
                        <div class="total-item"><span>Subtotal:</span><span>₡${parseFloat(sale.subtotal || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>
                        ${(sale.discount || 0) > 0 ? `<div class="total-item"><span>Descuento:</span><span>-₡${parseFloat(sale.discount).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>` : ''}
                        <div class="total-item"><span>IVA:</span><span>₡${parseFloat(sale.iva || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>
                        <div class="total-item total-final"><span><strong>Total:</strong></span><span><strong>₡${parseFloat(sale.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span></div>
                    </div>
                </div>
                ${sale.notes ? `<div class="detail-section"><h4><i class="fas fa-sticky-note"></i> Notas</h4><p class="sale-notes">${sale.notes}</p></div>` : ''}
            </div>`;
    })
    .catch(() => {
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al cargar los detalles</div>';
    });
}

// Helper to perform a sale state change (complete, cancel, refund)
function _saleAction(url, successMsg) {
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json().then(data => ({ data })))
    .then(({ data }) => {
        if (data.success) {
            let text = data.message || successMsg;
            if (data.sale && data.sale.invoice_number) {
                text += '\n\nFactura: ' + data.sale.invoice_number;
            }
            Swal.fire({
                title: 'Listo',
                text,
                icon: 'success',
                confirmButtonColor: '#2e7d32',
                confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        } else {
            Swal.fire({
                title: 'No se pudo completar',
                text: data.message || 'Ocurrió un error',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    })
    .catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión', icon: 'error' }));
}

// Mark sale as completed
function completeSale(id) {
    Swal.fire({
        title: '¿Confirmar pedido?',
        text: 'El pedido pasará a confirmado y quedará registrado como venta con su factura.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/complete`, 'Pedido confirmado correctamente.'));
}

function cancelSale(id) {
    Swal.fire({
        title: '¿Rechazar pedido?',
        text: 'Se cancelará el pedido y se devolverá el stock al inventario.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'No'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/cancel`, 'Pedido rechazado.'));
}

function refundSale(id) {
    Swal.fire({
        title: '¿Reembolsar venta?',
        text: 'La venta pasará a estado reembolsado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f57c00',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reembolsar',
        cancelButtonText: 'Cancelar'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/refund`, 'Reembolso procesado.'));
}

function printSale(id) {
    window.open(`/sales/${id}/print`, '_blank');
}

//Expose public functions on window (required by Vite/ESM) 
Object.assign(window, {
    openNewSaleModal,
    closeNewSaleModal,
    closeViewSaleModal,
    addProduct,
    removeProduct,
    viewSale,
    completeSale,
    cancelSale,
    refundSale,
    printSale,
});

// DOMContentLoaded 
document.addEventListener('DOMContentLoaded', () => {

    // Auto-print (
    if (meta('auto-print') === '1') {
        window.print();
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
                const res  = await fetch(`${heartbeatUrl}?since=${latestPurchaseSaleId}`, {
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
                    title: 'Descuento inválido',
                    text: 'El descuento no puede ser mayor que el subtotal (₡' + sub.toFixed(2) + ').',
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
                        text: data.message || 'La venta se registró correctamente.',
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
                text: 'Error de conexión',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            }));
        });
    }

    // Event delegation for dynamic product rows 
    document.addEventListener('change', function (e) {
        // Product selection: set unit price from data attribute
        if (e.target.classList.contains('product-select')) {
            const opt = e.target.selectedOptions[0];
            if (opt?.dataset?.precio) {
                const row = e.target.closest('.product-row');
                row.querySelector('input[name*="[precio_unitario]"]').value = opt.dataset.precio;
                calculateProductTotal(row);
            }
        }
        // Quantity change: recalc product total
        if (e.target.name?.includes('[quantity]')) {
            calculateProductTotal(e.target.closest('.product-row'));
        }
        // Discount or VAT change: recalc totals
        if (['discount', 'iva_percentage'].includes(e.target.id)) {
            calculateTotals();
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.id === 'discount') {
            calculateTotals();
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