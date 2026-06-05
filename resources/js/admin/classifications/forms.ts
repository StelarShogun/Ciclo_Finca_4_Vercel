// @ts-nocheck
import { cf4Confirm } from '../shared/swal';

function bindClassificationFormConfirm(form) {
    if (!form || form.dataset.cf4ConfirmBound === '1') {
        return;
    }

    form.dataset.cf4ConfirmBound = '1';

    form.addEventListener('submit', async (event) => {
        if (form.dataset.cf4Confirmed === '1') {
            form.dataset.cf4Confirmed = '0';
            return;
        }

        event.preventDefault();

        const isDanger = form.dataset.confirmDanger === '1';
        const title = form.dataset.confirmTitle || '¿Guardar cambios?';
        const text = form.dataset.confirmText || 'Se aplicarán los cambios en el catálogo de atributos.';

        const result = await cf4Confirm({
            title,
            text,
            icon: isDanger ? 'warning' : 'question',
            confirmButtonText: form.dataset.confirmButton || 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            danger: isDanger,
        });

        if (!result.isConfirmed) {
            return;
        }

        form.dataset.cf4Confirmed = '1';
        form.requestSubmit();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-cf4-confirm]').forEach(bindClassificationFormConfirm);
});
