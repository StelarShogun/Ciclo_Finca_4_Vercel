import '../../shared/ajax-pagination.js';

const ORDERS_HEARTBEAT_INTERVAL_MS = 30000;

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';

function formatPendingCount(value) {
    return new Intl.NumberFormat('es-CR').format(Math.max(0, Number(value) || 0));
}

function isOrdersAdminBusy() {
    if (window.cf4OrdersActionInProgress) {
        return true;
    }

    if (document.querySelector('.modal-overlay.active')) {
        return true;
    }

    const swal = window.Swal;

    if (swal?.isVisible?.() && (swal.isLoading?.() || document.querySelector('.swal2-container'))) {
        return true;
    }

    return false;
}

function updateOrdersPendingBadges(pendingCount) {
    const count = Math.max(0, Number(pendingCount) || 0);
    const formatted = formatPendingCount(count);

    const kpiEl = document.querySelector('[data-cf4-orders-pending-kpi]');

    if (kpiEl) {
        kpiEl.textContent = formatted;
    }

    const sidebarBadge = document.querySelector('[data-cf4-orders-pending-badge]');

    if (sidebarBadge) {
        if (count > 0) {
            sidebarBadge.textContent = count > 99 ? '99+' : String(count);
            sidebarBadge.hidden = false;
            sidebarBadge.setAttribute('aria-label', `${count} encargo(s) pendiente(s)`);
        } else {
            sidebarBadge.textContent = '';
            sidebarBadge.hidden = true;
            sidebarBadge.removeAttribute('aria-label');
        }
    }
}

function showOrdersNewBanner(newCount) {
    const banner = document.getElementById('cf4-orders-new-banner');
    const textEl = document.querySelector('[data-cf4-orders-new-banner-text]');

    if (!banner || !textEl) {
        return;
    }

    const count = Math.max(1, Number(newCount) || 1);
    const label = count === 1
        ? 'Hay 1 nuevo encargo'
        : `Hay ${formatPendingCount(count)} nuevos encargos`;

    textEl.textContent = `${label}. Los filtros actuales se mantienen al actualizar.`;
    banner.hidden = false;
}

function hideOrdersNewBanner() {
    const banner = document.getElementById('cf4-orders-new-banner');

    if (banner) {
        banner.hidden = true;
    }
}

let ordersTableRefreshInFlight = false;

function flashOrdersTableRegion() {
    const region = document.querySelector('[data-cf4-orders-table-region]');

    if (!region) {
        return;
    }

    region.classList.remove('cf4-orders-table-region--updated');
    window.requestAnimationFrame(() => {
        region.classList.add('cf4-orders-table-region--updated');
        window.setTimeout(() => region.classList.remove('cf4-orders-table-region--updated'), 1200);
    });
}

