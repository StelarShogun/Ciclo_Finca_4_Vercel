// @ts-nocheck
/** Shared cart utilities for header, home, and catalog bundles. */

export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

export function isClientStockShortMessage(msg) {
    return msg === 'Producto agotado' || msg === 'Stock insuficiente';
}

/** Update navbar cart badge count. */
export function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl = document.getElementById('cart-link');
    const mobileCountEl = document.getElementById('header-mobile-cart-count');
    const mobileLinkEl = document.getElementById('header-mobile-cart-link');

    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
    if (mobileCountEl) {
        mobileCountEl.textContent = count;
        mobileCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (mobileLinkEl) {
        mobileLinkEl.setAttribute('data-cart-count', String(count));
        mobileLinkEl.setAttribute(
            'aria-label',
            count > 0 ? `Ver carrito (${count} productos)` : 'Ver carrito',
        );
    }

    import('./header-menu-alert').then((m) => {
        m.setHeaderAlertMeta('cf4-header-alert-cart', count);
        m.updateHeaderMenuToggleBadge();
    }).catch(() => {});
}

import { cf4Toast, cf4Error, cf4Warning } from './swal';

function showToast(payload) {
    if (typeof window !== 'undefined' && typeof window.cf4ShowToast === 'function') {
        window.cf4ShowToast(payload);
        return true;
    }
    return false;
}

/** Add product to cart via AJAX. */
export function addToCart(productId, quantity, triggerBtn) {
    quantity = quantity || 1;

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity }),
    })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.cart_count);
                if (!showToast({ variant: 'success', title: '¡Agregado!', message: data.message || 'Producto agregado al carrito', durationMs: 2000 })) {
                    void cf4Toast({
                        icon: 'success',
                        title: '¡Agregado!',
                        text: data.message || 'Producto agregado al carrito',
                        timer: 2000,
                    });
                }
            } else {
                const msg = data.message || 'No se pudo agregar el producto al carrito';
                const stockShort = isClientStockShortMessage(msg);
                if (stockShort) {
                    if (!showToast({ variant: 'warning', title: 'Atención', message: msg, durationMs: 3500 })) {
                        void cf4Warning(msg, 'Atención');
                    }
                } else {
                    if (!showToast({ variant: 'error', title: 'Error', message: msg })) {
                        void cf4Error(msg, 'Error');
                    }
                }
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            if (!showToast({ variant: 'error', title: 'Error', message: 'Ocurrió un error al agregar el producto al carrito.' })) {
                void cf4Error('Ocurrió un error al agregar el producto al carrito.', 'Error');
            }
        });
}

export function initCartBadgeFromDom() {
    const cartLinkEl = document.getElementById('cart-link');
    const cartGuestEl = document.getElementById('cart-guest');
    const mobileLinkEl = document.getElementById('header-mobile-cart-link');
    const cartRef = cartLinkEl || cartGuestEl || mobileLinkEl;
    if (cartRef) {
        const initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }
}

export function initGuestCartPrompt() {
    const cartGuestEl = document.getElementById('cart-guest');
    if (!cartGuestEl) return;

    cartGuestEl.addEventListener('click', function () {
        if (!showToast({ variant: 'warning', title: 'Inicia sesión', message: 'Debes iniciar sesión para ver tu carrito.' })) {
            void cf4Warning('Debes iniciar sesión para ver tu carrito.', 'Inicia sesión');
        }
    });
}
