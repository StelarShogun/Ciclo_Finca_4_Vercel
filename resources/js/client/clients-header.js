import { initHeaderCatalogSearch } from './header-catalog-search.js';
import {
    initCartBadgeFromDom,
    initGuestCartPrompt,
    updateCartCount,
} from './cart-shared.js';
import { initCatalogFavoriteClickDelegation } from './catalog-product-favorites.js';

initHeaderCatalogSearch();

document.addEventListener('DOMContentLoaded', function () {
    initCartBadgeFromDom();
    initGuestCartPrompt();
    initCatalogFavoriteClickDelegation();

    if (document.querySelector('meta[name="cf4-favorites-index-url"]')) {
        import('./clients-header-auth.js').then((m) => m.initClientHeaderAuth());
    }

    if (document.querySelector('meta[name="cf4-invoice-heartbeat-url"]')) {
        import('./clients-invoice-heartbeat.js').then((m) => m.startInvoiceHeartbeat());
    }
});

window.updateCartCount = updateCartCount;