async function refreshOrdersTable() {
    if (ordersTableRefreshInFlight) {
        return false;
    }

    const region = document.querySelector('[data-cf4-orders-table-region]');

    if (!region) {
        return false;
    }

    ordersTableRefreshInFlight = true;

    try {
        const url = `${window.location.pathname}${window.location.search}`;
        const res = await fetch(url, {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            return false;
        }

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const incoming = doc.querySelector('[data-cf4-orders-table-region]');

        if (!incoming) {
            return false;
        }

        region.replaceWith(document.importNode(incoming, true));
        flashOrdersTableRegion();

        return true;
    } catch {
        return false;
    } finally {
        ordersTableRefreshInFlight = false;
    }
}

function initOrdersHeartbeat() {
    const root = document.querySelector('[data-cf4-orders-heartbeat]');
    const latestEl = document.getElementById('cf4-latest-purchase-sale-id');
    const heartbeatUrl = metaContent('sales-route-heartbeat');

    if (!root || !latestEl || !heartbeatUrl) {
        return;
    }

    let latestPurchaseSaleId = parseInt(latestEl.dataset.value, 10) || 0;
    let bannerDismissed = false;
    let pendingTableRefresh = false;

    const kpiEl = document.querySelector('[data-cf4-orders-pending-kpi]');

    if (kpiEl) {
        const initialPending = Number(kpiEl.textContent.replace(/[^\d]/g, '')) || 0;
        updateOrdersPendingBadges(initialPending);
    }

    const refreshBtn = document.querySelector('[data-cf4-orders-refresh-btn]');
    const dismissBtn = document.querySelector('[data-cf4-orders-dismiss-banner]');

    refreshBtn?.addEventListener('click', async () => {
        hideOrdersNewBanner();
        bannerDismissed = false;
        pendingTableRefresh = false;

        if (!(await refreshOrdersTable())) {
            window.location.reload();
        }
    });

    dismissBtn?.addEventListener('click', () => {
        bannerDismissed = true;
        hideOrdersNewBanner();
    });

    async function applyNewOrders(newCount) {
        if (isOrdersAdminBusy()) {
            pendingTableRefresh = true;

            if (!bannerDismissed) {
                showOrdersNewBanner(newCount);
            }

            return false;
        }

        pendingTableRefresh = false;
        hideOrdersNewBanner();
        bannerDismissed = false;

        if (await refreshOrdersTable()) {
            return true;
        }

        if (!bannerDismissed) {
            showOrdersNewBanner(newCount);
        }

        return false;
    }

    async function heartbeatCheck() {
        if (document.visibilityState === 'hidden') {
            return;
        }

        try {
            const res = await fetch(`${heartbeatUrl}?since=${latestPurchaseSaleId}`, {
                headers: { Accept: 'application/json' },
            });

            if (!res.ok) {
                return;
            }

            const data = await res.json();

            if (typeof data.pendingCount !== 'undefined') {
                updateOrdersPendingBadges(data.pendingCount);
            }

            if (data.hasNew) {
                const applied = await applyNewOrders(data.newCount);

                if (applied && typeof data.latestSaleId !== 'undefined') {
                    latestPurchaseSaleId = data.latestSaleId;
                }
            } else {
                if (typeof data.latestSaleId !== 'undefined') {
                    latestPurchaseSaleId = data.latestSaleId;
                }

                if (pendingTableRefresh && !isOrdersAdminBusy()) {
                    pendingTableRefresh = false;
                    hideOrdersNewBanner();

                    if (await refreshOrdersTable() && typeof data.latestSaleId !== 'undefined') {
                        latestPurchaseSaleId = data.latestSaleId;
                    }
                }
            }
        } catch {
            /* fail silently */
        }
    }

    heartbeatCheck();
    window.setInterval(heartbeatCheck, ORDERS_HEARTBEAT_INTERVAL_MS);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            heartbeatCheck();
        }
    });
}

function initOrderExpirationModal() {
    const modal = document.getElementById('order-expiration-modal');
    const openBtn = document.getElementById('btn-open-order-expiration-modal');
    const form = document.getElementById('order-expiration-form');
    const metaUrl = document.querySelector('meta[name="order-expiration-update-url"]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!modal || !openBtn || !form || !metaUrl) {
        return;
    }

    const url = metaUrl.getAttribute('content');

    const close = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    };

    openBtn.addEventListener('click', () => {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    });

    modal.querySelectorAll('[data-close-order-expiration-modal]').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const errEl = document.getElementById('order-expiration-form-error');
        const input = document.getElementById('ready_to_pickup_expiration_hours');
        const submitBtn = document.getElementById('order-expiration-submit');

        errEl.style.display = 'none';
        errEl.textContent = '';
        submitBtn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    ready_to_pickup_expiration_hours: input.value === '' ? null : Number(input.value),
                }),
            });

            const data = await parseResponsePayload(res);

            if (res.status === 422 && data.errors) {
                const first = Object.values(data.errors).flat()[0] ?? 'Datos no válidos.';

                errEl.textContent = first;
                errEl.style.display = 'block';

                return;
            }

            if (!res.ok) {
                errEl.textContent = data.message ?? 'No se pudo guardar.';
                errEl.style.display = 'block';

                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: data.message ?? 'Guardado',
                    timer: 2000,
                    showConfirmButton: false,
                });
            }

            close();

            if (data.ready_to_pickup_expiration_hours != null) {
                input.value = String(data.ready_to_pickup_expiration_hours);
            }
        } catch {
            errEl.textContent = 'No se pudo conectar con el servidor.';
            errEl.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initOrderExpirationModal();
    initOrdersHeartbeat();
});

