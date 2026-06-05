// @ts-nocheck
import '../../shared/ajax-pagination';
import {
    cf4Confirm,
    cf4PromptTextarea,
    cf4Toast,
    cf4Error,
    cf4Loading,
    cf4Close,
    escapeHtml,
} from '../shared/swal';

const ORDERS_HEARTBEAT_INTERVAL_MS = 30000;

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';

function formatPendingCount(value) {
    return new Intl.NumberFormat('es-CR').format(Math.max(0, Number(value) || 0));
}

function isOrdersAdminBusy() {
    if (window.cf4OrdersActionInProgress) {
        return true;
    }

    if (document.querySelector('.modal-overlay.active, .edit-modal.active')) {
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
        document.dispatchEvent(new CustomEvent('cf4:ajax-pagination:loaded'));
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

function initOrdersDateRangeFilter() {
    const ordersDateRange = document.getElementById('orders-date-range');
    const ordersDateFromGroup = document.getElementById('orders-custom-date-from-group');
    const ordersDateToGroup = document.getElementById('orders-custom-date-to-group');

    if (!ordersDateRange || !ordersDateFromGroup || !ordersDateToGroup) {
        return;
    }

    const toggleOrdersCustomDates = () => {
        const isCustom = ordersDateRange.value === 'custom';
        ordersDateFromGroup.style.display = isCustom ? '' : 'none';
        ordersDateToGroup.style.display = isCustom ? '' : 'none';
    };

    ordersDateRange.addEventListener('change', toggleOrdersCustomDates);
    toggleOrdersCustomDates();
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

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            close();
        }
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

            await cf4Toast({
                icon: 'success',
                title: data.message ?? 'Guardado',
                timer: 2000,
            });

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
    initOrdersDateRangeFilter();
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

async function markReadyToPickup(saleId, label) {
    // Prevent double-submission: disable every "marcar" button for this sale
    // so a second click while the request is in-flight does nothing.
    const triggerBtn = document.querySelector(
        `button[onclick*="markReadyToPickup"][onclick*="${saleId}"]`,
    );
    if (triggerBtn) triggerBtn.disabled = true;

    try {
        const result = await cf4Confirm({
            title: '¿Marcar como listo para recoger?',
            text: `El pedido ${label} pasará a estado "Listo para recoger". El stock ya fue reservado al crear el pedido.`,
            icon: 'question',
            confirmButtonText: 'Sí, marcar',
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return;

        await doOrderAction(`/orders/${saleId}/ready-to-pickup`, {
            method: 'PATCH',
            successTitle: () => 'Actualizado',
        });
    } finally {
        // Re-enable in case the user cancelled (page reloads on success/error anyway)
        if (triggerBtn) triggerBtn.disabled = false;
    }
}

async function doOrderAction(url, {
    method = 'POST',
    payload = null,
    successTitle = () => 'Listo',
} = {}) {
    const csrf = getCsrfToken();
    const controller = new AbortController();

    window.cf4OrdersActionInProgress = true;

    // 50 s — generous for cloud deployments (Render free tier + cold DB + notification)
    const timeoutId = window.setTimeout(() => {
        controller.abort();
    }, 50000);

    void cf4Loading('Procesando…', 'Espere mientras se completa la acción.');

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

        await cf4Close();

        if (res.ok && (data.success === true || typeof data.success === 'undefined')) {
            let text = data.message ?? '';

            if (data.sale?.invoice_number) {
                text = text
                    ? `${text}\n\nFactura: ${data.sale.invoice_number}`
                    : `Factura: ${data.sale.invoice_number}`;
            }

            await cf4Toast({
                icon: 'success',
                title: successTitle(data),
                text: text || '',
                timer: 3000,
            });

            window.cf4OrdersActionInProgress = false;
            window.location.reload();

            return;
        }

        window.cf4OrdersActionInProgress = false;

        const message = data.message || `No se pudo completar la acción. Código: ${res.status}`;

        // After any server-side error the table may be stale (e.g. another request
        // already changed the status). Reload so the UI matches the DB.
        await cf4Error(message, 'Error');
        window.location.reload();
    } catch (error) {
        window.clearTimeout(timeoutId);
        window.cf4OrdersActionInProgress = false;
        await cf4Close();

        const isTimeout = error.name === 'AbortError';
        const title = isTimeout ? 'La acción tardó más de lo esperado' : 'Error de red';
        const message = isTimeout
            ? 'El servidor pudo haber procesado la acción. Recargando la página para verificar…'
            : 'No se pudo conectar con el servidor. Verificá tu conexión e intentá de nuevo.';

        if (isTimeout) {
            // Don't wait for user to dismiss — show brief toast and reload
            void cf4Toast({ icon: 'warning', title, text: message, timer: 3500 });
            window.setTimeout(() => window.location.reload(), 3500);
        } else {
            await cf4Error(message, title);
        }
    }
}

function orderAction(url, successMsg, payload = null) {
    return doOrderAction(url, {
        payload,
        successTitle: () => successMsg,
    });
}

async function completeSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || `#${id}`;

    const result = await cf4Confirm({
        title: `¿Confirmar encargo con factura ${invoiceLabel}?`,
        text: 'El pedido pasará a confirmado. No se volverá a descontar stock porque ya fue reservado en el checkout.',
        icon: 'question',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    await doOrderAction(`/sales/${id}/complete`, {
        successTitle: () => 'Encargo confirmado',
    });
}

async function cancelSale(id, invoiceNumber) {
    const invoiceLabel = invoiceNumber || `#${id}`;

    const result = await cf4PromptTextarea({
        title: `¿Rechazar encargo ${invoiceLabel}?`,
        text: 'Ingrese el motivo de cancelación. El stock reservado se devolverá al inventario.',
        placeholder: 'Motivo de cancelación',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
        minLength: 3,
        maxLength: 500,
        danger: true,
    });

    if (!result.isConfirmed) return;

    await doOrderAction(`/sales/${id}/cancel`, {
        payload: {
            reason: result.value,
        },
        successTitle: () => 'Encargo rechazado',
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

            const confirmedAtRow = sale.status === 'completed'
                ? `<div class="cf4-order-detail__item">
                        <span>Fecha de confirmación</span>
                        <strong>${escapeHtml(sale.confirmed_at_label || '—')}</strong>
                    </div>`
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

                    return `<tr>
                        <td>${escapeHtml(prod.name || 'N/A')}</td>
                        <td class="text-center">${escapeHtml(String(item.quantity ?? '0'))}</td>
                        <td class="text-right">₡${up.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right"><strong>₡${tot.toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
                    </tr>`;
                })
                .join('');

            const statusClass = escapeHtml(sale.status || '');
            const statusText = escapeHtml(statusLabels[sale.status] || sale.status || '—');
            const paymentText = escapeHtml(paymentLabels[sale.payment_method] || sale.payment_method || '—');

            body.innerHTML = `
                <div class="cf4-order-detail">
                    <section class="cf4-order-detail__summary">
                        <div class="cf4-order-detail__item">
                            <span>${escapeHtml(saleDateLabel)}</span>
                            <strong>${escapeHtml(saleDateValue)}</strong>
                        </div>
                        ${confirmedAtRow}
                        <div class="cf4-order-detail__item">
                            <span>Cliente</span>
                            <strong>${escapeHtml(customerName)}</strong>
                        </div>
                        <div class="cf4-order-detail__item">
                            <span>Estado</span>
                            <strong class="order-status-pill ${statusClass}">${statusText}</strong>
                        </div>
                        <div class="cf4-order-detail__item">
                            <span>Método de pago</span>
                            <strong>${paymentText}</strong>
                        </div>
                    </section>

                    <section class="cf4-order-detail__products">
                        <h4><i class="fas fa-box" aria-hidden="true"></i> Productos del pedido</h4>
                        <div class="admin-table-responsive">
                            <table class="sales-table admin-table">
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
                    </section>
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
