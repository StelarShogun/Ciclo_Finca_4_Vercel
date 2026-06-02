/** Lazy SweetAlert2 — avoid loading on pages that never open a dialog. */

import { escapeHtml } from '../shared/escape-html.js';

export { escapeHtml };

let swalModulePromise = null;

export async function getSwal() {
    if (typeof window !== 'undefined' && window.Swal) {
        return window.Swal;
    }

    if (!swalModulePromise) {
        swalModulePromise = import('sweetalert2').then((mod) => {
            const Swal = mod.default || mod;
            window.Swal = Swal;
            return Swal;
        });
    }

    return swalModulePromise;
}

export async function fireSwal(options) {
    const Swal = await getSwal();
    return Swal.fire(options);
}

export const cf4SwalClasses = {
    popup: 'cf4-swal-popup',
    title: 'cf4-swal-title',
    htmlContainer: 'cf4-swal-html',
    actions: 'cf4-swal-actions',
    confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
    cancelButton: 'cf4-swal-btn cf4-swal-btn-muted',
    denyButton: 'cf4-swal-btn cf4-swal-btn-danger',
    loader: 'cf4-swal-loader',
};

export function cf4DialogDefaults() {
    return {
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true,
        customClass: { ...cf4SwalClasses },
    };
}

export async function cf4Confirm(options = {}) {
    const Swal = await getSwal();

    const {
        title = '¿Confirmar acción?',
        text = '',
        html = null,
        icon = 'question',
        confirmButtonText = 'Sí, continuar',
        cancelButtonText = 'Cancelar',
        danger = false,
        confirmStyle = 'primary',
    } = options;

    const confirmClass = danger || confirmStyle === 'danger'
        ? 'cf4-swal-btn cf4-swal-btn-danger'
        : confirmStyle === 'warning'
            ? 'cf4-swal-btn cf4-swal-btn-warning'
            : 'cf4-swal-btn cf4-swal-btn-primary';

    return Swal.fire({
        ...cf4DialogDefaults(),
        title,
        text: html ? undefined : text,
        html: html || undefined,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        customClass: {
            ...cf4SwalClasses,
            confirmButton: confirmClass,
        },
    });
}

export async function cf4PromptTextarea({
    title = 'Ingrese la información requerida',
    text = '',
    placeholder = 'Escriba aquí...',
    confirmButtonText = 'Confirmar',
    cancelButtonText = 'Cancelar',
    minLength = 3,
    maxLength = 500,
    danger = false,
} = {}) {
    const Swal = await getSwal();

    return Swal.fire({
        ...cf4DialogDefaults(),
        title,
        text,
        input: 'textarea',
        inputPlaceholder: placeholder,
        inputAttributes: {
            maxlength: String(maxLength),
        },
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        customClass: {
            ...cf4SwalClasses,
            confirmButton: danger
                ? 'cf4-swal-btn cf4-swal-btn-danger'
                : 'cf4-swal-btn cf4-swal-btn-primary',
        },
        inputValidator: (value) => {
            if (!value || value.trim().length < minLength) {
                return `Debe ingresar al menos ${minLength} caracteres.`;
            }

            return null;
        },
        preConfirm: (value) => value.trim(),
    });
}

export async function cf4Toast({
    icon = 'success',
    title = 'Operación completada',
    text = '',
    timer = 3000,
} = {}) {
    const Swal = await getSwal();

    return Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        text: text || undefined,
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        showCloseButton: true,
        customClass: {
            popup: 'cf4-swal-toast',
            title: 'cf4-swal-title',
        },
    });
}