async function parseResponsePayload(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json().catch(() => ({}));
    }

    const text = await response.text().catch(() => '');

    return {
        message: text || null,
    };
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function markReadyToPickup(saleId, label) {
    const swal = window.Swal;

    if (!swal) {
        const ok = window.confirm(`¿Marcar como listo para recoger?\n\nEl pedido ${label} pasará a estado "Listo para recoger".`);

        if (!ok) {
            return;
        }

        doOrderAction(`/orders/${saleId}/ready-to-pickup`, {
            method: 'PATCH',
            successTitle: () => 'Actualizado',
        });

        return;
    }

    swal.fire({
        title: '¿Marcar como listo para recoger?',
        text: `El pedido ${label} pasará a estado "Listo para recoger". El stock ya fue reservado al crear el pedido.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, marcar',
        cancelButtonText: 'Cancelar',
    }).then(async (result) => {
        if (!result.isConfirmed) {
            return;
        }

        await doOrderAction(`/orders/${saleId}/ready-to-pickup`, {
            method: 'PATCH',
            successTitle: () => 'Actualizado',
        });
    });
}

async function doOrderAction(url, {
    method = 'POST',
    payload = null,
    successTitle = () => 'Listo',
} = {}) {
    const swal = window.Swal;
    const csrf = getCsrfToken();
    const controller = new AbortController();

    window.cf4OrdersActionInProgress = true;

    const timeoutId = window.setTimeout(() => {
        controller.abort();
    }, 15000);

    if (swal) {
        swal.fire({
            title: 'Procesando…',
            text: 'Espere mientras se completa la acción.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => swal.showLoading(),
        });
    }

    try {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload ? JSON.stringify(payload) : null,
            signal: controller.signal,
        });

        window.clearTimeout(timeoutId);

        const data = await parseResponsePayload(res);

        if (res.ok && (data.success === true || typeof data.success === 'undefined')) {
            let text = data.message ?? '';

            if (data.sale?.invoice_number) {
                text = text
                    ? `${text}\n\nFactura: ${data.sale.invoice_number}`
                    : `Factura: ${data.sale.invoice_number}`;
            }

            if (swal) {
                await swal.fire({
                    icon: 'success',
                    title: successTitle(data),
                    text: text || undefined,
                    confirmButtonText: 'Entendido',
                });
            } else {
                window.alert(text || 'Acción realizada correctamente.');
            }

            window.cf4OrdersActionInProgress = false;
            window.location.reload();

            return;
        }

        window.cf4OrdersActionInProgress = false;

        const message = data.message || `No se pudo completar la acción. Código: ${res.status}`;

        if (swal) {
            await swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'Cerrar',
            });
        } else {
            window.alert(message);
        }
    } catch (error) {
        window.clearTimeout(timeoutId);
        window.cf4OrdersActionInProgress = false;

        const isTimeout = error.name === 'AbortError';
        const title = isTimeout ? 'Tiempo de espera agotado' : 'Error de red';
        const message = isTimeout
            ? 'El servidor tardó demasiado en responder. Revise el controlador, la ruta o la lógica del backend.'
            : 'No se pudo conectar con el servidor.';

        if (swal) {
            await swal.fire({
                icon: 'error',
                title,
                text: message,
                confirmButtonText: 'Cerrar',
            });
        } else {
            window.alert(`${title}: ${message}`);
        }
    }
}

function orderAction(url, successMsg, payload = null) {
    return doOrderAction(url, {
        payload,
        successTitle: () => successMsg,
    });
}

function completeSale(id, invoiceNumber) {
    const swal = window.Swal;
    const invoiceLabel = invoiceNumber || `#${id}`;

    if (!swal) {
        const ok = window.confirm(`¿Confirmar encargo con factura ${invoiceLabel}?`);

        if (!ok) {
            return;
        }

        doOrderAction(`/sales/${id}/complete`, {
            successTitle: () => 'Encargo confirmado',
        });

        return;
    }

    swal.fire({
        title: `¿Confirmar encargo con factura ${invoiceLabel}?`,
        text: 'El pedido pasará a confirmado. No se volverá a descontar stock porque ya fue reservado en el checkout.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        doOrderAction(`/sales/${id}/complete`, {
            successTitle: () => 'Encargo confirmado',
        });
    });
}

function cancelSale(id, invoiceNumber) {
    const swal = window.Swal;
    const invoiceLabel = invoiceNumber || `#${id}`;

    if (!swal) {
        const reason = window.prompt(`Ingrese el motivo de cancelación para el encargo ${invoiceLabel}.`);

        if (!reason || reason.trim().length < 3) {
            window.alert('Debe ingresar un motivo de al menos 3 caracteres.');
            return;
        }

        doOrderAction(`/sales/${id}/cancel`, {
            payload: {
                reason: reason.trim(),
            },
            successTitle: () => 'Encargo rechazado',
        });

        return;
    }

    swal.fire({
        title: `¿Rechazar encargo ${invoiceLabel}?`,
        text: 'Ingrese el motivo de cancelación. El stock reservado se devolverá al inventario.',
        input: 'textarea',
        inputPlaceholder: 'Motivo de cancelación',
        inputAttributes: { maxlength: 500 },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value || value.trim().length < 3) {
                return 'Debe ingresar un motivo de al menos 3 caracteres.';
            }

            return null;
        },
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        doOrderAction(`/sales/${id}/cancel`, {
            payload: {
                reason: result.value.trim(),
            },
            successTitle: () => 'Encargo rechazado',
        });
    });
}

