/**
 * Client header bootstrap — only the critical pieces run synchronously:
 *
 * - Cart badge from DOM (cheap, prevents badge flicker)
 * - Guest "must sign in" cart prompt (tiny click handler)
 *
 * Everything else (catalog search trending fetch, favorites click delegation,
 * authenticated user menu / favorites drawer, invoice heartbeat polling) is
 * loaded on idle, after the first paint, so it never blocks FCP/LCP.
 */
import {
    initCartBadgeFromDom,
    initGuestCartPrompt,
    updateCartCount,
} from './cart-shared.js';

const onIdle = (cb) => {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(cb, { timeout: 1500 });
    } else {
        setTimeout(cb, 200);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    initCartBadgeFromDom();
    initGuestCartPrompt();

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

    if (document.querySelector('meta[name="cf4-favorites-index-url"]')) {
        onIdle(() => {
            import('./clients-header-auth.js').then((m) => m.initClientHeaderAuth());
        });
    }

    if (document.querySelector('meta[name="cf4-invoice-heartbeat-url"]')) {
        onIdle(() => {
            import('./clients-invoice-heartbeat.js').then((m) => m.startInvoiceHeartbeat());
        });
    }
});

window.updateCartCount = updateCartCount;
