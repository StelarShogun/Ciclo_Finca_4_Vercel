/**
 * Admin shell — shared admin UI (tables, list pagination, SweetAlert2 prefetch).
 */
import '../shared/admin-table-responsive.js';
import '../shared/ajax-pagination.js';
import { getSwal, cf4Toast, cf4Error, cf4Warning } from './shared/swal.js';

const prefetchSwal = () => {
    void getSwal();
};

if (typeof requestIdleCallback === 'function') {
    requestIdleCallback(prefetchSwal, { timeout: 2500 });
} else {
    setTimeout(prefetchSwal, 200);
}

async function renderCf4FlashMessages() {
    const flash = window.__cf4Flash || null;
    if (!flash || flash.__rendered) return;

    flash.__rendered = true;

    if (flash.error) {
        await cf4Error(flash.error, 'No se pudo completar');
        return;
    }

    if (flash.warning) {
        await cf4Warning(flash.warning, 'Atención');
        return;
    }

    if (flash.status || flash.success) {
        await cf4Toast({
            icon: 'success',
            title: 'Listo',
            text: flash.status || flash.success,
            timer: 3000,
        });
        return;
    }

    if (flash.info) {
        await cf4Toast({
            icon: 'info',
            title: 'Información',
            text: flash.info,
            timer: 3600,
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    void renderCf4FlashMessages();
});
