/** Yellow alert dot on the mobile hamburger when cart / invoices / notifications need attention. */

function readMetaInt(name, fallback = 0) {
    const meta = document.querySelector(`meta[name="${name}"]`);
    if (!meta) {
        return fallback;
    }

    const value = parseInt(meta.getAttribute('content') || '', 10);

    return Number.isFinite(value) ? value : fallback;
}

export function setHeaderAlertMeta(name, value) {
    let meta = document.querySelector(`meta[name="${name}"]`);
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', name);
        document.head.appendChild(meta);
    }

    meta.setAttribute('content', String(Math.max(0, Number(value) || 0)));
}

function readCartCount() {
    const fromMeta = readMetaInt('cf4-header-alert-cart', -1);
    if (fromMeta >= 0) {
        return fromMeta;
    }

    for (const id of ['header-mobile-cart-count', 'cart-count']) {
        const el = document.getElementById(id);
        if (!el) {
            continue;
        }

        const value = parseInt(el.textContent || '', 10);
        if (Number.isFinite(value) && value > 0) {
            return value;
        }
    }

    for (const id of ['cart-link', 'header-mobile-cart-link', 'cart-guest']) {
        const el = document.getElementById(id);
        if (!el) {
            continue;
        }

        const value = parseInt(el.getAttribute('data-cart-count') || '0', 10);
        if (Number.isFinite(value) && value > 0) {
            return value;
        }
    }

    return 0;
}

export function headerMenuHasPendingAlerts() {
    return (
        readCartCount() > 0
        || readMetaInt('cf4-header-alert-invoices') > 0
        || readMetaInt('cf4-header-alert-notifications') > 0
        || readMetaInt('cf4-header-alert-history') > 0
    );
}

export function updateHeaderMenuToggleBadge() {
    const toggle = document.getElementById('header-menu-toggle');
    if (!toggle) {
        return;
    }

    const hasAlert = headerMenuHasPendingAlerts();
    let badge = toggle.querySelector('.header-menu-toggle-badge');

    if (hasAlert) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'header-menu-toggle-badge';
            badge.setAttribute('aria-hidden', 'true');
            toggle.appendChild(badge);
        }
        badge.hidden = false;
        toggle.classList.add('has-alert');
        toggle.setAttribute('aria-label', 'Abrir menú de navegación (tienes novedades)');
    } else {
        if (badge) {
            badge.hidden = true;
        }
        toggle.classList.remove('has-alert');
        toggle.setAttribute('aria-label', 'Abrir menú de navegación');
    }
}
