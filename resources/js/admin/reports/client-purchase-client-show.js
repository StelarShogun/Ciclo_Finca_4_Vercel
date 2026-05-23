/**
 * CF4-33 — vista por cliente: modal solo para detalle de venta (JSON /sales/:id maquetado).
 */

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function esc(text) {
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function buildSaleDetailHtml(sale) {
    const fecha = new Date(sale.sale_date).toLocaleString('es-CR');
    const items = sale.sale_items || sale.saleItems || [];
    const statusLabels = {
        pending: 'Pendiente',
        ready_to_pickup: 'Por recoger',
        completed: 'Confirmado',
        cancelled: 'Rechazado',
        refunded: 'Reembolsado',
        returned: 'Devuelta',
    };
    const paymentLabels = { cash: 'Efectivo', sinpe: 'SINPE móvil', transfer: 'Transferencia' };

    let customerName = 'Mostrador / sin datos';
    if (sale.client) {
        customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname]
            .filter(Boolean)
            .join(' ');
        if (sale.client.gmail) customerName += ` (${sale.client.gmail})`;
    } else if (sale.buyer?.name) {
        customerName = sale.buyer.name;
        if (sale.buyer.email) customerName += ` (${sale.buyer.email})`;
    }

    const productsHtml = items
        .map((item) => {
            const prod = item.product || {};
            const up = parseFloat(item.unit_price || 0);
            const tot = parseFloat(item.total || 0);
            return `<tr>
                <td>${esc(prod.name || 'N/A')}</td>
                <td class="text-center">${esc(String(item.quantity))}</td>
                <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
            </tr>`;
        })
        .join('');

    // Expiration badge — same convention as the main sales modal:
    // - ready_to_pickup uses the new pickup_time_remaining_label (72h from ready_at).
    // - completed sales no longer expire, render an em dash.
    // - other statuses keep the legacy 30-day countdown from sale_date.
    let expiryBadge = '<span class="text-muted">—</span>';
    if (sale.status === 'ready_to_pickup') {
        const pickupLabel = (sale.pickup_time_remaining_label || '').trim();
        if (sale.is_pickup_expired || pickupLabel === 'Vencido') {
            expiryBadge = '<span class="expiry-badge expiry-expired"><i class="fas fa-clock"></i> Vencido</span>';
        } else if (pickupLabel !== '') {
            expiryBadge = `<span class="expiry-badge expiry-ok">${esc(pickupLabel)}</span>`;
        }
    } else if (sale.status === 'completed') {
        expiryBadge = '<span class="text-muted">—</span>';
    } else {
        const daysLeft = sale.days_remaining_until_expiration;
        if (typeof daysLeft !== 'undefined' && daysLeft <= 0) {
            expiryBadge = '<span class="expiry-badge expiry-expired">Expirado</span>';
        } else if (sale.is_expiry_warning) {
            expiryBadge = `<span class="expiry-badge expiry-warning">${esc(String(daysLeft))} día(s)</span>`;
        } else if (typeof daysLeft !== 'undefined') {
            expiryBadge = esc(String(daysLeft)) + ' día(s)';
        }
    }

    const refBlock =
        sale.payment_reference != null && String(sale.payment_reference).trim() !== ''
            ? `<div class="detail-item"><label>Referencia:</label><span>${esc(sale.payment_reference)}</span></div>`
            : '';

    const productsSection = productsHtml
        ? `<div class="detail-section">
                <h4><i class="fas fa-shopping-cart"></i> Productos</h4>
                <table class="sale-products-table admin-table">
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
            </div>`
        : '';

    const discountRow =
        (sale.discount || 0) > 0
            ? `<div class="total-item"><span>Descuento:</span><span>-₡${parseFloat(sale.discount).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>`
            : '';

    const notesSection =
        sale.notes != null && String(sale.notes).trim() !== ''
            ? `<div class="detail-section"><h4><i class="fas fa-sticky-note"></i> Notas</h4><p class="sale-notes">${esc(sale.notes)}</p></div>`
            : '';

    return `<div class="sale-details">
        <div class="detail-section">
            <h4><i class="fas fa-info-circle"></i> Información general</h4>
            <div class="detail-grid">
                <div class="detail-item"><label>Factura:</label><span><strong>${esc(sale.invoice_number || '#' + sale.sale_id)}</strong></span></div>
                <div class="detail-item"><label>Fecha:</label><span>${esc(fecha)}</span></div>
                <div class="detail-item"><label>Cliente:</label><span>${esc(customerName)}</span></div>
                <div class="detail-item"><label>Estado:</label><span class="status-badge ${esc(sale.status)}">${esc(statusLabels[sale.status] || sale.status)}</span></div>
                <div class="detail-item"><label>Método de pago:</label><span>${esc(paymentLabels[sale.payment_method] || sale.payment_method)}</span></div>
                <div class="detail-item"><label>Días restantes:</label><span>${expiryBadge}</span></div>
                ${refBlock}
            </div>
        </div>
        ${productsSection}
        <div class="detail-section">
            <h4><i class="fas fa-calculator"></i> Totales</h4>
            <div class="totals-summary">
                <div class="total-item"><span>Subtotal:</span><span>₡${parseFloat(sale.subtotal || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</span></div>
                ${discountRow}
                <div class="total-item total-final"><span><strong>Total:</strong></span><span><strong>₡${parseFloat(sale.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span></div>
            </div>
        </div>
        ${notesSection}
    </div>`;
}

function initClientPurchaseClientShow() {
    const root = document.getElementById('client-purchase-client-show-root');
    if (!root) return;

    const template = root.dataset.saleJsonUrlTemplate || '';
    const dialog = document.getElementById('sale-detail-dialog');
    const bodyEl = document.getElementById('sale-detail-dialog-body');
    const closeBtn = document.getElementById('sale-detail-dialog-close');

    function saleJsonUrl(saleId) {
        return template.replace('__SALE__', String(saleId));
    }

    function ensureDialogOnBody() {
        if (dialog && dialog.parentElement !== document.body) {
            document.body.appendChild(dialog);
        }
    }

    async function openSaleDetail(saleId) {
        if (!dialog || !bodyEl) return;
        bodyEl.innerHTML = '<p class="loading-cell">Cargando…</p>';
        ensureDialogOnBody();
        dialog.showModal();

        try {
            const res = await fetch(saleJsonUrl(saleId), {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success || !data.sale) {
                bodyEl.innerHTML = `<div class="empty-cell">${esc(data.message || 'No se pudieron cargar los detalles.')}</div>`;
                return;
            }
            bodyEl.innerHTML = buildSaleDetailHtml(data.sale);
        } catch {
            bodyEl.innerHTML = '<div class="empty-cell">Error de conexión al cargar el detalle.</div>';
        }
    }

    root.querySelectorAll('.btn-open-sale-detail').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-sale-id');
            if (id) void openSaleDetail(id);
        });
    });

    if (closeBtn && dialog) {
        closeBtn.addEventListener('click', () => dialog.close());
    }
    if (dialog) {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close();
        });
    }
}

document.addEventListener('DOMContentLoaded', initClientPurchaseClientShow);
