import { cf4Confirm } from '../shared/swal.js';

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;

    const form = btn.closest('form');
    if (!form) return;

    e.preventDefault();
    e.stopPropagation();

    const message = btn.getAttribute('data-confirm');
    const title = btn.getAttribute('data-confirm-title') || '¿Deseas continuar?';

    void cf4Confirm({
        title,
        text: message,
        icon: 'warning',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'No, cancelar',
        danger: true,
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
