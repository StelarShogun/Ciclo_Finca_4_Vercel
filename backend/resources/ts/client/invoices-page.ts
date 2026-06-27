// @ts-nocheck
/**
 * Mis Facturas — confirm dialogs before detail/print navigation.
 */
import { cf4Confirm } from './swal';
import '../shared/client-pagination';

function initInvoiceAutoPrint() {
    const meta = document.querySelector('meta[name="cf4-auto-print"]');
    if (!meta || meta.content !== '1') {
        return;
    }

    window.addEventListener('load', () => {
        window.setTimeout(() => window.print(), 400);
    }, { once: true });
}

function initInvoiceConfirmHandlers() {
    document.addEventListener('click', async (e) => {
        const printLink = e.target.closest('[data-cf4-confirm-print]');
        if (printLink) {
            e.preventDefault();

            const result = await cf4Confirm({
                title: '¿Imprimir comprobante?',
                text: 'Se abrirá la vista de impresión o PDF de la factura.',
                icon: 'question',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) return;

            const printUrl = printLink.getAttribute('data-print-url');
            if (printUrl) {
                window.open(printUrl, '_blank', 'noopener,noreferrer');
                return;
            }

            if (printLink.tagName === 'A') {
                window.location.href = printLink.href;
                return;
            }

            window.setTimeout(() => window.print(), 350);

            return;
        }

        const invoiceLink = e.target.closest('[data-cf4-confirm-invoice]');
        if (!invoiceLink) return;

        e.preventDefault();

        const result = await cf4Confirm({
            title: '¿Ver factura?',
            text: 'Se abrirá el detalle de la factura seleccionada.',
            icon: 'question',
            confirmButtonText: 'Sí, ver factura',
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return;

        window.location.href = invoiceLink.href;
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initInvoiceConfirmHandlers();
        initInvoiceAutoPrint();
    }, { once: true });
} else {
    initInvoiceConfirmHandlers();
    initInvoiceAutoPrint();
}
