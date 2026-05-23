import { cf4DialogDefaults, escapeHtml, fireSwal } from './swal.js';

document.addEventListener('DOMContentLoaded', async () => {
    const errors = window.__cf4RegisterErrors;
    if (!errors?.length) return;

    const html = '<ul style="text-align:left;margin:0;padding-left:18px;">'
        + errors.map((err) => `<li>${escapeHtml(err)}</li>`).join('')
        + '</ul>';

    await fireSwal({
        ...cf4DialogDefaults(),
        icon: 'error',
        title: 'Error en el registro',
        html,
        confirmButtonText: 'Entendido',
    });
});
