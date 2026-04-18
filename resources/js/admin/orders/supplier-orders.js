// Retrieve CSRF token from meta tag
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/* ---- Date range validation ---- */
document.addEventListener('DOMContentLoaded', () => {
    const form     = document.getElementById('supplier-orders-filters-form');
    const dateFrom = document.getElementById('date_from');
    const dateTo   = document.getElementById('date_to');

    if (dateFrom && dateTo) {
        // Keep min of dateTo in sync with dateFrom
        dateFrom.addEventListener('change', () => {
            dateTo.min = dateFrom.value || '';
            if (dateTo.value && dateTo.value < dateFrom.value) {
                dateTo.value = dateFrom.value;
            }
        });

        // Restore min on page load if dateFrom already has a value
        if (dateFrom.value) dateTo.min = dateFrom.value;
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            const from = dateFrom?.value;
            const to   = dateTo?.value;
            if (from && to && to < from) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rango de fechas inválido',
                        text: 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#2e7d32',
                    });
                } else {
                    alert('La fecha "Hasta" no puede ser anterior a la fecha "Desde".');
                }
            }
        });
    }
});

/* ---- Modal helpers ---- */
function closeViewOrderModal()    { document.getElementById('view-order-modal')?.classList.remove('active'); }
function closeViewSupplierModal() { document.getElementById('view-supplier-modal')?.classList.remove('active'); }

const STATE_LABELS = { draft: 'Borrador', pending: 'Pendiente', confirmed: 'Confirmado', delivered: 'Entregado', cancelled: 'Cancelado' };

let activeOrderIdInModal = null;

