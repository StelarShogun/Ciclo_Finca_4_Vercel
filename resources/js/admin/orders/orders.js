const ORDERS_HEARTBEAT_INTERVAL_MS = 45000;

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

function initOrdersHeartbeat() {
    const root = document.querySelector('[data-cf4-orders-heartbeat]');
    const latestEl = document.getElementById('cf4-latest-purchase-sale-id');
    const heartbeatUrl = metaContent('sales-route-heartbeat');

    if (!root || !latestEl || !heartbeatUrl) {
        return;
    }

    let latestPurchaseSaleId = parseInt(latestEl.dataset.value, 10) || 0;
    let bannerDismissed = false;

    const kpiEl = document.querySelector('[data-cf4-orders-pending-kpi]');

    if (kpiEl) {
        const initialPending = Number(kpiEl.textContent.replace(/[^\d]/g, '')) || 0;
        updateOrdersPendingBadges(initialPending);
    }

    const refreshBtn = document.querySelector('[data-cf4-orders-refresh-btn]');
    const dismissBtn = document.querySelector('[data-cf4-orders-dismiss-banner]');

    refreshBtn?.addEventListener('click', () => {
        window.location.reload();
    });

    dismissBtn?.addEventListener('click', () => {
        bannerDismissed = true;
        hideOrdersNewBanner();
    });

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

            if (typeof data.latestSaleId !== 'undefined') {
                latestPurchaseSaleId = data.latestSaleId;
            }

            if (typeof data.pendingCount !== 'undefined') {
                updateOrdersPendingBadges(data.pendingCount);
            }

            if (data.hasNew && !bannerDismissed) {
                showOrdersNewBanner(data.newCount);
            }
        } catch {
            /* fail silently */
        }
    }

    heartbeatCheck();
    window.setInterval(heartbeatCheck, ORDERS_HEARTBEAT_INTERVAL_MS);
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

Object.assign(window, {
    markReadyToPickup,
    completeSale,
    cancelSale,
    orderAction,
});