import { cf4Toast, cf4Error, cf4Warning } from './swal.js';

document.addEventListener('DOMContentLoaded', async () => {
    const flash = window.__cf4ClientFlash || null;
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
});
