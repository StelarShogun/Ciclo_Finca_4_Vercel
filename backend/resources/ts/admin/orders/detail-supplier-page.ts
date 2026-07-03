// @ts-nocheck
import { cf4Toast, cf4Warning } from '../shared/swal';

export async function showReceptionSuccess(message) {
    await cf4Toast({
        icon: 'success',
        title: 'Recepción registrada',
        text: message,
        timer: 3000,
    });
}

export async function showPartialCloseWarning(message) {
    await cf4Warning(message, 'Pedido cerrado con faltantes');
}

Object.assign(window, {
    showReceptionSuccess,
    showPartialCloseWarning,
});
