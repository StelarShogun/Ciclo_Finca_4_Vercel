// Retrieve CSRF token from meta tag
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/* ---- Modal helpers ---- */
function closeViewOrderModal()    { document.getElementById('view-order-modal')?.classList.remove('active'); }
function closeViewSupplierModal() { document.getElementById('view-supplier-modal')?.classList.remove('active'); }

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
        const stateLabels  = { pending: 'Pendiente', confirmed: 'Confirmado', delivered: 'Entregado', cancelled: 'Cancelado' };
        const supplierName = order.supplier?.name ?? '—';

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
                        <div class="detail-item"><label>Nº Pedido:</label><span><strong>#${order.num_order}</strong></span></div>
                        <div class="detail-item"><label>Proveedor:</label><span>${supplierName}</span></div>
                        <div class="detail-item"><label>Fecha:</label><span>${order.date}</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="status-badge ${order.state}">${stateLabels[order.state] || order.state}</span></div>
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
                }).then(() => location.reload());
            } else {
                Swal.fire({ title: 'Error', text: data.message || 'No se pudo actualizar.', icon: 'error' });
            }
        })
        .catch(() => Swal.fire({ title: 'Error', text: 'Error de conexión.', icon: 'error' }));
    });
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
    confirmOrder,
    deliverOrder,
    cancelOrder,
});
