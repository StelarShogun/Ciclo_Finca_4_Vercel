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

    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

export async function fireSwal(options) {
    const { default: Swal } = await import('sweetalert2');
    return Swal.fire(options);
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
                fireSwal({
                    icon: 'success',
                    title: '¡Agregado!',
                    text: data.message || 'Producto agregado al carrito',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                });
            } else {
                const msg = data.message || 'No se pudo agregar el producto al carrito';
                const stockShort = isClientStockShortMessage(msg);
                fireSwal({
                    icon: stockShort ? 'warning' : 'error',
                    title: stockShort ? msg : 'Error',
                    text: stockShort ? '' : msg,
                });
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            fireSwal({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al agregar el producto al carrito',
            });
        });
}

export function initCartBadgeFromDom() {
    const cartLinkEl = document.getElementById('cart-link');
    const cartGuestEl = document.getElementById('cart-guest');
    const cartRef = cartLinkEl || cartGuestEl;
    if (cartRef) {
        const initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }
}

export function initGuestCartPrompt() {
    const cartGuestEl = document.getElementById('cart-guest');
    if (!cartGuestEl) return;

    cartGuestEl.addEventListener('click', function () {
        fireSwal({
            icon: 'info',
            title: 'Inicia sesión',
            text: 'Debes iniciar sesión para ver tu carrito.',
            confirmButtonText: 'Entendido',
        });
    });
}
