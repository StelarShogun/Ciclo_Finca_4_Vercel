// Retrieve CSRF token from meta tag
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/** SweetAlert2 v11 — diálogos sin `buttonsStyling` heredado, botones redondeados. */
function cf4SwalDialogDefaults() {
    return {
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
    };
}

function cf4SwalConfirmBase(confirmButtonClass) {
    return {
        ...cf4SwalDialogDefaults(),
        showCancelButton: true,
        cancelButtonText: 'Volver',
        customClass: {
            popup: 'cf4-swal-popup',
            confirmButton: confirmButtonClass,
            cancelButton: 'cf4-swal-btn cf4-swal-btn-muted',
            actions: 'cf4-swal-actions',
            title: 'cf4-swal-title',
            htmlContainer: 'cf4-swal-html',
        },
    };
}

function cf4SwalToastFire(icon, title, text) {
    return Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        text: text || undefined,
        showConfirmButton: false,
        timer: icon === 'success' ? 3600 : 5200,
        timerProgressBar: true,
        showCloseButton: true,
    });
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
                        ...cf4SwalDialogDefaults(),
                        icon: 'warning',
                        title: 'Rango de fechas inválido',
                        text: 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
                        showCancelButton: false,
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'cf4-swal-popup',
                            confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
                            title: 'cf4-swal-title',
                        },
                    });
                } else {
                    alert('La fecha "Hasta" no puede ser anterior a la fecha "Desde".');
                }
            }
        });
    }
});

/* ---- Modal helpers ---- */
function closeViewOrderModal() {
    document.getElementById('view-order-modal')?.classList.remove('active');
    activeOrderIdInModal = null;
}
function closeViewSupplierModal() { document.getElementById('view-supplier-modal')?.classList.remove('active'); }

const STATE_LABELS = { draft: 'Borrador', pending: 'Pendiente', confirmed: 'Confirmado', delivered: 'Entregado', cancelled: 'Cancelado' };

let activeOrderIdInModal = null;

/** Estado mostrado en listado (`tr`) o en página de detalle (`data-supplier-order-state`). */
function supplierOrderStateSnapshot(id) {
    const sid = String(id);
    const tr = document.querySelector(`tr[data-order-id="${sid}"]`);
    if (tr) {
        return tr.getAttribute('data-order-state');
    }
    const root = document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${sid}"]`);
    if (root) {
        return root.getAttribute('data-supplier-order-state');
    }
    return null;
}

function setSupplierOrderStateSnapshot(id, state) {
    const sid = String(id);
    const tr = document.querySelector(`tr[data-order-id="${sid}"]`);
    if (tr) {
        tr.setAttribute('data-order-state', state);
    }
    const root = document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${sid}"]`);
    if (root) {
        root.setAttribute('data-supplier-order-state', state);
    }
}

