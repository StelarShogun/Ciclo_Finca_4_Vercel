// @ts-nocheck
import '../../shared/ajax-pagination';
import {
    cf4Confirm,
    cf4Toast,
    cf4Error,
    cf4Warning,
    cf4DialogDefaults,
    cf4SwalClasses,
    fireSwal,
    getSwal,
} from '../shared/swal';

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
        dateFrom.addEventListener('change', () => {
            dateTo.min = dateFrom.value || '';
            if (dateTo.value && dateTo.value < dateFrom.value) {
                dateTo.value = dateFrom.value;
            }
        });

        if (dateFrom.value) dateTo.min = dateFrom.value;
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            const from = dateFrom?.value;
            const to   = dateTo?.value;
            if (from && to && to < from) {
                e.preventDefault();
                void cf4Warning(
                    'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
                    'Rango de fechas inválido'
                );
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

const STATE_LABELS = {
    draft:            'Borrador',
    pending:          'Pendiente',
    confirmed:        'Confirmado',
    partial_received: 'Recepción parcial',
    delivered:        'Entregado',
    cancelled:        'Cancelado',
};

let activeOrderIdInModal = null;

/** Estado mostrado en listado (`tr`) o en página de detalle (`data-supplier-order-state`). */
function supplierOrderStateSnapshot(id) {
    const sid  = String(id);
    const tr   = document.querySelector(`tr[data-order-id="${sid}"]`);
    if (tr) return tr.getAttribute('data-order-state');

    const root = document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${sid}"]`);
    if (root) return root.getAttribute('data-supplier-order-state');

    return null;
}

function setSupplierOrderStateSnapshot(id, state) {
    const sid  = String(id);
    const tr   = document.querySelector(`tr[data-order-id="${sid}"]`);
    if (tr) tr.setAttribute('data-order-state', state);

    const root = document.querySelector(`.cf4-supplier-orders-module[data-supplier-order-num="${sid}"]`);
    if (root) root.setAttribute('data-supplier-order-state', state);
}

/** Nº de pedido si la URL es `/supplier-orders/{id}/detail`, si no `null`. */
function supplierOrderDetailPageOrderId() {
    const m = window.location.pathname.match(/^\/supplier-orders\/(\d+)\/detail$/);
    return m ? m[1] : null;
}

/**
 * Renderiza los botones de acción para una fila del listado o el modal de vista rápida.
 *
 * El botón "Cerrar con faltantes" solo aparece en la variante 'text' (modal / detalle)
 * porque en el listado no hay espacio para el flujo completo con textarea de motivo;
 * desde el listado se redirige a la página de detalle.
 */
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

    // draft → confirmed; no existe paso intermedio "pending" para pedidos nuevos.
    if (state === 'draft') {
        return `${viewBtn}${btn(variant === 'icon' ? 'success' : 'btn-primary', 'Confirmar pedido', 'fa-check', 'Confirmar', 'confirmOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }

    // Compatibilidad con pedidos históricos en estado pending.
    if (state === 'pending') {
        return `${viewBtn}${btn(variant === 'icon' ? 'success' : 'btn-primary', 'Confirmar pedido', 'fa-check', 'Confirmar', 'confirmOrder')}${btn(variant === 'icon' ? 'danger' : 'btn-secondary', 'Cancelar', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }

    // confirmed: el botón de recepción redirige a la página de detalle donde está el modal.
    if (state === 'confirmed') {
        const detailUrl = `/supplier-orders/${id}/detail`;
        if (variant === 'text') {
            return `${viewBtn}
                <a class="btn btn-primary" href="${detailUrl}" title="Registrar recepción de mercancía">
                    <i class="fas fa-clipboard-check"></i> Registrar recepción
                </a>
                ${btn('btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
        }
        return `${viewBtn}<a class="action-btn view" href="${detailUrl}" title="Registrar recepción"><i class="fas fa-clipboard-check"></i></a>${btn('danger', 'Cancelar', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }

    // partial_received: completar recepción o cerrar con faltantes (ambas desde la página de detalle).
    if (state === 'partial_received') {
        const detailUrl = `/supplier-orders/${id}/detail`;
        if (variant === 'text') {
            // En variante texto (modal de vista rápida) se ofrece ir a detalle para ambas acciones,
            // ya que "Cerrar con faltantes" requiere el textarea de motivo que solo existe en el detalle.
            return `${viewBtn}
                <a class="btn btn-primary" href="${detailUrl}" title="Completar recepción de mercancía">
                    <i class="fas fa-clipboard-check"></i> Completar recepción
                </a>
                <a class="btn btn-warning" href="${detailUrl}" title="Cerrar pedido con faltantes del proveedor">
                    <i class="fas fa-exclamation-triangle"></i> Cerrar con faltantes
                </a>
                ${btn('btn-secondary', 'Cancelar pedido', 'fa-times', 'Cancelar', 'cancelOrder')}`;
        }
        return `${viewBtn}<a class="action-btn view" href="${detailUrl}" title="Completar recepción / cerrar con faltantes"><i class="fas fa-clipboard-check"></i></a>${btn('danger', 'Cancelar', 'fa-times', 'Cancelar', 'cancelOrder')}`;
    }

    return `${viewBtn}`;
}

function updateRowState(id, nextState) {
    setSupplierOrderStateSnapshot(id, nextState);

    const tr = document.querySelector(`tr[data-order-id="${id}"]`);
    if (!tr) return;

    const pill = tr.querySelector('[data-role="order-state-pill"]');
    if (pill) {
        pill.className   = `order-status-pill ${nextState}`;
        pill.textContent = STATE_LABELS[nextState] || nextState;
    }

    const actions = tr.querySelector('[data-role="order-actions"]');
    if (actions) {
        actions.innerHTML = renderActionButtonsHtml(id, nextState, 'icon');
    }
}

/** Actualiza el badge de estado dentro del modal de vista rápida. */
function updateModalState(nextState) {
    if (!activeOrderIdInModal) return;
    const badge = document.querySelector('#view-order-body [data-role="modal-state-badge"]');
    if (badge) {
        badge.className   = `status-badge ${nextState}`;
        badge.textContent = STATE_LABELS[nextState] || nextState;
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
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
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
        const detailUrl    = `/supplier-orders/${order.num_order}/detail`;

        const productsHtml = (order.products || []).map(item => {
            const up      = parseFloat(item.unit_price || 0);
            const tot     = parseFloat(item.total || 0);
            const recvQty = (item.received_quantity !== null && item.received_quantity !== undefined)
                ? parseInt(item.received_quantity, 10)
                : 0;
            const recvCol = (item.received_quantity !== null && item.received_quantity !== undefined)
                ? `<td class="text-center">${recvQty}</td>`
                : '';
            return `
                <tr>
                    <td>${escapeHtml(item.name || 'N/A')}</td>
                    <td class="text-center">${item.quantity}</td>
                    ${recvCol}
                    <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                    <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
                </tr>`;
        }).join('');

        const showRecvCol = order.products?.some(p => p.received_quantity !== null && p.received_quantity !== undefined);
        const recvHeader  = showRecvCol ? '<th class="text-center">Recibido</th>' : '';

        const TL_CONFIG = {
            draft:            { label: 'Borrador',          icon: 'fa-pencil-alt',      color: '#64748b' },
            pending:          { label: 'Pendiente',         icon: 'fa-clock',            color: '#f59e0b' },
            confirmed:        { label: 'Confirmado',        icon: 'fa-check',            color: '#3b82f6' },
            partial_received: { label: 'Recepción parcial', icon: 'fa-clipboard-check',  color: '#f97316' },
            delivered:        { label: 'Entregado',         icon: 'fa-truck',            color: '#235347' },
            cancelled:        { label: 'Cancelado',         icon: 'fa-times',            color: '#ef4444' },
        };

        const timelineHtml = (order.timeline || []).map(t => {
            const isClosePartial = t.state === 'delivered' && (t.reason || '').startsWith('[Cierre con faltantes]');
            const cfg            = isClosePartial
                ? { label: 'Cerrado con faltantes', icon: 'fa-exclamation-triangle', color: '#f59e0b' }
                : (TL_CONFIG[t.state] || { label: t.state, icon: 'fa-circle', color: '#94a3b8' });
            const displayReason  = isClosePartial
                ? t.reason.replace(/^\[Cierre con faltantes\]\s*/, '')
                : t.reason;
            const reasonHtml = displayReason
                ? `<span class="tl-reason"><i class="fas fa-comment-alt"></i> ${escapeHtml(displayReason)}</span>`
                : '';
            return `
                <li class="tl-item">
                    <div class="tl-dot" style="background:${cfg.color};">
                        <i class="fas ${cfg.icon}"></i>
                    </div>
                    <div class="tl-body">
                        <span class="tl-state" style="color:${cfg.color};">${cfg.label}</span>
                        <span class="tl-meta">
                            <i class="fas fa-user-circle"></i> ${escapeHtml(t.user_name)}
                            &nbsp;·&nbsp;
                            <i class="fas fa-calendar-alt"></i> ${t.changed_at}
                        </span>
                        ${reasonHtml}
                    </div>
                </li>`;
        }).join('');

        const firstConfirmed = (order.timeline || []).find(t => t.state === 'confirmed');
        const confirmAuditHtml = firstConfirmed
            ? `
                <div class="detail-section order-confirm-audit">
                    <h4><i class="fas fa-user-check"></i> Confirmación con proveedor</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Fecha:</label><span>${escapeHtml(firstConfirmed.changed_at)}</span></div>
                        <div class="detail-item"><label>Registró:</label><span>${escapeHtml(firstConfirmed.user_name || '—')}</span></div>
                    </div>
                </div>`
            : '';

        const initialTotalFromLines = (order.products || []).reduce((acc, p) => acc + parseFloat(p.total || 0), 0);
        const initialTotal = initialTotalFromLines > 0
            ? initialTotalFromLines
            : parseFloat(order.total || 0);

        const receivedTotal = showRecvCol
            ? (order.products || []).reduce((acc, p) => {
                const unit = parseFloat(p.unit_price || 0);
                const qty  = parseInt(p.received_quantity ?? 0, 10) || 0;
                return acc + Math.round((unit * qty + Number.EPSILON) * 100) / 100;
            }, 0)
            : null;

        const shortsTotal = (showRecvCol && receivedTotal !== null)
            ? Math.max(initialTotal - receivedTotal, 0)
            : 0;

        const closedWithShortsBadge = order.closed_with_shorts
            ? `<div class="detail-item" style="color:#b45309;">
                   <label>Observación:</label>
                   <span><i class="fas fa-exclamation-triangle"></i> Cerrado con faltantes del proveedor</span>
               </div>`
            : '';

        body.innerHTML = `
            <div class="sale-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Información general</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>Nº Pedido:</label><span><strong>${escapeHtml(order.po_number || ('#' + order.num_order))}</strong></span></div>
                        <div class="detail-item"><label>Proveedor:</label><span>${escapeHtml(supplierName)}</span></div>
                        <div class="detail-item"><label>Fecha:</label><span>${order.date}</span></div>
                        <div class="detail-item"><label>Entrega estimada:</label><span>${order.estimated_delivery_date || '—'}</span></div>
                        ${order.received_at ? `<div class="detail-item"><label>Fecha recepción:</label><span>${order.received_at}</span></div>` : ''}
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${order.state}" data-role="modal-state-badge">${STATE_LABELS[order.state] || order.state}</span></div>
                        ${closedWithShortsBadge}
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
                    <table class="sale-products-table admin-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Pedido</th>
                                ${recvHeader}
                                <th class="text-right">Precio unit.</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${productsHtml}</tbody>
                    </table>
                    <div class="sale-totals">
                        ${(showRecvCol && shortsTotal > 0.009) ? `
                            <div class="total-item">
                                <span><strong>Total pedido:</strong></span>
                                <span><strong>₡${initialTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span>
                            </div>
                            <div class="total-item">
                                <span><strong>Total recibido:</strong></span>
                                <span><strong>₡${receivedTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span>
                            </div>
                            <div class="total-item total-final">
                                <span><strong>Faltante:</strong></span>
                                <span><strong>₡${shortsTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span>
                            </div>
                        ` : `
                            <div class="total-item total-final">
                                <span><strong>Total:</strong></span>
                                <span><strong>₡${initialTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></span>
                            </div>
                        `}
                    </div>
                </div>` : ''}
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
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
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
                        <div class="detail-item"><label>Nombre:</label><span><strong>${escapeHtml(s.name)}</strong></span></div>
                        <div class="detail-item"><label>Contacto:</label><span>${escapeHtml(s.primary_contact || '—')}</span></div>
                        <div class="detail-item"><label>Teléfono:</label><span>${escapeHtml(s.phone || '—')}</span></div>
                        <div class="detail-item"><label>Correo:</label><span>${escapeHtml(s.email || '—')}</span></div>
                        <div class="detail-item"><label>Dirección:</label><span>${escapeHtml(s.address || '—')}</span></div>
                        <div class="detail-item"><label>Tiempo de entrega:</label><span>${s.delivery_time} día(s)</span></div>
                        <div class="detail-item"><label>Evaluación:</label><span title="${s.rating}/5">${stars} (${s.rating})</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${s.status}">${escapeHtml(statusLabel[s.status] || s.status)}</span></div>
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
async function _orderAction(id, state, confirmCfg, successMsg) {
    const result = await cf4Confirm({
        title: confirmCfg.title,
        html: confirmCfg.html,
        icon: confirmCfg.icon,
        confirmButtonText: confirmCfg.confirm,
        cancelButtonText: 'Volver',
        danger: confirmCfg.danger ?? false,
        confirmStyle: confirmCfg.confirmStyle ?? 'primary',
    });

    if (!result.isConfirmed) return;

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

    try {
        const res = await fetch(`/supplier-orders/${id}/state`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ state }),
        });
        const data = await res.json();

        if (data.success) {
            await cf4Toast({
                icon: 'success',
                title: 'Listo',
                text: data.message || successMsg,
                timer: 3600,
            });

            if (state === 'confirmed' || state === 'delivered') {
                window.location.reload();
            } else {
                updateRowState(String(id), state);
                if (activeOrderIdInModal === String(id)) {
                    updateModalState(state);
                }
                disableButtons(false);
            }
        } else {
            disableButtons(false);
            await cf4Error(data.message || 'No se pudo actualizar.', 'No se pudo completar');
        }
    } catch {
        disableButtons(false);
        await cf4Error('Revisa tu red e inténtalo de nuevo.', 'Error de conexión');
    }
}

function confirmOrder(id) {
    const st = supplierOrderStateSnapshot(id);
    if (st !== null && st !== 'draft' && st !== 'pending') return;

    _orderAction(id, 'confirmed', {
        title:   '¿Confirmar este pedido?',
        html:    '<p>El pedido pasará a estado <strong>confirmado</strong> con el proveedor. Luego podrás registrar la <strong>recepción de mercancía</strong> al recibirla.</p>',
        icon:    'question',
        confirm: 'Sí, confirmar',
    }, 'Pedido confirmado correctamente.');
}

function deliverOrder(id) {
    const st = supplierOrderStateSnapshot(id);
    // deliverOrder solo aplica desde confirmed (flujo directo/legacy).
    // Desde partial_received se usa closePartialOrder().
    if (st !== null && st !== 'confirmed') return;

    _orderAction(id, 'delivered', {
        title:   '¿Marcar como entregado?',
        html:    '<p>Se registrará la <strong>recepción de la mercancía</strong> y se actualizará el inventario según las líneas del pedido.</p>',
        icon:    'question',
        confirm: 'Sí, marcar entregado',
    }, 'Pedido marcado como entregado.');
}

async function cancelOrder(id) {
    const Swal = await getSwal();

    const result = await fireSwal({
        ...cf4DialogDefaults(),
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
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Cancelar',
        customClass: {
            ...cf4SwalClasses,
            confirmButton: 'cf4-swal-btn cf4-swal-btn-danger',
        },
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
                hint.style.color         = ok ? '#235347' : '#9ca3af';
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
    });

    if (!result.isConfirmed) return;

    const reason = result.value;

    const disableButtons = (disabled) => {
        document
            .querySelectorAll(`tr[data-order-id="${id}"] .action-btn, #view-order-body [data-role="modal-actions"] .btn`)
            .forEach((el) => {
                if (el instanceof HTMLButtonElement) el.disabled = disabled;
            });
    };

    disableButtons(true);

    try {
        const res = await fetch(`/supplier-orders/${id}/state`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ state: 'cancelled', reason }),
        });
        const data = await res.json();

        if (data.success) {
            await cf4Toast({
                icon: 'success',
                title: 'Pedido cancelado',
                text: data.message || 'El pedido fue cancelado correctamente.',
                timer: 3000,
            });
            updateRowState(String(id), 'cancelled');
            if (activeOrderIdInModal === String(id)) {
                updateModalState('cancelled');
            }
            disableButtons(false);
        } else {
            disableButtons(false);
            await cf4Error(data.message || 'No se pudo cancelar.', 'Error');
        }
    } catch {
        disableButtons(false);
        await cf4Error('Error de conexión.', 'Error');
    }
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