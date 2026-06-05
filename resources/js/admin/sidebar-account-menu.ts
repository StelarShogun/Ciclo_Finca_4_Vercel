// @ts-nocheck
/**
 * Admin sidebar — cuenta desplegable hacia arriba (tema, sitio web, cerrar sesión).
 */
export function initSidebarAccountMenu() {
    const root = document.querySelector('[data-sidebar-account-menu]');
    if (!root || root.dataset.cf4AccountMenuInit === '1') {
        return;
    }

    root.dataset.cf4AccountMenuInit = '1';

    const trigger = root.querySelector('[data-sidebar-account-trigger]');
    const panel = root.querySelector('[data-sidebar-account-panel]');

    if (!trigger || !panel) {
        return;
    }

    const setOpen = (open) => {
        root.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.hidden = !open;
    };

    const close = () => setOpen(false);
    const toggle = () => setOpen(panel.hidden);

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        toggle();
    });

    panel.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}