/** Nº de pedido si la URL es `/supplier-orders/{id}/detail`, si no `null`. */
function supplierOrderDetailPageOrderId() {
    const m = window.location.pathname.match(/^\/supplier-orders\/(\d+)\/detail$/);
    return m ? m[1] : null;
}

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
        return `${viewBtn}${btn(variant === 'icon' ? 'success' : 'btn-primary', 'Confirmar pedido', 'fa-check', 'Confirmar', 'confirmOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
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
    setSupplierOrderStateSnapshot(id, nextState);

    const tr = document.querySelector(`tr[data-order-id="${id}"]`);
    if (!tr) return;

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

function updateRowConfirmation(id, confirmedAt, confirmedByLabel) {
    const tr = document.querySelector(`tr[data-order-id="${id}"]`);
    if (!tr) return;
    const cell = tr.querySelector('[data-role="order-conf-cell"]');
    if (!cell) return;

    if (confirmedAt) {
        const user = confirmedByLabel
            ? `<span class="order-conf-user" title="${confirmedByLabel.replace(/"/g, '&quot;')}">${escapeHtml(confirmedByLabel.length > 28 ? confirmedByLabel.slice(0, 25) + '…' : confirmedByLabel)}</span>`
            : '';
        cell.innerHTML = `
            <div class="order-conf-stack">
                <span class="order-conf-date">${escapeHtml(confirmedAt)}</span>
                ${user}
            </div>`;
    } else {
        cell.innerHTML = '<span class="text-muted">—</span>';
    }
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
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

        const TL_CONFIG = {
            draft:     { label: 'Borrador',   icon: 'fa-pencil-alt', color: '#64748b' },
            pending:   { label: 'Pendiente',  icon: 'fa-clock',      color: '#f59e0b' },
            confirmed: { label: 'Confirmado', icon: 'fa-check',      color: '#3b82f6' },
            delivered: { label: 'Entregado',  icon: 'fa-truck',      color: '#22c55e' },
            cancelled: { label: 'Cancelado',  icon: 'fa-times',      color: '#ef4444' },
        };
        const timelineHtml = (order.timeline || []).map(t => {
            const cfg = TL_CONFIG[t.state] || { label: t.state, icon: 'fa-circle', color: '#94a3b8' };
            const reasonHtml = t.reason
                ? `<span class="tl-reason"><i class="fas fa-comment-alt"></i> ${t.reason}</span>`
                : '';
            return `
                <li class="tl-item">
                    <div class="tl-dot" style="background:${cfg.color};">
                        <i class="fas ${cfg.icon}"></i>
                    </div>
                    <div class="tl-body">
                        <span class="tl-state" style="color:${cfg.color};">${cfg.label}</span>
                        <span class="tl-meta">
                            <i class="fas fa-user-circle"></i> ${t.user_name}
                            &nbsp;·&nbsp;
                            <i class="fas fa-calendar-alt"></i> ${t.changed_at}
                        </span>
                        ${reasonHtml}
                    </div>
                </li>`;
        }).join('');

        const confirmAuditHtml = order.confirmed_at
            ? `
                <div class="detail-section order-confirm-audit">
                    <h4><i class="fas fa-user-check"></i> Confirmación con proveedor</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Fecha:</label><span>${order.confirmed_at}</span></div>
                        <div class="detail-item"><label>Registró:</label><span>${order.confirmed_by_label || '—'}</span></div>
                    </div>
                </div>`
            : '';

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
                ${confirmAuditHtml}
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
                ${timelineHtml ? `
                <div class="detail-section">
                    <h4><i class="fas fa-history"></i> Historial de estados</h4>
                    <ol class="order-timeline" style="margin-top:8px;">${timelineHtml}</ol>
                </div>` : ''}
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
function _orderAction(id, state, confirmCfg, successMsg) {
    Swal.fire({
        ...cf4SwalConfirmBase(confirmCfg.confirmClass),
        title: confirmCfg.title,
        html: confirmCfg.html,
        icon: confirmCfg.icon,
        confirmButtonText: confirmCfg.confirm,
    }).then(r => {
        if (!r.isConfirmed) return;

        const disableButtons = (disabled) => {
            document
                .querySelectorAll(
                    `tr[data-order-id="${id}"] .action-btn, #view-order-body [data-role="modal-actions"] .btn, .sales-actions[data-supplier-order-actions="${id}"] button`
                )
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
                    if (state === 'confirmed' || state === 'delivered') {
                        window.location.reload();
                    } else {
                        updateRowState(String(id), state);
                        if (activeOrderIdInModal === String(id)) {
                            updateModalState(state);
                        }
                        disableButtons(false);
                    }
                });
            } else {
                disableButtons(false);
                Swal.fire({
                    ...cf4SwalDialogDefaults(),
                    title: 'No se pudo completar',
                    text: data.message || 'No se pudo actualizar.',
                    icon: 'error',
                    showCancelButton: false,
                    confirmButtonText: 'Cerrar',
                    customClass: {
                        popup: 'cf4-swal-popup',
                        confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
                        title: 'cf4-swal-title',
                    },
                });
            }
        })
        .catch(() => {
            disableButtons(false);
            Swal.fire({
                ...cf4SwalDialogDefaults(),
                title: 'Error de conexión',
                text: 'Revisa tu red e inténtalo de nuevo.',
                icon: 'error',
                showCancelButton: false,
                confirmButtonText: 'Cerrar',
                customClass: {
                    popup: 'cf4-swal-popup',
                    confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
                    title: 'cf4-swal-title',
                },
            });
        });
    });
}

