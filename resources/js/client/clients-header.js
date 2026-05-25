/**
 * Client header bootstrap — only the critical pieces run synchronously:
 *
 * - Cart badge from DOM (cheap, prevents badge flicker)
 * - Guest "must sign in" cart message (tiny click handler)
 *
 * Everything else is deferred to idle so it never blocks FCP/LCP.
 *
 * Single polling loop: clients-notification-toasts.js handles BOTH toast
 * notifications AND invoice/history badge updates (the notifications heartbeat
 * endpoint returns all of that data). The old clients-invoice-heartbeat.js
 * is kept for pages that explicitly need it (e.g. invoice-detail without auth
 * header), but the header no longer starts it — one loop, one interval.
 */
import {
    initCartBadgeFromDom,
    initGuestCartPrompt,
    updateCartCount,
} from './cart-shared.js';
import { initClientHeaderMenu } from './header-menu.js';
import { updateHeaderMenuToggleBadge } from './header-menu-alert.js';

const onIdle = (cb) => {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(cb, { timeout: 1500 });
    } else {
        setTimeout(cb, 200);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    initClientHeaderMenu();
    initCartBadgeFromDom();
    initGuestCartPrompt();
    updateHeaderMenuToggleBadge();

    // Catalog search trending only matters once the user focuses the input.
    onIdle(() => {
        import('./header-catalog-search.js').then((m) => m.initHeaderCatalogSearch());
    });

    // Single delegated click listener for product favorite hearts. Only worth
    // wiring up on pages that actually render those buttons.
    onIdle(() => {
        if (!document.querySelector('[data-product-favorite-btn]')) return;
        import('./catalog-product-favorites.js').then((m) => m.initCatalogFavoriteClickDelegation());
    });

    // Account dropdown must bind on first paint (not gated on favorites meta / idle).
    if (document.getElementById('user-menu-trigger')) {
        import('./clients-header-auth.js').then((m) => m.initClientHeaderAuth());
    }

    // Single unified polling loop: handles toasts, notification badge,
    // invoice count badge and unseen-history badge — all at the same interval.
    if (document.querySelector('meta[name="cf4-notifications-heartbeat-url"]')) {
        onIdle(() => {
            import('./clients-notification-toasts.js').then((m) => m.startNotificationToasts());
        });
    }
});

window.updateCartCount = updateCartCount;
