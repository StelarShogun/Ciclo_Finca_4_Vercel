import Swiper from 'swiper';
import { Navigation, Autoplay, A11y } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/a11y';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from './checkout-copy.js';
import './auth-welcome-toast.js';
import { initHeaderCatalogSearch } from './header-catalog-search.js';

// Marker used by clients-users.js to skip the cart/checkout listeners
// it duplicates. The header (loaded on every page) ships clients-users.js,
// while pages with cart UI also ship this file; without this guard each
// click on .quantity-btn / cart-remove-item / proceed-checkout fires twice.
window.__cf4ClientPageJsLoaded = true;

// ----------------------------------------------------------------
// GLOBAL UTILITIES
// ----------------------------------------------------------------

/** Returns the CSRF token from the meta tag or a hidden form input. */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

/** Mensajes cortos de stock devueltos por el servidor (carrito / checkout). */
function isClientStockShortMessage(msg) {
    return msg === 'Producto agotado' || msg === 'Stock insuficiente';
}

initHeaderCatalogSearch();

// ----------------------------------------------------------------
// CART COUNTER (navbar)
// ----------------------------------------------------------------

/** Update navbar cart badge count. */
function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl  = document.getElementById('cart-link');

    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// ----------------------------------------------------------------
// ADD TO CART
// ----------------------------------------------------------------

/** Read quantity from product card stepper (defaults to 1). */
function readProductCardQuantity(addBtn) {
    if (!addBtn) return 1;
    var root = addBtn.closest('.product-footer') || addBtn.closest('.product-card');
    if (!root) return 1;
    var inp = root.querySelector('.product-card-qty-input');
    if (!inp) return 1;
    var max = parseInt(inp.getAttribute('max'), 10);
    var v = parseInt(String(inp.value || '1').trim(), 10);
    if (isNaN(v) || v < 1) v = 1;
    if (!isNaN(max) && max >= 1 && v > max) v = max;
    return v;
}

function clampProductCardQtyInput(inp) {
    if (!inp) return;
    var max = parseInt(inp.getAttribute('max'), 10);
    var v = parseInt(String(inp.value || '1').trim(), 10);
    if (isNaN(v) || v < 1) v = 1;
    if (!isNaN(max) && max >= 1 && v > max) v = max;
    inp.value = String(v);
}

function resetProductCardQtyAfterAdd(triggerBtn) {
    if (!triggerBtn) return;
    var root = triggerBtn.closest('.product-footer') || triggerBtn.closest('.product-card');
    if (!root) return;
    var inp = root.querySelector('.product-card-qty-input');
    if (inp) inp.value = '1';
}

/** Add product to cart via AJAX. */
function addToCart(productId, quantity, triggerBtn) {
    quantity = quantity || 1;

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.cart_count);
                resetProductCardQtyAfterAdd(triggerBtn);
                Swal.fire({
                    icon: 'success',
                    title: '¡Agregado!',
                    text: data.message || 'Producto agregado al carrito',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                var msg = data.message || 'No se pudo agregar el producto al carrito';
                var stockShort = isClientStockShortMessage(msg);
                Swal.fire({
                    icon: stockShort ? 'warning' : 'error',
                    title: stockShort ? msg : 'Error',
                    text: stockShort ? '' : msg
                });
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
        });
}

/** Syncs favorite button UI state with current value. */
function setFavoriteButtonState(btn, isFavorite) {
    if (!btn) return;
    btn.classList.toggle('is-active', !!isFavorite);
    btn.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
    btn.setAttribute('aria-label', isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos');
    var icon = btn.querySelector('i');
    if (icon) {
        icon.classList.toggle('fas', !!isFavorite);
        icon.classList.toggle('far', !isFavorite);
        icon.classList.add('fa-heart');
    }
}

/** Emits a global event so other UI blocks sync favorites live. */
function notifyFavoriteChange(productId, isFavorite) {
    var payload = {
        product_id: String(productId || ''),
        is_favorite: !!isFavorite
    };
    window.dispatchEvent(new CustomEvent('cf4:favorites:changed', { detail: payload }));
}

/** Toggle product favorite status via AJAX. */
function toggleFavoriteProduct(btn) {
    var cfg = window.catalogFavoriteConfig || {};
    var productId = btn ? btn.getAttribute('data-product-id') : null;
    if (!productId) return;

    if (!cfg.toggleUrl) {
        window.location.href = cfg.loginUrl || '/login';
        return;
    }

    btn.disabled = true;

    fetch(cfg.toggleUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
    })
        .then(function (res) {
            return res.text().then(function (raw) {
                var payload = {};
                try {
                    payload = raw ? JSON.parse(raw) : {};
                } catch (e) {
                    payload = { success: false, message: raw || 'Respuesta inválida del servidor.' };
                }
                if (!res.ok) {
                    payload.success = false;
                }
                return payload;
            });
        })
        .then(function (data) {
            if (!data || data.success !== true) {
                throw new Error((data && data.message) ? data.message : 'No se pudo actualizar favorito');
            }
            var isFavorite = !!data.is_favorite;
            setFavoriteButtonState(btn, isFavorite);
            notifyFavoriteChange(productId, isFavorite);
        })
        .catch(function (err) {
            console.error('Error toggling favorite:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err && err.message ? err.message : 'No se pudo actualizar tu favorito.'
            });
        })
        .finally(function () {
            btn.disabled = false;
        });
}