function confirmOrder(id) {
    const st = supplierOrderStateSnapshot(id);
    if (st !== null && st !== 'draft' && st !== 'pending') {
        return;
    }

    _orderAction(id, 'confirmed', {
        title: '¿Confirmar este pedido?',
        html: '<p>El pedido pasará a estado <strong>confirmado</strong> con el proveedor. Luego podrás marcarlo como <strong>entregado</strong> al recibir la mercancía.</p>',
        icon: 'question',
        confirm: 'Sí, confirmar',
        confirmClass: 'cf4-swal-btn cf4-swal-btn-primary',
    }, 'Pedido confirmado correctamente.');
}

function deliverOrder(id) {
    const st = supplierOrderStateSnapshot(id);
    if (st !== null && st !== 'confirmed') {
        return;
    }

    _orderAction(id, 'delivered', {
        title: '¿Marcar como entregado?',
        html: '<p>Se registrará la <strong>recepción de la mercancía</strong> y se actualizará el inventario según las líneas del pedido.</p>',
        icon: 'question',
        confirm: 'Sí, marcar entregado',
        confirmClass: 'cf4-swal-btn cf4-swal-btn-info',
    }, 'Pedido marcado como entregado.');
}

function cancelOrder(id) {
    Swal.fire({
        title: '¿Cancelar pedido?',
        html: `
            <p style="margin:0 0 12px; color:#4b5563;">El pedido se marcará como cancelado.</p>
            <textarea id="swal-cancel-reason"
                placeholder="Motivo de la cancelación…"
                style="width:100%; min-height:80px; resize:vertical; padding:8px 10px;
                       border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem;
                       font-family:inherit; outline:none; box-sizing:border-box;"
            ></textarea>
            <div id="swal-cancel-hint"
                 style="font-size:0.76rem; color:#9ca3af; margin-top:5px; text-align:left; transition:color .15s;">
                Escribe al menos 4 caracteres para continuar.
            </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const confirmBtn = Swal.getConfirmButton();
            const textarea   = document.getElementById('swal-cancel-reason');
            const hint       = document.getElementById('swal-cancel-hint');

            confirmBtn.disabled      = true;
            confirmBtn.style.opacity = '0.45';
            confirmBtn.style.cursor  = 'not-allowed';

            textarea.addEventListener('input', () => {
                const ok = textarea.value.trim().length >= 4;
                confirmBtn.disabled      = !ok;
                confirmBtn.style.opacity = ok ? '1' : '0.45';
                confirmBtn.style.cursor  = ok ? '' : 'not-allowed';
                hint.style.color         = ok ? '#22c55e' : '#9ca3af';
                hint.textContent         = ok ? '✓ Motivo válido.' : 'Escribe al menos 4 caracteres para continuar.';
            });
        },
        preConfirm: () => {
            const reason = document.getElementById('swal-cancel-reason').value.trim();
            if (reason.length < 4) {
                Swal.showValidationMessage('El motivo debe tener al menos 4 caracteres.');
                return false;
            }
            return reason;
        },
    }).then(r => {
        if (!r.isConfirmed) return;

        const reason = r.value;

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
            body: JSON.stringify({ state: 'cancelled', reason }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Pedido cancelado',
                    text:  data.message || 'El pedido fue cancelado correctamente.',
                    icon:  'success',
                    confirmButtonColor: '#2e7d32',
                    confirmButtonText: 'Entendido',
                }).then(() => {
                    updateRowState(String(id), 'cancelled');
                    if (activeOrderIdInModal === String(id)) {
                        updateModalState('cancelled');
                    }
                    disableButtons(false);
                });
            } else {
                disableButtons(false);
                Swal.fire({ title: 'Error', text: data.message || 'No se pudo cancelar.', icon: 'error' });
            }
        })
        .catch(() => {
            disableButtons(false);
            Swal.fire({ title: 'Error', text: 'Error de conexión.', icon: 'error' });
        });
    });
}

// Expose functions on window (required by Vite/ESM)
Object.assign(window, {
    closeViewOrderModal,
    closeViewSupplierModal,
    viewOrder,
    viewSupplier,
    confirmOrder,
    deliverOrder,
    cancelOrder,
});
