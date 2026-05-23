/** Lazy SweetAlert2 — avoid loading on pages that never open a dialog. */

export { escapeHtml } from '../shared/escape-html.js';

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
