import '../../shared/ajax-pagination.js';
import {
    cf4Confirm,
    cf4PromptTextarea,
    cf4Toast,
    cf4Error,
    cf4Loading,
    cf4Close,
    escapeHtml,
} from '../shared/swal.js';

document.addEventListener('DOMContentLoaded', () => {
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
}

async function doOrderAction(url, {
    method = 'POST',
    payload = null,
    successTitle = () => 'Listo',
} = {}) {
    const csrf = getCsrfToken();
    const controller = new AbortController();

    const timeoutId = window.setTimeout(() => {
        controller.abort();
    }, 15000);

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

            window.location.reload();

            return;
        }

        const message = data.message || `No se pudo completar la acción. Código: ${res.status}`;

        await cf4Error(message, 'Error');
    } catch (error) {
        window.clearTimeout(timeoutId);
        await cf4Close();

        const isTimeout = error.name === 'AbortError';
        const title = isTimeout ? 'Tiempo de espera agotado' : 'Error de red';
        const message = isTimeout
            ? 'El servidor tardó demasiado en responder. Revise el controlador, la ruta o la lógica del backend.'
            : 'No se pudo conectar con el servidor.';

        await cf4Error(message, title);
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

            const readyAtRow = sale.ready_at || sale.ready_at_label
                ? `<div class="cf4-order-detail__item">
                        <span>Fecha listo para recoger</span>
                        <strong>${escapeHtml(sale.ready_at_label || '—')}</strong>
                    </div>`
                : '';

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
                        ${readyAtRow}
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