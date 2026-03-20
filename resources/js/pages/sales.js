/* ── Read routes from <meta> ────────────────────────────────────────────── */
const meta   = name => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
const ROUTES = {
    get store()     { return meta('sales-route-store');     },
    get heartbeat() { return meta('sales-route-heartbeat'); },
};

/* ── Helpers ────────────────────────────────────────────────────────────── */
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/* ── Modals ─────────────────────────────────────────────────────────────── */
function openNewSaleModal()   { document.getElementById('new-sale-modal')?.classList.add('active'); }
function closeNewSaleModal()  { document.getElementById('new-sale-modal')?.classList.remove('active'); }
function closeViewSaleModal() { document.getElementById('view-sale-modal')?.classList.remove('active'); }

/* ── Product rows ───────────────────────────────────────────────────────── */
let productIndex = 1;

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

function removeProduct(button) {
    button.closest('.product-row').remove();
    updateRemoveButtons();
    calculateTotals();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        const btn = row.querySelector('.remove-product');
        if (btn) btn.style.display = rows.length > 1 ? 'block' : 'none';
    });
}

/* ── Totals calculation ─────────────────────────────────────────────────── */
function calculateProductTotal(row) {
    const precio   = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;
    const cantidad = parseInt(row.querySelector('input[name*="[quantity]"]').value) || 0;
    row.querySelector('input[name*="[total]"]').value = (precio * cantidad).toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('input[name*="[total]"]').forEach(i => {
        subtotal += parseFloat(i.value) || 0;
    });

    const discount              = parseFloat(document.getElementById('discount')?.value) || 0;
    const subtotalAfterDiscount = subtotal - discount;
    const ivaPercent            = parseInt(document.getElementById('iva_percentage')?.value, 10) || 0;
    const iva                   = subtotalAfterDiscount * (ivaPercent / 100);
    const total                 = subtotalAfterDiscount + iva;

    const el = id => document.getElementById(id);
    if (el('subtotal')) el('subtotal').textContent = '₡' + subtotal.toFixed(2);
    if (el('iva'))      el('iva').textContent      = '₡' + iva.toFixed(2);
    if (el('total'))    el('total').textContent    = '₡' + total.toFixed(2);
}

