/**
 * Mis Facturas — custom tab dropdown (mobile + desktop, avoids native select quirks).
 */
import { cf4Confirm } from './swal.js';

function initInvoicesTabDropdown() {
    const root = document.querySelector('[data-cf4-invoices-tab-dropdown]');
    if (!root) {
        return;
    }

    const trigger = root.querySelector('.cf4-invoices-tab-trigger');
    const menu = root.querySelector('.cf4-invoices-tab-menu');
    if (!trigger || !menu) {
        return;
    }

    const close = () => {
        root.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
    };

    const open = () => {
        root.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
    };

    const toggle = () => {
        if (root.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        toggle();
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            trigger.focus();
        }
    });

    menu.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => close());
    });
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

            if (printLink.tagName === 'A') {
                window.location.href = printLink.href;
            } else {
                window.print();
            }

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
        initInvoicesTabDropdown();
        initInvoiceConfirmHandlers();
    }, { once: true });
} else {
    initInvoicesTabDropdown();
    initInvoiceConfirmHandlers();
}