// ----------------------------------------------------------------
// MODAL HELPERS
// ----------------------------------------------------------------

/** Show modal overlay. */
function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

/** Hide modal overlay. */
function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// ----------------------------------------------------------------
// CART PAGE (/cart)
// ----------------------------------------------------------------

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
                        Swal.fire(
                            'Aviso',
                            'La cantidad se ajustó al stock disponible (' + data.quantity_applied + ' unidades).',
                            'warning'
                        );
                    }
                }
            } else {
                var umsg = data.message || 'No se pudo actualizar el carrito';
                var uShort = isClientStockShortMessage(umsg);
                Swal.fire(uShort ? umsg : 'Error', uShort ? '' : umsg, uShort ? 'warning' : 'error');
            }
        })
        .catch(function () {
            Swal.fire('Error', 'Ocurrió un error al actualizar el carrito', 'error');
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

// ----------------------------------------------------------------
// INITIALIZATION (DOMContentLoaded)
// ----------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {

    // Seed cart badge from server-rendered data.
    var cartLinkEl  = document.getElementById('cart-link');
    var cartGuestEl = document.getElementById('cart-guest');
    var cartRef     = cartLinkEl || cartGuestEl;
    if (cartRef) {
        var initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }

    // Show login prompt for guest cart click.
    if (cartGuestEl) {
        cartGuestEl.addEventListener('click', function () {
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión',
                text: 'Debes iniciar sesión para ver tu carrito.',
                confirmButtonText: 'Entendido'
            });
        });
    }

    // Login modal trigger: redirect to /login page.
    var loginModalTrigger = document.getElementById('login-modal-trigger');
    if (loginModalTrigger) {
        loginModalTrigger.addEventListener('click', function () {
            window.location.href = '/login';
        });
    }

    var closeLoginModalBtn = document.getElementById('close-login-modal');
    if (closeLoginModalBtn) closeLoginModalBtn.addEventListener('click', closeLoginModal);

    var loginModalOverlay = document.getElementById('login-modal-overlay');
    if (loginModalOverlay) loginModalOverlay.addEventListener('click', closeLoginModal);

    // Login form submission via AJAX.
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            // Redirect to login if the CSRF token has expired.
            if (!csrfToken) {
                window.location.href = '/login?session_expired=1';
                return;
            }

            var formData   = new FormData(this);
            var submitBtn  = document.getElementById('login-submit-btn');
            var loadingSpan = document.getElementById('login-loading');
            var submitSpan = submitBtn ? submitBtn.querySelector('span:not(.btn-loading)') : null;

            // Show spinner while waiting for the response.
            if (submitBtn)    submitBtn.disabled = true;
            if (submitSpan)   submitSpan.classList.add('hidden');
            if (loadingSpan)  loadingSpan.classList.remove('hidden');

            fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    // 419 means expired session/CSRF token.
                    if (response.status === 419) {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('csrf');
                    }
                    return response.json().catch(function () {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('parse');
                    });
                })
                .then(function (data) {
                    if (data.success) {
                        if (typeof window.cf4AuthWelcomeToast === 'function') {
                            window.cf4AuthWelcomeToast({
                                kind: 'welcome',
                                authIcon: 'user',
                                displayName: data.display_name || '',
                                thenUrl: data.redirect || '/',
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Bienvenido!',
                                text: data.message || 'Inicio de sesión exitoso',
                                timer: 4000,
                                showConfirmButton: false,
                            }).then(function () {
                                window.location.href = data.redirect || '/';
                            });
                        }
                    } else if (data.redirect) {
                        // Unverified email: offer to send code and redirect to verify.
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Correo no verificado',
                            text: data.message || 'Debes verificar tu correo antes de iniciar sesión.',
                            showCancelButton: true,
                            confirmButtonText: 'Verificar Correo',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2d7a2d',
                            cancelButtonColor: '#6c757d'
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: (data.message || 'Error al iniciar sesión') +
                                '<hr style="margin:12px 0">' +
                                '<p style="font-size:0.9rem;margin:0">¿Tienes una cuenta registrada? ¿O deseas registrarte?</p>',
                            showCancelButton: true,
                            confirmButtonText: 'Ir a Registro',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2d7a2d',
                            cancelButtonColor: '#6c757d',
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                window.location.href = '/register';
                            }
                        });
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                    if (submitBtn)   submitBtn.disabled = false;
                    if (submitSpan)  submitSpan.classList.remove('hidden');
                    if (loadingSpan) loadingSpan.classList.add('hidden');
                });
        });
    }

    // Delegated: product card quantity +/- and add to cart (sin modal).
    document.addEventListener('click', function (e) {
        var qtyBtn = e.target.closest('.product-card-qty-btn');
        if (qtyBtn && qtyBtn.closest('.product-card-qty')) {
            e.preventDefault();
            var wrap = qtyBtn.closest('.product-card-qty');
            var inp = wrap ? wrap.querySelector('.product-card-qty-input') : null;
            if (!inp) return;
            var step = parseInt(qtyBtn.getAttribute('data-qty-step') || '0', 10);
            var max = parseInt(inp.getAttribute('max'), 10);
            var v = parseInt(String(inp.value || '1').trim(), 10);
            if (isNaN(v) || v < 1) v = 1;
            v += step;
            if (v < 1) v = 1;
            if (!isNaN(max) && max >= 1 && v > max) v = max;
            inp.value = String(v);
            return;
        }

        var favoriteBtn = e.target.closest('[data-product-favorite-btn]');
        if (favoriteBtn) {
            e.preventDefault();
            toggleFavoriteProduct(favoriteBtn);
            return;
        }

        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                Swal.fire({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            var qty = readProductCardQuantity(addBtn);
            addToCart(addBtn.dataset.productId, qty, addBtn);
            return;
        }

        var guestBtn = e.target.closest('.guest-add-btn');
        if (guestBtn) {
            if (guestBtn.dataset.purchasable === '0' || parseInt(guestBtn.dataset.productStock, 10) < 1) {
                Swal.fire({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            window.location.href = '/login';
            return;
        }

        // Close modal on backdrop click.
        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });

    // Delegated: remove single cart item with confirmation.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-remove-item');
        if (!btn) return;

        var cartItem = btn.closest('.cart-item');
        var itemId   = btn.dataset.productId;
        var itemName = btn.dataset.productName || 'este producto';
        if (!cartItem || !itemId) return;

        Swal.fire({
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
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar' });
                        return;
                    }

                    cartItem.remove();

                    Swal.fire({
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
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
                });
        });
    });

    // Delegated: clear all cart items with confirmation.
    // Se usa delegación en lugar de un listener directo sobre #btn-clear-cart
    // para que funcione aunque el botón sea regenerado dinámicamente
    // o el click caiga sobre el <i> hijo.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#btn-clear-cart')) return;

        Swal.fire({
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
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Carrito vaciado correctamente',
                            showConfirmButton: false, timer: 2500, timerProgressBar: true
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo vaciar el carrito' });
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al vaciar el carrito' });
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
                Swal.fire('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning');
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
            Swal.fire({
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
                            Swal.fire({
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
                        Swal.fire({
                            icon: 'success',
                            title: '¡Pedido confirmado!',
                            text: buildCf4CheckoutSuccessText(paidWith),
                            confirmButtonText: 'Entendido'
                        });
                    })
                    .catch(function (err) {
                        console.error('Checkout error:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
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

    // Catalog price-range filter validation.
    (function initCatalogPriceFilter() {
        var form      = document.getElementById('filter-form');
        if (!form) return;
        var minInput  = document.getElementById('min_price');
        var maxInput  = document.getElementById('max_price');
        var submitBtn = document.getElementById('filter-submit-btn');

        function checkPriceRange() {
            if (!minInput || !maxInput || !submitBtn) return;
            var min       = parseFloat(minInput.value);
            var max       = parseFloat(maxInput.value);
            var minFilled = minInput.value.trim() !== '';
            var maxFilled = maxInput.value.trim() !== '';
            var negMin    = minFilled && !isNaN(min) && min < 0;
            var negMax    = maxFilled && !isNaN(max) && max < 0;
            var invalid   = negMin || negMax || (minFilled && maxFilled && !isNaN(min) && !isNaN(max) && min > max);
            submitBtn.disabled = invalid;
            if (invalid) {
                submitBtn.setAttribute(
                    'title',
                    negMin || negMax
                        ? 'Los precios no pueden ser negativos.'
                        : 'El precio mínimo debe ser menor o igual al precio máximo.'
                );
            } else {
                submitBtn.removeAttribute('title');
            }
        }

        if (minInput) minInput.addEventListener('input',  checkPriceRange);
        if (minInput) minInput.addEventListener('change', checkPriceRange);
        if (maxInput) maxInput.addEventListener('input',  checkPriceRange);
        if (maxInput) maxInput.addEventListener('change', checkPriceRange);
        checkPriceRange();
    })();

    document.addEventListener('change', function (e) {
        var inp = e.target.closest('.product-card-qty-input');
        if (inp) clampProductCardQtyInput(inp);
    });

    (function initCatalogFilterSearchSync() {
        var filterForm = document.getElementById('filter-form');
        var navSearch = document.getElementById('catalog-nav-search');
        var hiddenSearch = document.getElementById('catalog-filter-search-fallback');
        if (!filterForm) return;

        function syncHiddenFromNav() {
            if (!navSearch || !hiddenSearch) return;
            hiddenSearch.value = String(navSearch.value || '');
        }

        if (navSearch && hiddenSearch) {
            navSearch.addEventListener('input', syncHiddenFromNav);
            navSearch.addEventListener('change', syncHiddenFromNav);
            syncHiddenFromNav();
        }

        filterForm.addEventListener('formdata', function (e) {
            var q = navSearch
                ? String(navSearch.value || '').trim()
                : (hiddenSearch ? String(hiddenSearch.value || '').trim() : '');
            e.formData.set('search', q);
        });
    })();

    (function initCatalogPagination() {
        var wrapper = document.querySelector('.pagination-wrapper .pagination');
        if (!wrapper) return;

        var goInput = wrapper.querySelector('#goToPageInput');
        var goBtn   = wrapper.querySelector('#goToPageBtn');

        wrapper.querySelectorAll('.button[aria-label]').forEach(function (a) {
            if (a.getAttribute('aria-disabled') === 'true') {
                a.addEventListener('click', function (e) { e.preventDefault(); });
                a.classList.add('is-disabled');
            }
        });

        function goToPage() {
            var totalSpan = wrapper.querySelector('.button.button-primary');
            if (!totalSpan) return;
            var parts    = totalSpan.textContent.trim().split('/');
            var lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
            var target   = parseInt((goInput && goInput.value) ? goInput.value.trim() : '1', 10);
            if (isNaN(target)) target = 1;
            if (target < 1)    target = 1;
            if (target > lastPage) target = lastPage;
            var url = new URL(window.location.href);
            url.searchParams.set('page', String(target));
            window.location.assign(url.toString());
        }

        if (goBtn)   goBtn.addEventListener('click', goToPage);
        if (goInput) {
            goInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); goToPage(); }
            });
        }
    })();

    // Home: carrusel de categorías (padres + chips de subcategorías).
    (function initCategoriesCarousel() {
        var wrap  = document.querySelector('[data-categories-carousel]');
        if (!wrap) return;
        var track = wrap.querySelector('[data-carousel-track]');
        var prev  = wrap.querySelector('[data-carousel-prev]');
        var next  = wrap.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;

        function getStep() {
            var first = track.querySelector('.category-slide');
            if (!first) return Math.max(120, track.clientWidth * 0.85);
            var gap = parseInt(getComputedStyle(track).gap, 10);
            if (isNaN(gap)) gap = 18;
            return first.getBoundingClientRect().width + gap;
        }

        function updateButtons() {
            var maxScroll = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll;
        }

        prev.addEventListener('click', function () { track.scrollBy({ left: -getStep(), behavior: 'smooth' }); });
        next.addEventListener('click', function () { track.scrollBy({ left:  getStep(), behavior: 'smooth' }); });
        track.addEventListener('scroll',  function () { window.requestAnimationFrame(updateButtons); });
        window.addEventListener('resize', function () { updateButtons(); });
        updateButtons();
    })();

    // ----------------------------------------------------------------
    // FIX: Catalog filter toggle — usa clase .open en vez de style.display
    // ----------------------------------------------------------------
    (function initCatalogFilterToggle() {
        var btn     = document.getElementById('catalog-filter-toggle');
        var sidebar = document.getElementById('catalog-sidebar');
        if (!btn || !sidebar) return;

        function checkMobile() {
            if (window.innerWidth <= 1024) {
                btn.style.display = 'flex';
                // Si no está expandido, asegurar que .open no esté
                if (btn.getAttribute('aria-expanded') !== 'true') {
                    sidebar.classList.remove('open');
                }
            } else {
                btn.style.display = 'none';
                // En desktop siempre visible: agregar .open para que el CSS lo muestre
                sidebar.classList.add('open');
            }
        }

        btn.addEventListener('click', function () {
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!open));
            // Toggle la clase .open que el CSS usa para max-height/opacity
            sidebar.classList.toggle('open', !open);
            var caret = btn.querySelector('.fa-chevron-down');
            if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
            var label = btn.querySelector('span');
            if (label) label.textContent = open ? 'Mostrar filtros' : 'Ocultar filtros';
        });

        checkMobile();
        window.addEventListener('resize', checkMobile);
    })();

    // Catálogo: panel + sidebar categorías (hover desktop, tap móvil).
    (function initCatalogCategoryUi() {
        var panel = document.getElementById('catalog-category-panel');
        var sidebar = document.getElementById('catalog-category-sidebar');
        var dataEl = document.getElementById('catalog-category-tree-data');
        var tree = [];
        try {
            tree = dataEl && dataEl.textContent ? JSON.parse(dataEl.textContent) : [];
        } catch (e) {
            tree = [];
        }
        if (!panel && !sidebar) return;

        function esc(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function hrefAttr(url) {
            return String(url).replace(/"/g, '%22');
        }

        function isDesktop() {
            return window.matchMedia && window.matchMedia('(min-width: 1024px)').matches;
        }

        function parseDelayMs(el, fallback) {
            if (!el) return fallback;
            var raw = el.getAttribute('data-close-delay-ms');
            var n = parseInt(raw, 10);
            return !isNaN(n) && n >= 0 ? n : fallback;
        }

        function resolveParentForActive(activeId) {
            var id = parseInt(activeId, 10);
            if (!id) return null;
            for (var i = 0; i < tree.length; i++) {
                var p = tree[i];
                if (p.id === id) return p;
                var ch = p.children || [];
                for (var j = 0; j < ch.length; j++) {
                    if (ch[j].id === id) return p;
                }
            }
            return null;
        }

        function activeChildIdIfAny(activeId, parentNode) {
            var id = parseInt(activeId, 10);
            if (!id || !parentNode) return null;
            var ch = parentNode.children || [];
            for (var j = 0; j < ch.length; j++) {
                if (ch[j].id === id) return id;
            }
            return null;
        }

        function parentNodeById(pid) {
            for (var i = 0; i < tree.length; i++) {
                if (tree[i].id === pid) return tree[i];
            }
            return null;
        }

        /* ---------- Panel superior ---------- */
        if (panel) {
            var backdrop = document.getElementById('catalog-category-backdrop');
            var trigger = document.getElementById('catalog-category-trigger');
            var closeBtn = document.getElementById('catalog-category-close');
            var subCol = document.getElementById('catalog-category-subcolumn');
            var hoverRoot = panel.querySelector('[data-catalog-panel-hover-root]');
            var panelDelay = parseDelayMs(panel, 150);
            var panelLeaveTimer = null;

            function clearPanelLeaveTimer() {
                if (panelLeaveTimer) {
                    clearTimeout(panelLeaveTimer);
                    panelLeaveTimer = null;
                }
            }

            function setParentRowsHovered(parentId) {
                panel.querySelectorAll('.catalog-category-parent-row').forEach(function (row) {
                    var pid = parseInt(row.getAttribute('data-parent-id'), 10);
                    row.classList.toggle('is-hovered', !!parentId && pid === parentId);
                });
            }

            function renderSubcolumn(parentNode, highlightChildId) {
                if (!subCol) return;
                if (!parentNode) {
                    subCol.innerHTML = '<p class="catalog-category-placeholder">Pasá el cursor sobre una categoría para ver subcategorías.</p>';
                    return;
                }
                var html = '';
                var ch = parentNode.children || [];
                ch.forEach(function (c) {
                    var cls = highlightChildId && c.id === highlightChildId
                        ? 'catalog-category-sub-link is-active'
                        : 'catalog-category-sub-link';
                    html += '<a class="' + cls + '" href="' + hrefAttr(c.url) + '">' + esc(c.name) + '</a>';
                });
                subCol.innerHTML = html;
            }

            function selectParent(parentNode, highlightChildId) {
                setParentRowsHovered(parentNode ? parentNode.id : null);
                renderSubcolumn(parentNode, highlightChildId || null);
            }

            function syncFromUrl() {
                if (!isDesktop()) return;
                var activeRaw = panel.getAttribute('data-active-category-id');
                var p = resolveParentForActive(activeRaw);
                var childHi = activeChildIdIfAny(activeRaw, p);
                if (p) {
                    selectParent(p, childHi);
                } else {
                    setParentRowsHovered(null);
                    renderSubcolumn(null, null);
                }
            }

            function scheduleClearPanelHover() {
                clearPanelLeaveTimer();
                panelLeaveTimer = setTimeout(function () {
                    panelLeaveTimer = null;
                    setParentRowsHovered(null);
                    renderSubcolumn(null, null);
                }, panelDelay);
            }

            function openPanel() {
                panel.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
                panel.setAttribute('aria-hidden', 'false');
                if (backdrop) backdrop.setAttribute('aria-hidden', 'false');
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
                document.body.classList.add('catalog-category-panel-open');
                if (isDesktop()) syncFromUrl();
                else if (subCol) {
                    subCol.innerHTML = '<p class="catalog-category-placeholder">Expandí una categoría para ver subcategorías.</p>';
                }
            }

            function closePanel() {
                clearPanelLeaveTimer();
                panel.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                if (backdrop) backdrop.setAttribute('aria-hidden', 'true');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('catalog-category-panel-open');
            }

            function togglePanel() {
                if (panel.classList.contains('is-open')) closePanel();
                else openPanel();
            }

            if (hoverRoot && subCol) {
                hoverRoot.addEventListener('mouseenter', function () {
                    clearPanelLeaveTimer();
                });
                hoverRoot.addEventListener('mouseleave', function () {
                    if (!isDesktop()) return;
                    scheduleClearPanelHover();
                });

                panel.querySelectorAll('.catalog-category-parent-row').forEach(function (row) {
                    row.addEventListener('mouseenter', function () {
                        if (!isDesktop()) return;
                        clearPanelLeaveTimer();
                        var pid = parseInt(row.getAttribute('data-parent-id'), 10);
                        var pnode = parentNodeById(pid);
                        if (!pnode) return;
                        var activeRaw = panel.getAttribute('data-active-category-id');
                        var hi = activeChildIdIfAny(activeRaw, pnode);
                        selectParent(pnode, hi);
                    });
                });
            }

            panel.querySelectorAll('.catalog-category-panel-mobile-expand').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isDesktop()) return;
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    var id = btn.getAttribute('aria-controls');
                    var block = id ? document.getElementById(id) : null;
                    btn.setAttribute('aria-expanded', String(!open));
                    if (block) block.hidden = open;
                });
            });

            if (trigger) {
                trigger.addEventListener('click', function (e) {
                    if (isDesktop()) return;
                    e.stopPropagation();
                    togglePanel();
                });
            }
            if (closeBtn) closeBtn.addEventListener('click', function () { closePanel(); });
            if (backdrop) backdrop.addEventListener('click', function () { closePanel(); });

            document.addEventListener('click', function (ev) {
                if (!panel.classList.contains('is-open')) return;
                var t = ev.target;
                if (panel.contains(t)) return;
                if (trigger && trigger.contains(t)) return;
                closePanel();
            });

            document.addEventListener('keydown', function (ev) {
                if (!panel.classList.contains('is-open')) return;
                if (ev.key === 'Escape') {
                    ev.preventDefault();
                    closePanel();
                }
            });

            if (isDesktop()) syncFromUrl();

            window.addEventListener('resize', function () {
                if (isDesktop() && panel.classList.contains('is-open')) closePanel();
            });
        }

        /* ---------- Sidebar categorías ---------- */
        if (sidebar) {
            var sbDelay = parseDelayMs(sidebar, 150);
            var sbLeaveTimer = null;
            var flyoutPortalEl = null;
            var activeSidebarItem = null;
            var portalGlobalBound = false;
            var portalRepositionRaf = null;

            function clampNumber(value, min, max) {
                return Math.max(min, Math.min(max, value));
            }

            function ensureSidebarFlyoutPortal() {
                if (!flyoutPortalEl) {
                    flyoutPortalEl = document.getElementById('catalog-category-flyout-portal');
                    if (!flyoutPortalEl) {
                        flyoutPortalEl = document.createElement('div');
                        flyoutPortalEl.id = 'catalog-category-flyout-portal';
                        flyoutPortalEl.className = 'catalog-category-flyout-portal';
                        flyoutPortalEl.setAttribute('aria-hidden', 'true');
                        document.body.appendChild(flyoutPortalEl);
                    }

                    flyoutPortalEl.addEventListener('mouseenter', function () {
                        clearSbTimer();
                    });
                    flyoutPortalEl.addEventListener('mouseleave', function () {
                        scheduleSidebarFlyoutClose();
                    });
                }

                if (!portalGlobalBound) {
                    portalGlobalBound = true;

                    function schedulePortalReposition() {
                        if (!activeSidebarItem || !flyoutPortalEl || !flyoutPortalEl.classList.contains('is-open')) return;
                        if (portalRepositionRaf) return;
                        portalRepositionRaf = window.requestAnimationFrame(function () {
                            portalRepositionRaf = null;
                            if (activeSidebarItem && flyoutPortalEl && flyoutPortalEl.classList.contains('is-open')) {
                                positionSidebarFlyoutPortal(activeSidebarItem);
                            }
                        });
                    }

                    window.addEventListener('scroll', schedulePortalReposition, true);
                    var railScroll = sidebar.querySelector('.category-rail-scroll');
                    if (railScroll) {
                        railScroll.addEventListener('scroll', schedulePortalReposition, true);
                    }
                    var sidebarStack = sidebar.closest('.catalog-sidebar-stack');
                    if (sidebarStack) {
                        sidebarStack.addEventListener('scroll', schedulePortalReposition, true);
                    }

                    window.addEventListener('resize', function () {
                        if (!isDesktop()) {
                            closeSidebarFlyoutPortal();
                            return;
                        }
                        if (activeSidebarItem && flyoutPortalEl && flyoutPortalEl.classList.contains('is-open')) {
                            positionSidebarFlyoutPortal(activeSidebarItem);
                        }
                    });

                    document.addEventListener('keydown', function (ev) {
                        if (ev.key !== 'Escape') return;
                        var p = document.getElementById('catalog-category-flyout-portal');
                        if (p && p.classList.contains('is-open')) {
                            ev.preventDefault();
                            closeSidebarFlyoutPortal();
                        }
                    });
                }

                return flyoutPortalEl;
            }

            function clearSbTimer() {
                if (sbLeaveTimer) {
                    clearTimeout(sbLeaveTimer);
                    sbLeaveTimer = null;
                }
            }

            function closeSidebarFlyoutPortal() {
                clearSbTimer();
                if (activeSidebarItem) {
                    activeSidebarItem.classList.remove('is-flyout-open');
                    var fo = activeSidebarItem.querySelector('.catalog-category-flyout');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                }
                activeSidebarItem = null;
                var portal = document.getElementById('catalog-category-flyout-portal');
                if (portal) {
                    portal.classList.remove('is-open');
                    portal.setAttribute('aria-hidden', 'true');
                    portal.innerHTML = '';
                    portal.style.left = '';
                    portal.style.top = '';
                    portal.style.visibility = '';
                }
            }

            function closeAllSidebarFlyouts() {
                closeSidebarFlyoutPortal();
                sidebar.querySelectorAll('.catalog-category-sidebar-item.is-flyout-open').forEach(function (el) {
                    el.classList.remove('is-flyout-open');
                    var fo = el.querySelector('.catalog-category-flyout');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                });
            }

            function positionSidebarFlyoutPortal(item) {
                var portal = ensureSidebarFlyoutPortal();
                var row = item.querySelector('.catalog-category-sidebar-item-row') || item;
                var rect = row.getBoundingClientRect();
                var gap = 12;
                var viewportPadding = 12;

                portal.style.visibility = 'hidden';
                portal.classList.add('is-open');

                var portalRect = portal.getBoundingClientRect();
                var left = rect.right + gap;
                var top = rect.top;

                if (left + portalRect.width > window.innerWidth - viewportPadding) {
                    left = rect.left - portalRect.width - gap;
                }

                left = clampNumber(left, viewportPadding, window.innerWidth - portalRect.width - viewportPadding);
                top = clampNumber(
                    top,
                    viewportPadding,
                    window.innerHeight - portalRect.height - viewportPadding
                );

                portal.style.left = left + 'px';
                portal.style.top = top + 'px';
                portal.style.visibility = 'visible';
            }

            function openSidebarFlyoutPortal(item) {
                if (!isDesktop()) return;

                var sourceFlyout = item.querySelector('.catalog-category-flyout');
                if (!sourceFlyout) return;

                clearSbTimer();
                closeAllSidebarFlyouts();

                activeSidebarItem = item;
                item.classList.add('is-flyout-open');
                sourceFlyout.setAttribute('aria-hidden', 'false');

                var portal = ensureSidebarFlyoutPortal();
                portal.innerHTML = sourceFlyout.innerHTML;
                portal.setAttribute('aria-hidden', 'false');

                positionSidebarFlyoutPortal(item);
            }

            function scheduleSidebarFlyoutClose() {
                clearSbTimer();
                sbLeaveTimer = setTimeout(function () {
                    sbLeaveTimer = null;
                    closeSidebarFlyoutPortal();
                }, sbDelay);
            }

            sidebar.querySelectorAll('.catalog-category-sidebar-item[data-has-children="1"]').forEach(function (item) {
                item.addEventListener('mouseenter', function () {
                    if (!isDesktop()) return;
                    openSidebarFlyoutPortal(item);
                });

                item.addEventListener('mouseleave', function () {
                    if (!isDesktop()) return;
                    scheduleSidebarFlyoutClose();
                });

                item.addEventListener('focusin', function () {
                    if (!isDesktop()) return;
                    openSidebarFlyoutPortal(item);
                });

                item.addEventListener('focusout', function (ev) {
                    if (!isDesktop()) return;
                    var rt = ev.relatedTarget;
                    var portal = document.getElementById('catalog-category-flyout-portal');
                    if (portal && rt && portal.contains(rt)) return;
                    scheduleSidebarFlyoutClose();
                });
            });

            sidebar.querySelectorAll('.catalog-category-mobile-expand').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isDesktop()) return;
                    var item = btn.closest('.catalog-category-sidebar-item');
                    if (!item) return;
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    var id = btn.getAttribute('aria-controls');
                    var block = id ? document.getElementById(id) : null;
                    var nextOpen = !open;
                    btn.setAttribute('aria-expanded', String(nextOpen));
                    item.classList.toggle('is-mobile-open', nextOpen);
                    if (block) block.setAttribute('aria-hidden', String(!nextOpen));
                });
            });

            sidebar.addEventListener('keydown', function (ev) {
                if (ev.key !== 'Escape') return;
                closeAllSidebarFlyouts();
                sidebar.querySelectorAll('.catalog-category-sidebar-item.is-mobile-open').forEach(function (item) {
                    item.classList.remove('is-mobile-open');
                    var fo = item.querySelector('.catalog-category-flyout');
                    var btn = item.querySelector('.catalog-category-mobile-expand');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            });

            /* Toggle persistente del rail: en desktop el rail expandido tapa filtros (CSS capa); inert evita foco/clic en filtros. */
            var railToggle = document.getElementById('catalog-category-sidebar-toggle');
            var filtersAside = document.getElementById('catalog-sidebar');

            function syncRailExpandedForViewport() {
                if (isDesktop()) return;
                if (sidebar.classList.contains('is-expanded')) {
                    sidebar.classList.remove('is-expanded');
                    var cc = sidebar.closest('.catalog-container');
                    if (cc) cc.classList.remove('rail-expanded');
                    if (railToggle) {
                        railToggle.setAttribute('aria-expanded', 'false');
                        railToggle.setAttribute('aria-label', 'Expandir menú de categorías');
                    }
                }
                if (filtersAside) filtersAside.removeAttribute('inert');
            }

            window.addEventListener('resize', syncRailExpandedForViewport);

            if (railToggle) {
                railToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var expanded = sidebar.classList.toggle('is-expanded');
                    var catalogContainer = sidebar.closest('.catalog-container');
                    if (catalogContainer) {
                        catalogContainer.classList.toggle('rail-expanded', expanded);
                    }
                    if (filtersAside) {
                        if (expanded) filtersAside.setAttribute('inert', '');
                        else filtersAside.removeAttribute('inert');
                    }
                    railToggle.setAttribute('aria-expanded', String(expanded));
                    railToggle.setAttribute(
                        'aria-label',
                        expanded ? 'Contraer menú de categorías' : 'Expandir menú de categorías'
                    );
                    var portalNode = document.getElementById('catalog-category-flyout-portal');
                    if (activeSidebarItem && portalNode && portalNode.classList.contains('is-open')) {
                        positionSidebarFlyoutPortal(activeSidebarItem);
                    }
                });
            }
        }
    })();

    // Home: reveal progresivo de secciones al hacer scroll.
    (function initHomeRevealSections() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var sections = document.querySelectorAll(
            '.home-trust-strip, .featured-section, .categories-section, .benefits-section, .how-it-works-section, .testimonials-section, .final-cta-section'
        );
        if (!sections.length) return;

        sections.forEach(function (section) { section.classList.add('home-reveal'); });

        if (!('IntersectionObserver' in window)) {
            sections.forEach(function (section) { section.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.14
        });

        sections.forEach(function (section) { observer.observe(section); });
    })();

    // ---- Product image carousel ----
    (function initProductCarousel() {
        var track = document.getElementById('carousel-track');
        if (!track) return;
        var slides  = track.querySelectorAll('.carousel-slide');
        var total   = slides.length;
        if (total <= 1) return;
        var prevBtn = document.getElementById('carousel-prev');
        var nextBtn = document.getElementById('carousel-next');
        var dots    = document.querySelectorAll('.carousel-dot');
        var current = 0;

        function goTo(index) {
            current = Math.max(0, Math.min(total - 1, index));
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
            if (prevBtn) prevBtn.disabled = current === 0;
            if (nextBtn) nextBtn.disabled = current === total - 1;
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });

        // Swipe support (touch devices)
        var startX = null;
        track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
            startX = null;
        }, { passive: true });

        // Keyboard arrow navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        });

        goTo(0);
    })();

    // ---- Catalog spotlight carousel (Swiper) ----
    // Renders featured + novelty products with autoplay, navigation arrows
    // and mobile swipe. Markup lives in catalog.blade.php behind
    // `[data-catalog-spotlight-carousel]` and is hidden by Blade when any
    // filter is active or the user is past page 1 of pagination.
    (function initCatalogSpotlightCarousel() {
        var root = document.querySelector('[data-catalog-spotlight-carousel]');
        if (!root) return;

        var swiperEl = root.querySelector('.swiper');
        var prevBtn = root.querySelector('[data-spotlight-prev]');
        var nextBtn = root.querySelector('[data-spotlight-next]');
        if (!swiperEl) return;

        var wrapperEl = swiperEl.querySelector('.swiper-wrapper');
        var slides = swiperEl.querySelectorAll('.swiper-slide');
        if (!slides.length) return;

        // Swiper's loop mode requires enough real slides to clone seamlessly.
        // With slidesPerView up to 3 on desktop we need at least 6 slides
        // available; otherwise loop silently disables itself and autoplay
        // gets stuck at the last slide. We duplicate slides as needed.
        var maxSlidesPerView = 3;
        var minSlidesForLoop = maxSlidesPerView * 2;

        if (wrapperEl && slides.length > 1 && slides.length < minSlidesForLoop) {
            var originalSlides = Array.prototype.slice.call(slides);
            while (wrapperEl.querySelectorAll('.swiper-slide').length < minSlidesForLoop) {
                originalSlides.forEach(function (slide) {
                    wrapperEl.appendChild(slide.cloneNode(true));
                });
            }
            slides = swiperEl.querySelectorAll('.swiper-slide');
        }

        var delay = parseInt(root.getAttribute('data-autoplay-delay'), 10);
        if (!Number.isFinite(delay) || delay <= 0) delay = 4000;

        // Autoplay runs continuously for everyone. We do not use
        // pauseOnMouseEnter: it attaches pointerenter/pointerleave on the Swiper
        // root and in some browsers/layouts the carousel can appear stuck until
        // the cursor moves over the track again.
        var autoplayOption = {
            enabled: true,
            delay: delay,
            disableOnInteraction: false,
            pauseOnMouseEnter: false,
        };

        // Circular (infinite) navigation. Real slide count is guaranteed
        // to be enough above; we keep loopAdditionalSlides at 0 so Swiper
        // computes the minimum required duplicates per breakpoint.
        var enableLoop = slides.length > 1;

        try {
            new Swiper(swiperEl, {
                modules: [Navigation, Autoplay, A11y],
                slidesPerView: 1,
                spaceBetween: 18,
                centeredSlides: false,
                loop: enableLoop,
                loopAdditionalSlides: 0,
                speed: 600,
                grabCursor: true,
                watchOverflow: true,
                autoplay: autoplayOption,
                navigation: {
                    prevEl: prevBtn,
                    nextEl: nextBtn,
                    disabledClass: 'swiper-button-disabled',
                },
                a11y: {
                    prevSlideMessage: 'Producto destacado anterior',
                    nextSlideMessage: 'Siguiente producto destacado',
                    slideLabelMessage: '{{index}} de {{slidesLength}}',
                },
                breakpoints: {
                    640: { slidesPerView: 2, spaceBetween: 16 },
                    1024: { slidesPerView: 3, spaceBetween: 18 },
                    1280: { slidesPerView: 4, spaceBetween: 20 },
                    1680: { slidesPerView: 5, spaceBetween: 22 },
                },
            });
        } catch (err) {
            // Fail silently — carousel is enhancement; the page already shows the cards.
            if (typeof console !== 'undefined' && console.error) {
                console.error('Catalog spotlight carousel failed to init:', err);
            }
        }
    })();

}); // end DOMContentLoaded

// ----------------------------------------------------------------
// GLOBAL EXPORTS (for use from inline scripts)
// ----------------------------------------------------------------

window.addToCart       = addToCart;
window.updateCartCount = updateCartCount;