/* ── View sale details ──────────────────────────────────────────────────── */
function viewSale(id) {
    const modal = document.getElementById('view-sale-modal');
    const body  = document.getElementById('view-sale-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
            <p>Loading details...</p>
        </div>`;
    modal.classList.add('active');

    fetch(`/sales/${id}`, {
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.sale) {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error loading sale details</div>';
            return;
        }

        const sale          = data.sale;
        const fecha         = new Date(sale.sale_date).toLocaleString('es-CR');
        const items         = sale.sale_items || sale.saleItems || [];
        const statusLabels  = { pending: 'Pending', completed: 'Completed', cancelled: 'Cancelled', refunded: 'Refunded' };
        const paymentLabels = { cash: 'Cash', sinpe: 'SINPE Mobile', transfer: 'Transfer' };

        let customerName = 'Walk-in / No data';
        if (sale.client) {
            customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname]
                .filter(Boolean).join(' ');
            if (sale.client.gmail) customerName += ` (${sale.client.gmail})`;
        } else if (sale.buyer?.name) {
            customerName = sale.buyer.name;
            if (sale.buyer.email) customerName += ` (${sale.buyer.email})`;
        }

        const productsHtml = items.map(item => {
            const prod = item.product || {};
            const up   = parseFloat(item.unit_price || 0);
            const tot  = parseFloat(item.total || 0);
            return `<tr>
                <td>${prod.name || 'N/A'}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
            </tr>`;
        }).join('');

        const daysLeft    = sale.days_remaining_until_expiration;
        const expiryBadge = (typeof daysLeft !== 'undefined' && daysLeft <= 0)
            ? '<span class="expiry-badge expiry-expired">Expired</span>'
            : (sale.is_expiry_warning
                ? `<span class="expiry-badge expiry-warning">
                       <span class="expiry-warning-trigger" tabindex="0" role="button">
                           <i class="fas fa-exclamation-triangle"></i>
                           <span class="expiry-tooltip-label">Attention! This order will be automatically deleted in ${daysLeft} day(s).</span>
                       </span>
                       ${daysLeft} day(s)
                   </span>`
                : (typeof daysLeft !== 'undefined' ? `${daysLeft} day(s)` : '—'));

        body.innerHTML = `
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> General Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Invoice:</label><span><strong>${sale.invoice_number || '#' + sale.sale_id}</strong></span></div>
                        <div class="detail-item"><label>Created at:</label><span>${fecha}</span></div>
                        <div class="detail-item"><label>Customer:</label><span>${customerName}</span></div>
                        <div class="detail-item"><label>Status:</label><span class="status-badge ${sale.status}">${statusLabels[sale.status] || sale.status}</span></div>
                        <div class="detail-item"><label>Payment Method:</label><span>${paymentLabels[sale.payment_method] || sale.payment_method}</span></div>
                        <div class="detail-item"><label>Days remaining:</label><span>${expiryBadge}</span></div>
                        ${sale.payment_reference ? `<div class="detail-item"><label>Reference:</label><span>${sale.payment_reference}</span></div>` : ''}
                    </div>
                </div>
                ${productsHtml ? `
                <div class="detail-section">
                    <h4><i class="fas fa-shopping-cart"></i> Products</h4>
                    <table class="sale-products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${productsHtml}</tbody>
                    </table>
                </div>` : ''}
                <div class="detail-section">
                    <h4><i class="fas fa-calculator"></i> Totals</h4>
                    <div class="totals-summary">
                        <div class="total-item"><span>Subtotal:</span><span>₡${parseFloat(sale.subtotal || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>
                        ${(sale.discount || 0) > 0 ? `<div class="total-item"><span>Discount:</span><span>-₡${parseFloat(sale.discount).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>` : ''}
                        <div class="total-item"><span>VAT:</span><span>₡${parseFloat(sale.iva || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>
                        <div class="total-item total-final"><span><strong>Total:</strong></span><span><strong>₡${parseFloat(sale.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span></div>
                    </div>
                </div>
                ${sale.notes ? `<div class="detail-section"><h4><i class="fas fa-sticky-note"></i> Notes</h4><p class="sale-notes">${sale.notes}</p></div>` : ''}
            </div>`;
    })
    .catch(() => {
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Connection error while loading details</div>';
    });
}

/* ── Sale actions ───────────────────────────────────────────────────────── */
function _saleAction(url, successMsg) {
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success', text: data.message || successMsg,
                icon: 'success', confirmButtonColor: '#2e7d32'
            }).then(() => location.reload());
        } else {
            Swal.fire({ title: 'Error', text: data.message || 'An error occurred', icon: 'error' });
        }
    })
    .catch(() => Swal.fire({ title: 'Error', text: 'Connection error', icon: 'error' }));
}

function completeSale(id) {
    Swal.fire({
        title: 'Complete sale?', text: 'This action will mark the sale as completed.',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#2e7d32', cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, complete', cancelButtonText: 'Cancel'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/complete`, 'Sale completed successfully'));
}

function cancelSale(id) {
    Swal.fire({
        title: 'Cancel sale?', text: 'This action will cancel the sale and release the stock.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel', cancelButtonText: 'No'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/cancel`, 'Sale cancelled successfully'));
}

function refundSale(id) {
    Swal.fire({
        title: 'Refund sale?', text: 'This action will mark the sale as refunded.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#f57c00', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, refund', cancelButtonText: 'Cancel'
    }).then(r => r.isConfirmed && _saleAction(`/sales/${id}/refund`, 'Refund processed successfully'));
}

function printSale(id) {
    window.open(`/sales/${id}/print`, '_blank');
}

/* ── Expose public functions on window (required by Vite/ESM) ───────────── */
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

/* ── DOMContentLoaded ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {

    // ── Auto-print (print.blade) ─────────────────────────────────────────
    if (meta('auto-print') === '1') {
        window.print();
    }

    // ── Heartbeat: reload if new purchases arrive ────────────────────────
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

    // ── New sale form ────────────────────────────────────────────────────
    const form = document.getElementById('new-sale-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const storeUrl = ROUTES.store;
            if (!storeUrl) return;

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
                        title: 'Success', text: 'Sale created successfully',
                        icon: 'success', confirmButtonText: 'Got it'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Error', text: data.message || 'Error creating the sale',
                        icon: 'error', confirmButtonText: 'Got it'
                    });
                }
            })
            .catch(() => Swal.fire({
                title: 'Error', text: 'Connection error',
                icon: 'error', confirmButtonText: 'Got it'
            }));
        });
    }

    // ── Event delegation for product rows ────────────────────────────────
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
        if (['discount', 'iva_percentage'].includes(e.target.id)) {
            calculateTotals();
        }
    });

    // ── Close modals on overlay click ────────────────────────────────────
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });

    updateRemoveButtons();
});