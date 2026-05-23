/**
 * Mobile header menu (hamburger) — must run on every page (guest + auth).
 */

/** Sets mobile header menu open/closed state. */
export function setHeaderMenuOpen(open) {
    const header = document.querySelector('.cliente-header');
    const toggle = document.getElementById('header-menu-toggle');
    const panel = document.getElementById('header-menu-panel');
    const icon = toggle ? toggle.querySelector('i') : null;

    if (!header) {
        return;
    }

    header.classList.toggle('menu-open', open);

    if (typeof window.cf4CloseUserDropdown === 'function') {
        window.cf4CloseUserDropdown();
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute(
            'aria-label',
            open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación',
        );
    }

    if (panel) {
        panel.setAttribute('aria-hidden', String(!open));
    }

    if (icon) {
        icon.classList.toggle('fa-bars', !open);
        icon.classList.toggle('fa-times', open);
    }

    if (open) {
        requestAnimationFrame(() => {
            if (typeof window.cf4SyncMobileUserDropdownPosition === 'function') {
                window.cf4SyncMobileUserDropdownPosition();
            }
            if (typeof window.cf4SyncHeaderSearchSuggestionsPosition === 'function') {
                window.cf4SyncHeaderSearchSuggestionsPosition();
            }
        });
    }
}

export function initClientHeaderMenu() {
    if (window.__cf4HeaderMenuBound) {
        return;
    }

    window.__cf4HeaderMenuBound = true;

    const headerMenuToggle = document.getElementById('header-menu-toggle');
    if (headerMenuToggle) {
        headerMenuToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const header = document.querySelector('.cliente-header');
            if (!header) {
                return;
            }
            setHeaderMenuOpen(!header.classList.contains('menu-open'));
        });
    }

    document.querySelectorAll('.header-menu-panel .nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            setHeaderMenuOpen(false);
        });
    });

    document.addEventListener('click', (event) => {
        const header = document.querySelector('.cliente-header');
        if (!header || !header.classList.contains('menu-open')) {
            return;
        }
        if (header.contains(event.target)) {
            return;
        }
        setHeaderMenuOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        setHeaderMenuOpen(false);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) {
            setHeaderMenuOpen(false);
        }
        if (typeof window.cf4SyncMobileUserDropdownPosition === 'function') {
            window.cf4SyncMobileUserDropdownPosition();
        }
    });
}

window.cf4SetHeaderMenuOpen = setHeaderMenuOpen;
