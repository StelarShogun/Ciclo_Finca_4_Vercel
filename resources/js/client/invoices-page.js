/**
 * Mis Facturas — custom tab dropdown (mobile + desktop, avoids native select quirks).
 */
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInvoicesTabDropdown, { once: true });
} else {
    initInvoicesTabDropdown();
}
