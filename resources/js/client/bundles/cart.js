import { initCartInteractions } from '../cart-actions.js';
import { addToCart, getCsrfToken, updateCartCount } from '../cart-shared.js';
import '../../shared/client-pagination.js';

window.__cf4ClientPageJsLoaded = true;

export function initClientCartPage() {
    initCartInteractions();
}

export { addToCart, getCsrfToken, updateCartCount };
