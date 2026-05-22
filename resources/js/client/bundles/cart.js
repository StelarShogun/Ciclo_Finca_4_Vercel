import { fireSwal } from '../swal.js';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from '../checkout-copy.js';
import {
    addToCart,
    getCsrfToken,
    isClientStockShortMessage,
    updateCartCount,
} from '../cart-shared.js';
import '../../shared/ajax-pagination.js';

window.__cf4ClientPageJsLoaded = true;


/** Selected checkout payment method from summary radios (default cash). */
function getCheckoutPaymentMethod() {
    var selected = document.querySelector('input[name="checkout_payment_method"]:checked');
    return selected ? selected.value : 'cash';
}

/** PUTs a quantity change for a single cart item, then updates DOM (no reload). */
function updateCartQuantity(productId, quantity) {
    fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
        .then(function (res) { return res.json().catch(function () { return {}; }); })
        .then(function (data) {
            if (data.success) {
                var totalFormatted = (data.cart_total != null)
                    ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                    : '₡0';

                var subtotalEl = document.getElementById('cart-subtotal');
                var totalEl    = document.getElementById('cart-total-amount');
                if (subtotalEl) subtotalEl.textContent = totalFormatted;
                if (totalEl)    totalEl.textContent    = totalFormatted;

                updateCartCount(data.cart_count || 0);

                var cartItem = document.querySelector('.cart-item[data-product-id="' + productId + '"]');
                if (cartItem) {
                    var lineSubtotalEl = cartItem.querySelector('.subtotal-amount');
                    if (lineSubtotalEl && data.line_subtotal != null) {
                        lineSubtotalEl.textContent = '₡' + Number(data.line_subtotal).toLocaleString('es-CR');
                    }
                    var qtyInput = cartItem.querySelector('.quantity-input');
                    if (qtyInput && data.quantity_applied != null) {
                        qtyInput.value = data.quantity_applied;
                    }
                    if (data.stock_clamped) {
                        fireSwal(
                            'Aviso',
                            'La cantidad se ajustó al stock disponible (' + data.quantity_applied + ' unidades).',
                            'warning'
                        );
                    }
                }
            } else {
                var umsg = data.message || 'No se pudo actualizar el carrito';
                var uShort = isClientStockShortMessage(umsg);
                fireSwal(uShort ? umsg : 'Error', uShort ? '' : umsg, uShort ? 'warning' : 'error');
            }
        })
        .catch(function () {
            fireSwal('Error', 'Ocurrió un error al actualizar el carrito', 'error');
        });
}

/** Replaces the cart card with an empty-state message (no reload). */
function showCartEmptyState() {
    var card = document.querySelector('.cart-page-card');
    if (!card) return;
    var catalogLink = card.querySelector('a.btn-ghost-cart[href], a[href*="/catalog"]');
    var rawHref = (catalogLink && catalogLink.getAttribute('href')) || '/catalog';
    var catalogBase = rawHref.split('#')[0];
    var spotlightHref = catalogBase + '#catalog-spotlight-heading';
    var homeUrl = '/';
    card.innerHTML =
        '<div class="cart-toolbar">' +
        '<div class="cart-toolbar-text">' +
        '<span class="cart-toolbar-label">Resumen rápido</span>' +
        '</div>' +
        '<div class="cart-toolbar-actions">' +
        '<a href="' + catalogBase + '" class="btn btn-ghost-cart">' +
        '<i class="fas fa-bicycle" aria-hidden="true"></i> Seguir comprando</a>' +
        '</div></div>' +
        '<div class="cart-empty">' +
        '<div class="cart-empty-inner">' +
        '<div class="cart-empty-icon" aria-hidden="true"><i class="fas fa-cart-shopping"></i></div>' +
        '<h2 class="cart-empty-title">Tu carrito está vacío</h2>' +
        '<p class="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>' +
        '<div class="cart-empty-actions">' +
        '<a href="' + catalogBase + '" class="btn btn-primary btn-lg">' +
        '<i class="fas fa-bicycle" aria-hidden="true"></i> Ir al catálogo</a>' +
        '<a href="' + spotlightHref + '" class="btn btn-ghost-cart btn-lg">' +
        '<i class="fas fa-star" aria-hidden="true"></i> Ver destacados</a>' +
        '</div>' +
        '<p class="cart-empty-home-link">' +
        '<a href="' + homeUrl + '" class="cart-empty-home-anchor">Volver al inicio</a></p>' +
        '</div></div>';
}

