import { cf4Error, cf4DialogDefaults, getSwal } from './swal.js';

document.addEventListener('DOMContentLoaded', async () => {
    const errors = window.__cf4RegisterErrors;
    if (!errors?.length) return;

    const html = '<ul style="text-align:left;margin:0;padding-left:18px;">'
        + errors.map((err) => `<li>${err}</li>`).join('')
        + '</ul>';

    const Swal = await getSwal();
    await Swal.fire({
        ...cf4DialogDefaults(),
        icon: 'error',
        title: 'Error en el registro',
        html,
        confirmButtonText: 'Entendido',
    });
});