function closeViewSaleModal() {
    document.getElementById('view-sale-modal')?.classList.remove('active');
}

function viewSale(id) {
    const modal = document.getElementById('view-sale-modal');
    const body = document.getElementById('view-sale-body');
    if (!modal || !body) return;

    body.innerHTML = `
        <div class="loading-spinner" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
            <p>Cargando detalles…</p>
        </div>`;
    modal.classList.add('active');

    fetch('/sales/' + id, {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
        },
    })
        .then((r) => r.json())
        .then((data) => {
            if (!data.success || !data.sale) {
                body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles.</div>';
                return;
            }

            const sale = data.sale;
            const items = sale.sale_items || sale.saleItems || [];
            const isWebOrder = sale.order_source === 'web_cart' || sale.order_source == null;
            const saleDateLabel = isWebOrder ? 'Fecha de pedido' : 'Fecha de venta';
            const saleDateValue = isWebOrder
                ? sale.order_placed_at_label || sale.sale_date_label || '—'
                : sale.sale_date_label || '—';

            const readyAtRow = sale.ready_at || sale.ready_at_label
                ? '<div class="detail-item"><label>Fecha listo para recoger:</label><span>'
                    + (sale.ready_at_label || '—')
                    + '</span></div>'
                : '';

            const confirmedAtRow = sale.status === 'completed'
                ? '<div class="detail-item"><label>Fecha de confirmación:</label><span>'
                    + (sale.confirmed_at_label || '—')
                    + '</span></div>'
                : '';

            const statusLabels = {
                pending: 'Pendiente',
                ready_to_pickup: 'Por recoger',
                completed: 'Confirmado',
                cancelled: 'Rechazado',
                refunded: 'Reembolsado (histórico)',
                returned: 'Devuelta',
            };
            const paymentLabels = {
                cash: 'Efectivo',
                sinpe: 'SINPE movil',
                transfer: 'Transferencia',
            };

            let customerName = 'Mostrador / sin datos';
            if (sale.client) {
                customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname]
                    .filter(Boolean)
                    .join(' ');
                if (sale.client.gmail) customerName += ' (' + sale.client.gmail + ')';
            } else if (sale.buyer?.name) {
                customerName = sale.buyer.name;
                if (sale.buyer.email) customerName += ' (' + sale.buyer.email + ')';
            }

            const productsHtml = items
                .map((item) => {
                    const prod = item.product || {};
                    const up = parseFloat(item.unit_price || 0);
                    const tot = parseFloat(item.total || 0);
                    return '<tr>'
                        + '<td>' + (prod.name || 'N/A') + '</td>'
                        + '<td class="text-center">' + item.quantity + '</td>'
                        + '<td class="text-right">CRC' + up.toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</td>'
                        + '<td class="text-right"><strong>CRC' + tot.toLocaleString('es-CR', { minimumFractionDigits: 2 }) + '</strong></td>'
                        + '</tr>';
                })
                .join('');

            body.innerHTML = `
                <div class="view-sale-detail">
                    <div class="detail-grid">
                        <div class="detail-item"><label>${saleDateLabel}:</label><span>${saleDateValue}</span></div>
                        ${readyAtRow}
                        ${confirmedAtRow}
                        <div class="detail-item"><label>Cliente:</label><span>${customerName}</span></div>
                        <div class="detail-item"><label>Estado:</label><span class="order-status-pill ${sale.status}">${statusLabels[sale.status] || sale.status}</span></div>
                        <div class="detail-item"><label>Método de pago:</label><span>${paymentLabels[sale.payment_method] || sale.payment_method || '—'}</span></div>
                    </div>

                    <div class="detail-section">
                        <h4>Productos</h4>
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-right">Precio unitario</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>${productsHtml}</tbody>
                        </table>
                    </div>
                </div>
            `;
        })
        .catch(() => {
            body.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> No se pudieron cargar los detalles.</div>';
        });
}

Object.assign(window, {
    markReadyToPickup,
    completeSale,
    cancelSale,
    orderAction,
    viewSale,
    closeViewSaleModal,
});