/** Close login modal. */
function closeLoginModal() {
    closeModal('login-modal');
    var overlay = document.getElementById('login-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

export function initClientCartPage() {
    // Delegated: remove single cart item with confirmation.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-remove-item');
        if (!btn) return;

        var cartItem = btn.closest('.cart-item');
        var itemId   = btn.dataset.productId;
        var itemName = btn.dataset.productName || 'este producto';
        if (!cartItem || !itemId) return;

        fireSwal({
            title: '¿Eliminar producto?',
            text: '¿Deseas eliminar "' + itemName + '" del carrito?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch('/cart/remove/' + itemId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        fireSwal({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar' });
                        return;
                    }

                    cartItem.remove();

                    fireSwal({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Producto eliminado del carrito',
                        showConfirmButton: false, timer: 2500, timerProgressBar: true
                    });

                    // Update the displayed total without reloading.
                    var totalFormatted = (data.cart_total != null)
                        ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                        : '₡0';
                    var subtotalEl = document.getElementById('cart-subtotal');
                    var totalEl    = document.getElementById('cart-total-amount');
                    if (subtotalEl) subtotalEl.textContent = totalFormatted;
                    if (totalEl)    totalEl.textContent    = totalFormatted;

                    updateCartCount(data.cart_count || 0);

                    // If no items remain, show the empty state.
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        showCartEmptyState();
                    }
                })
                .catch(function (err) {
                    console.error('Error removing cart item:', err);
                    fireSwal({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
                });
        });
    });

    // Delegated: clear all cart items with confirmation.
    // Se usa delegación en lugar de un listener directo sobre #btn-clear-cart
    // para que funcione aunque el botón sea regenerado dinámicamente
    // o el click caiga sobre el <i> hijo.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#btn-clear-cart')) return;

        fireSwal({
            title: '¿Vaciar carrito?',
            text: 'Se eliminarán todos los productos del carrito.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch('/cart/clear', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        updateCartCount(0);
                        showCartEmptyState();
                        fireSwal({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Carrito vaciado correctamente',
                            showConfirmButton: false, timer: 2500, timerProgressBar: true
                        });
                    } else {
                        fireSwal({ icon: 'error', title: 'Error', text: data.message || 'No se pudo vaciar el carrito' });
                    }
                })
                .catch(function () {
                    fireSwal({ icon: 'error', title: 'Error', text: 'Ocurrió un error al vaciar el carrito' });
                });
        });
    });

    // Clamp manual quantity input to [1, stock] and sync with server.
    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var productId = this.dataset.productId;
            var quantity  = parseInt(this.value, 10);
            var max       = parseInt(this.max, 10);
            if (quantity < 1) {
                this.value = 1;
                updateCartQuantity(productId, 1);
            } else if (quantity > max) {
                this.value = max;
                fireSwal('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning');
                updateCartQuantity(productId, max);
            } else {
                updateCartQuantity(productId, quantity);
            }
        });
    });

    // +/- stepper buttons for cart page.
    document.querySelectorAll('.quantity-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action    = this.dataset.action;
            var productId = this.dataset.productId;
            var input     = document.querySelector('.quantity-input[data-product-id="' + productId + '"]');
            if (!input) return;
            var quantity = parseInt(input.value, 10);
            var max      = parseInt(input.max, 10);
            if (action === 'increase' && quantity < max) quantity++;
            else if (action === 'decrease' && quantity > 1) quantity--;
            input.value = quantity;
            updateCartQuantity(productId, quantity);
        });
    });

    // Quantity controls on product detail page.
    var productQtyInput = document.getElementById('product-quantity');
    var productQty      = 1;

    if (productQtyInput) {
        var maxQty = parseInt(productQtyInput.max, 10) || 999;

        document.getElementById('decrease-qty') && document.getElementById('decrease-qty').addEventListener('click', function () {
            if (productQty > 1) { productQty--; productQtyInput.value = productQty; }
        });

        document.getElementById('increase-qty') && document.getElementById('increase-qty').addEventListener('click', function () {
            if (productQty < maxQty) { productQty++; productQtyInput.value = productQty; }
        });

        productQtyInput.addEventListener('change', function () {
            var value = parseInt(this.value, 10);
            if (value < 1)       { this.value = 1;      productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else                 { productQty = value; }
        });

        // Add to cart using the quantity from the detail page selector.
        var detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
        if (detailAddBtn) {
            detailAddBtn.addEventListener('click', function () {
                addToCart(this.dataset.productId, productQty, this);
            });
        }
    }

    // Checkout: confirm and submit order.
    var proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            var chosenMethodPreview = getCheckoutPaymentMethod();
            fireSwal({
                title: '¿Confirmar pedido con pago por '
                    + getCf4PaymentMethodShortLabel(chosenMethodPreview) + '?',
                text: 'Se enviará tu pedido para retiro en tienda.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                proceedBtn.disabled  = true;
                proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                fetch('/cart/checkout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ payment_method: getCheckoutPaymentMethod() })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            var cmsg = data.message || 'No se pudo procesar el pedido';
                            var cshort = isClientStockShortMessage(cmsg);
                            fireSwal({
                                icon: cshort ? 'warning' : 'error',
                                title: cshort ? cmsg : 'Error',
                                text: cshort ? '' : cmsg
                            });
                            proceedBtn.disabled  = false;
                            proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                            return;
                        }
                        // Vaciar la UI del carrito inmediatamente tras confirmar.
                        updateCartCount(0);
                        showCartEmptyState();

                        // Increment invoice badge immediately without waiting for heartbeat.
                        (function () {
                            var invoiceLink = document.getElementById('invoices-link');
                            if (!invoiceLink) return;
                            var badge = document.getElementById('invoice-count');
                            var currentVal = badge ? (parseInt(badge.textContent, 10) || 0) : 0;
                            var newVal = currentVal + 1;
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.id = 'invoice-count';
                                badge.className = 'cf4-invoice-count';
                                invoiceLink.appendChild(badge);
                            }
                            badge.textContent = newVal;
                            badge.style.display = 'flex';
                        })();

                        var paidWith = (data && data.payment_method)
                            ? data.payment_method
                            : getCheckoutPaymentMethod();
                        fireSwal({
                            icon: 'success',
                            title: '¡Pedido confirmado!',
                            text: buildCf4CheckoutSuccessText(paidWith),
                            confirmButtonText: 'Entendido'
                        });
                    })
                    .catch(function (err) {
                        console.error('Checkout error:', err);
                        fireSwal({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
                        proceedBtn.disabled  = false;
                        proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                    });
            });
        });
    }

    // ESC closes all modals and dropdowns.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeLoginModal();
        if (typeof window.cf4CloseUserDropdown === 'function') {
            window.cf4CloseUserDropdown();
        }
        document.querySelectorAll('.modal.active').forEach(function (modal) {
            modal.classList.remove('active');
        });
    });

}