/** Floating toast for order status updates — ~5s, readable, with optional action link. */
export async function cf4OrderStatusToast({
    title = '¡Listo para recoger!',
    message = '',
    actionUrl = '',
    actionLabel = '',
    timer = 5000,
    kind = 'ready_to_pickup',
} = {}) {
    const Swal = await getSwal();

    const iconColors = {
        ready_to_pickup: '#235347',
        completed: '#235347',
        cancelled: '#c0392b',
    };
    const borderColor = iconColors[kind] ?? '#235347';

    const actionHtml = actionUrl
        ? `<a href="${actionUrl}" class="cf4-order-status-toast-action">${escapeHtml(actionLabel || 'Ver pedido')}</a>`
        : '';
    const bodyHtml = (message || actionHtml)
        ? `<div class="cf4-order-status-toast-content">`
          + (message ? `<p class="cf4-order-status-toast-body">${escapeHtml(message)}</p>` : '')
          + actionHtml
          + `</div>`
        : '';

    const Toast = Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        showCloseButton: true,
        didOpen(popup) {
            popup.style.setProperty('border-left-color', borderColor, 'important');
            popup.addEventListener('mouseenter', Swal.stopTimer);
            popup.addEventListener('mouseleave', Swal.resumeTimer);
        },
        customClass: {
            popup: 'cf4-swal-toast cf4-order-status-toast-popup',
            htmlContainer: 'cf4-order-status-toast-html',
            title: 'cf4-order-status-toast-title',
            timerProgressBar: 'cf4-order-status-toast-progress',
        },
    });

    return Toast.fire({ icon: false, title, html: bodyHtml });
}

/** Persistent success dialog — user must confirm (e.g. post-checkout). */
export async function cf4CheckoutSuccessDialog({
    title = '¡Pedido confirmado!',
    text = '',
    confirmButtonText = 'Ver mis pedidos',
    cancelButtonText = 'Seguir comprando',
} = {}) {
    const Swal = await getSwal();
    const bodyHtml = text
        ? `<p class="cf4-swal-checkout-body">${escapeHtml(text)}</p>`
        : '';

    return Swal.fire({
        ...cf4DialogDefaults(),
        icon: 'success',
        title,
        html: bodyHtml,
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        customClass: {
            ...cf4SwalClasses,
            popup: 'cf4-swal-popup cf4-swal-popup--checkout-success',
            confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
            cancelButton: 'cf4-swal-btn cf4-swal-btn-secondary',
        },
        didOpen(popup) {
            const actions = popup.querySelector('.swal2-actions');
            if (actions) {
                actions.style.setProperty('display', 'flex', 'important');
                actions.style.setProperty('justify-content', 'center', 'important');
                actions.style.setProperty('align-items', 'center', 'important');
                actions.style.setProperty('width', '100%', 'important');
                actions.style.setProperty('flex-wrap', 'wrap', 'important');
                actions.style.setProperty('gap', '0.5rem', 'important');
            }

            popup.querySelectorAll('.swal2-confirm, .swal2-cancel').forEach((btn) => {
                btn.style.setProperty('width', 'auto', 'important');
                btn.style.setProperty('min-width', '10rem', 'important');
                btn.style.setProperty('margin-left', '0', 'important');
                btn.style.setProperty('margin-right', '0', 'important');
                btn.style.setProperty('flex', '0 0 auto', 'important');
            });

            const htmlContainer = popup.querySelector('.swal2-html-container');
            if (htmlContainer) {
                htmlContainer.style.setProperty('text-align', 'justify', 'important');
            }
        },
    });
}

export async function cf4Success(message = 'Operación realizada correctamente.', title = 'Listo') {
    return cf4Toast({
        icon: 'success',
        title,
        text: message,
        timer: 3000,
    });
}

export async function cf4Error(message = 'Ocurrió un error inesperado.', title = 'Error') {
    return fireSwal({
        ...cf4DialogDefaults(),
        icon: 'error',
        title,
        text: message,
        confirmButtonText: 'Cerrar',
    });
}

export async function cf4Warning(message, title = 'Atención') {
    return fireSwal({
        ...cf4DialogDefaults(),
        icon: 'warning',
        title,
        text: message,
        confirmButtonText: 'Entendido',
    });
}

export async function cf4Loading(title = 'Procesando…', text = 'Espere mientras se completa la acción.') {
    const Swal = await getSwal();

    void Swal.fire({
        ...cf4DialogDefaults(),
        title,
        text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    return Swal;
}

export async function cf4Close() {
    const Swal = await getSwal();
    Swal.close();
}