function renderActionButtonsHtml(id, state, variant = 'icon') {
    const btn = (cls, title, icon, label, handler) => {
        if (variant === 'text') {
            return `<button type="button" class="btn ${cls}" onclick="${handler}('${id}')" title="${title}">
                <i class="fas ${icon}"></i> ${label}
            </button>`;
        }
        return `<button class="action-btn ${cls}" type="button" onclick="${handler}('${id}')" title="${title}">
            <i class="fas ${icon}"></i>
        </button>`;
    };

    const viewBtn =
        variant === 'icon'
            ? `<button class="action-btn secondary" type="button" onclick="viewOrder('${id}')" title="Ver detalles"><i class="fas fa-eye"></i></button>`
            : '';

    if (state === 'draft') {
        return `${viewBtn}${btn(variant === 'icon' ? 'success' : 'btn-primary', 'Enviar pedido (pasar a Pendiente)', 'fa-paper-plane', 'Enviar', 'sendOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }
    if (state === 'pending') {
        return `${viewBtn}${btn(variant === 'icon' ? 'success' : 'btn-primary', 'Confirmar pedido', 'fa-check', 'Confirmar', 'confirmOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }
    if (state === 'confirmed') {
        return `${viewBtn}${btn(variant === 'icon' ? 'view' : 'btn-primary', 'Marcar como entregado', 'fa-truck', 'Entregado', 'deliverOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }
    return `${viewBtn}`;
}

function updateRowState(id, nextState) {
    const tr = document.querySelector(`tr[data-order-id="${id}"]`);
    if (!tr) return;
    tr.setAttribute('data-order-state', nextState);

    const pill = tr.querySelector('[data-role="order-state-pill"]');
    if (pill) {
        pill.className = `order-status-pill ${nextState}`;
        pill.textContent = STATE_LABELS[nextState] || nextState;
    }

    const actions = tr.querySelector('[data-role="order-actions"]');
    if (actions) {
        actions.innerHTML = renderActionButtonsHtml(id, nextState, 'icon');
    }
}

function updateModalState(nextState) {
    if (!activeOrderIdInModal) return;
    const badge = document.querySelector('#view-order-body [data-role="modal-state-badge"]');
    if (badge) {
        badge.className = `status-badge ${nextState}`;
        badge.textContent = STATE_LABELS[nextState] || nextState;
    }

    const actions = document.querySelector('#view-order-body [data-role="modal-actions"]');
    if (actions) {
        actions.innerHTML = renderActionButtonsHtml(activeOrderIdInModal, nextState, 'text');
    }
}

/* ---- View order details ---- */
function viewOrder(id) {
    const modal = document.getElementById('view-order-modal');
    const body  = document.getElementById('view-order-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
            <p>Cargando detalles…</p>
        </div>`;
    modal.classList.add('active');
    activeOrderIdInModal = String(id);

    fetch(`/supplier-orders/${id}`, {
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.order) {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles.</div>';
            return;
        }

        const order        = data.order;
        const supplierName = order.supplier?.name ?? '—';
        const detailUrl = `/supplier-orders/${order.num_order}/detail`;

        const productsHtml = (order.products || []).map(item => {
            const up  = parseFloat(item.unit_price || 0);
            const tot = parseFloat(item.total || 0);
            return `
                <tr>
                    <td>${item.name || 'N/A'}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                    <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
                </tr>`;
        }).join('');

        body.innerHTML = `
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Información general</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Nº Pedido:</label><span><strong>${order.po_number || ('#' + order.num_order)}</strong></span></div>
                        <div class="detail-item"><label>Proveedor:</label><span>${supplierName}</span></div>
                        <div class="detail-item"><label>Fecha:</label><span>${order.date}</span></div>
                        <div class="detail-item"><label>Entrega estimada:</label><span>${order.estimated_delivery_date || '—'}</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${order.state}" data-role="modal-state-badge">${STATE_LABELS[order.state] || order.state}</span></div>
                    </div>
                    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;" data-role="modal-actions">
                        ${renderActionButtonsHtml(order.num_order, order.state, 'text')}
                        <a class="btn btn-secondary" href="${detailUrl}" title="Ver página de detalle">
                            <i class="fas fa-external-link-alt"></i> Ir a detalle
                        </a>
                    </div>
                </div>
                ${productsHtml ? `
                <div class="detail-section">
                    <h4><i class="fas fa-box"></i> Productos pedidos</h4>
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
                    <h4><i class="fas fa-calculator"></i> Total del pedido</h4>
                    <div class="totals-summary">
                        <div class="total-item total-final">
                            <span><strong>Total:</strong></span>
                            <span><strong>₡${parseFloat(order.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span>
                        </div>
                    </div>
                </div>
            </div>`;
    })
    .catch(() => {
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al cargar los detalles.</div>';
    });
}

/* ---- View supplier details ---- */
function viewSupplier(id) {
    const modal = document.getElementById('view-supplier-modal');
    const body  = document.getElementById('view-supplier-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--color-primary);"></i>
            <p>Cargando datos del proveedor…</p>
        </div>`;
    modal.classList.add('active');

    fetch(`/supplier/details/${id}`, {
        headers: { 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.supplier) {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los datos del proveedor.</div>';
            return;
        }

        const s           = data.supplier;
        const statusLabel = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido' };
        const stars       = '★'.repeat(Math.round(s.rating)) + '☆'.repeat(5 - Math.round(s.rating));

        body.innerHTML = `
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-truck"></i> Datos del proveedor</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Nombre:</label><span><strong>${s.name}</strong></span></div>
                        <div class="detail-item"><label>Contacto:</label><span>${s.primary_contact || '—'}</span></div>
                        <div class="detail-item"><label>Teléfono:</label><span>${s.phone || '—'}</span></div>
                        <div class="detail-item"><label>Correo:</label><span>${s.email || '—'}</span></div>
                        <div class="detail-item"><label>Dirección:</label><span>${s.address || '—'}</span></div>
                        <div class="detail-item"><label>Tiempo de entrega:</label><span>${s.delivery_time} día(s)</span></div>
                        <div class="detail-item"><label>Evaluación:</label><span title="${s.rating}/5">${stars} (${s.rating})</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${s.status}">${statusLabel[s.status] || s.status}</span></div>
                        <div class="detail-item"><label>Productos activos:</label><span>${s.products_count}</span></div>
                    </div>
                </div>
            </div>`;
    })
    .catch(() => {
        body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión.</div>';
    });
}

/* ---- State change helpers ---- */
function _orderAction(id, state, confirmText, successMsg) {
    Swal.fire({
        title: confirmText.title,
        text:  confirmText.text,
        icon:  confirmText.icon,
        showCancelButton: true,
        confirmButtonColor: confirmText.color,
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText.confirm,
        cancelButtonText: 'Cancelar',
    }).then(r => {
        if (!r.isConfirmed) return;

        const disableButtons = (disabled) => {
            document
                .querySelectorAll(`tr[data-order-id="${id}"] .action-btn, #view-order-body [data-role="modal-actions"] .btn`)
                .forEach((el) => {
                    if (el instanceof HTMLButtonElement) el.disabled = disabled;
                });
        };

        disableButtons(true);
        fetch(`/supplier-orders/${id}/state`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept':       'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ state }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Listo',
                    text:  data.message || successMsg,
                    icon:  'success',
                    confirmButtonColor: '#2e7d32',
                    confirmButtonText: 'Entendido',
                }).then(() => {
                    updateRowState(String(id), state);
                    if (activeOrderIdInModal === String(id)) {
                        updateModalState(state);
                    }
                    disableButtons(false);
                });
            } else {
                disableButtons(false);
                Swal.fire({ title: 'Error', text: data.message || 'No se pudo actualizar.', icon: 'error' });
            }
        })
        .catch(() => {
            disableButtons(false);
            Swal.fire({ title: 'Error', text: 'Error de conexión.', icon: 'error' });
        });
    });
}

function submitDraftOrder(id) {
    _orderAction(id, 'pending', {
        title: '¿Enviar borrador?',
        text: 'El pedido pasará a estado pendiente ante el proveedor.',
        icon: 'question',
        color: '#2e7d32',
        confirm: 'Sí, enviar',
    }, 'Pedido enviado a pendiente.');
}

function confirmOrder(id) {
    _orderAction(id, 'confirmed', {
        title:   '¿Confirmar pedido?',
        text:    'El pedido pasará a estado confirmado con el proveedor.',
        icon:    'question',
        color:   '#2e7d32',
        confirm: 'Sí, confirmar',
    }, 'Pedido confirmado correctamente.');
}

function deliverOrder(id) {
    _orderAction(id, 'delivered', {
        title:   '¿Marcar como entregado?',
        text:    'Se registrará la recepción de la mercancía.',
        icon:    'question',
        color:   '#0277bd',
        confirm: 'Sí, marcar entregado',
    }, 'Pedido marcado como entregado.');
}

function sendOrder(id) {
    _orderAction(id, 'pending', {
        title:   '¿Enviar pedido?',
        text:    'El pedido dejará de ser borrador y pasará a estado pendiente.',
        icon:    'question',
        color:   '#2e7d32',
        confirm: 'Sí, enviar',
    }, 'Pedido enviado. Ahora está pendiente.');
}

function cancelOrder(id) {
    _orderAction(id, 'cancelled', {
        title:   '¿Cancelar pedido?',
        text:    'El pedido se marcará como cancelado.',
        icon:    'warning',
        color:   '#d33',
        confirm: 'Sí, cancelar',
    }, 'Pedido cancelado.');
}

// Expose functions on window (required by Vite/ESM)
Object.assign(window, {
    closeViewOrderModal,
    closeViewSupplierModal,
    viewOrder,
    viewSupplier,
    submitDraftOrder,
    confirmOrder,
    deliverOrder,
    sendOrder,
    cancelOrder,
});
