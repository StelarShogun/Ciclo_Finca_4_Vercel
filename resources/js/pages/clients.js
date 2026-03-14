/**
 * clients.js
 * JavaScript for the Ciclo Pérez client interface.
 * Handles cart, modals, and interactive features.
 */

// ============================================================
// GLOBAL UTILITIES
// ============================================================

/** Returns the CSRF token from the meta tag or a visible form input. */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

// ============================================================
// CART COUNTER (navbar)
// ============================================================

function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl  = document.getElementById('cart-link');

    if (cartCountEl) {
        cartCountEl.textContent   = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// ============================================================
// ADD TO CART
// ============================================================

function addToCart(productId, quantity) {
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
            closeModal('add-to-cart-modal');
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo agregar el producto al carrito'
            });
        }
    })
    .catch(function (err) {
        console.error('Error al agregar al carrito:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
    });
}

// ============================================================
// ADD-TO-CART MODAL (catalog & home)
// ============================================================

/** Tracks the product currently being added via the modal. */
var currentProductId = null;

/** Populates and opens the quantity modal from a product card button. */
function openAddToCartModal(btn) {
    currentProductId = btn.dataset.productId;
    var productName  = btn.dataset.productName;
    var productPrice = parseFloat(btn.dataset.productPrice);
    var productStock = parseInt(btn.dataset.productStock, 10);

    var nameEl  = document.getElementById('preview-name');
    var priceEl = document.getElementById('preview-price');
    var stockEl = document.getElementById('preview-stock');
    var qtyEl   = document.getElementById('cart-quantity');

    if (nameEl)  nameEl.textContent  = productName;
    if (priceEl) priceEl.textContent = '₡' + productPrice.toLocaleString('es-CR');
    if (stockEl) stockEl.textContent = 'Stock disponible: ' + productStock;
    if (qtyEl) {
        qtyEl.max   = productStock;
        qtyEl.value = 1;
    }

    // Pull the product image from the nearest card
    var productCard  = btn.closest('.product-card');
    var productImage = productCard ? productCard.querySelector('.product-image img') : null;
    var previewImg   = document.getElementById('preview-image');
    if (previewImg && productImage) {
        previewImg.src = productImage.src;
    }

    openModal('add-to-cart-modal');
}

// ============================================================
// MODAL HELPERS
// ============================================================

function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// ============================================================
// CART PAGE (/cart)
// ============================================================

/** PUTs a quantity change for a single cart item, then reloads. */
function updateCartQuantity(productId, quantity) {
    fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ producto_id: productId, cantidad: quantity })
    })
    .then(function (res) { return res.json().catch(function () { return {}; }); })
    .then(function (data) {
        if (data.success) {
            window.location.reload();
        } else {
            Swal.fire('Error', data.message || 'No se pudo actualizar el carrito', 'error');
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
    var catalogUrl = (card.querySelector('.cart-header a[href]') || {}).href || '/catalog';
    card.innerHTML =
        '<div class="cart-header">' +
            '<h1 class="cart-title"><i class="fas fa-shopping-cart"></i> Carrito de Compras</h1>' +
            '<a href="' + catalogUrl + '" class="btn btn-outline-secondary btn-sm">' +
                '<i class="fas fa-arrow-left"></i> Continuar Comprando</a>' +
        '</div>' +
        '<div class="cart-empty"><div class="empty-state">' +
            '<i class="fas fa-shopping-cart"></i>' +
            '<h2>Tu carrito está vacío</h2>' +
            '<p>Agrega productos desde nuestro catálogo</p>' +
            '<a href="' + catalogUrl + '" class="btn btn-primary btn-lg">' +
                '<i class="fas fa-th"></i> Ver Catálogo</a>' +
        '</div></div>';
}

// ============================================================
// USER MENU (profile dropdown)
// ============================================================

function closeUserDropdown() {
    var userDropdown    = document.getElementById('user-dropdown');
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userDropdown)    userDropdown.classList.remove('active');
    if (userMenuTrigger) userMenuTrigger.setAttribute('aria-expanded', 'false');
}

function toggleUserDropdown() {
    var userDropdown    = document.getElementById('user-dropdown');
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (!userDropdown) return;

    var willOpen = !userDropdown.classList.contains('active');
    if (willOpen) {
        userDropdown.classList.add('active');
        if (userMenuTrigger) userMenuTrigger.setAttribute('aria-expanded', 'true');
    } else {
        closeUserDropdown();
    }
}

// ============================================================
// LOGIN MODAL
// ============================================================

function closeLoginModal() {
    closeModal('login-modal');
    var overlay = document.getElementById('login-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // — Initial cart count —
    var cartLinkEl  = document.getElementById('cart-link');
    var cartGuestEl = document.getElementById('cart-guest');
    var cartRef     = cartLinkEl || cartGuestEl;
    if (cartRef) {
        var initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }

    // — Guest cart: show login prompt —
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

    // — User menu toggle —
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userMenuTrigger) {
        userMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserDropdown();
        });
    }

    // — Close user dropdown on outside click —
    document.addEventListener('click', function (e) {
        var userMenu     = document.getElementById('user-menu');
        var userDropdown = document.getElementById('user-dropdown');
        if (!userDropdown || !userDropdown.classList.contains('active')) return;
        if (userMenu && userMenu.contains(e.target)) return;
        closeUserDropdown();
    });

    // — Login modal —
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

    // — Login form submission —
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            if (!csrfToken) {
                window.location.href = '/login?session_expired=1';
                return;
            }

            var formData    = new FormData(this);
            var submitBtn   = document.getElementById('login-submit-btn');
            var loadingSpan = document.getElementById('login-loading');
            var submitSpan  = submitBtn ? submitBtn.querySelector('span:not(.btn-loading)') : null;

            if (submitBtn)   submitBtn.disabled = true;
            if (submitSpan)  submitSpan.classList.add('hidden');
            if (loadingSpan) loadingSpan.classList.remove('hidden');

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
                // 419 = CSRF expired
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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: data.message || 'Inicio de sesión exitoso',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = data.redirect || '/';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al iniciar sesión' });
                    if (submitBtn)   submitBtn.disabled = false;
                    if (submitSpan)  submitSpan.classList.remove('hidden');
                    if (loadingSpan) loadingSpan.classList.add('hidden');
                }
            })
            .catch(function (err) {
                if (err === 'csrf' || err === 'parse') return;
                console.error('Error en login:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                if (submitBtn)   submitBtn.disabled = false;
                if (submitSpan)  submitSpan.classList.remove('hidden');
                if (loadingSpan) loadingSpan.classList.add('hidden');
            });
        });
    }

    // — Add-to-cart (delegated): open modal or add directly if no modal present —
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            var modal = document.getElementById('add-to-cart-modal');
            if (modal) {
                openAddToCartModal(addBtn);
            } else {
                addToCart(addBtn.dataset.productId, 1);
            }
            return;
        }

        // Guest button: prompt to log in
        if (e.target.closest('.guest-add-btn')) {
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión',
                text: 'Debes iniciar sesión para agregar productos al carrito.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Close modal on backdrop click
        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });

    // — Confirm add-to-cart from modal —
    var confirmAddBtn = document.getElementById('confirm-add-to-cart');
    if (confirmAddBtn) {
        confirmAddBtn.addEventListener('click', function () {
            var qtyEl    = document.getElementById('cart-quantity');
            var quantity = parseInt(qtyEl ? qtyEl.value : '1', 10);
            if (quantity < 1) {
                Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
                return;
            }
            addToCart(currentProductId, quantity);
        });
    }

    var cancelAddBtn = document.getElementById('cancel-add-to-cart');
    if (cancelAddBtn) cancelAddBtn.addEventListener('click', function () { closeModal('add-to-cart-modal'); });

    var closeAddBtn = document.getElementById('close-add-to-cart-modal');
    if (closeAddBtn) closeAddBtn.addEventListener('click', function () { closeModal('add-to-cart-modal'); });

    // — Remove single cart item —
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

                // Update totals without reloading
                var totalFormatted = (data.cart_total != null)
                    ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                    : '₡0';
                var subtotalEl = document.getElementById('cart-subtotal');
                var totalEl    = document.getElementById('cart-total-amount');
                if (subtotalEl) subtotalEl.textContent = totalFormatted;
                if (totalEl)    totalEl.textContent    = totalFormatted;

                updateCartCount(data.cart_count || 0);

                // Switch to empty state if no items remain
                if (document.querySelectorAll('.cart-item').length === 0) {
                    showCartEmptyState();
                }
            })
            .catch(function (err) {
                console.error('Error al eliminar del carrito:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
            });
        });
    });

    // — Clear entire cart —
    var clearCartBtn = document.getElementById('btn-clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function () {
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
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo vaciar el carrito' });
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al vaciar el carrito' });
                });
            });
        });
    }

    // — Quantity input: clamp to [1, stock] on change —
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

    // — +/- quantity buttons (cart page) —
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

    // — Quantity controls on product detail page —
    var productQtyInput = document.getElementById('product-quantity');
    var productQty = 1;

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
            if (value < 1)           { this.value = 1;      productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else                     { productQty = value; }
        });

        // Detail page add-to-cart uses local productQty instead of the modal
        var detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
        if (detailAddBtn) {
            detailAddBtn.addEventListener('click', function () {
                addToCart(this.dataset.productId, productQty);
            });
        }
    }

    // — Checkout confirmation —
    var proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            Swal.fire({
                title: '¿Confirmar compra?',
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo procesar el pedido' });
                        proceedBtn.disabled  = false;
                        proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                        return;
                    }
                    updateCartCount(0);
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido enviado con éxito!',
                        html: 'Su pedido fue enviado con éxito.<br><br>Tiene un lapso de <b>3 días</b> para retirarlo en nuestro local.<br><br>El pago se realiza de forma presencial mediante <b>SINPE, efectivo o tarjeta</b>.',
                        confirmButtonText: 'Entendido'
                    }).then(function () {
                        window.location.reload();
                    });
                })
                .catch(function (err) {
                    console.error('Error en checkout:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
                    proceedBtn.disabled  = false;
                    proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                });
            });
        });
    }

    // — ESC closes all modals and dropdowns —
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeUserDropdown();
        closeLoginModal();
        document.querySelectorAll('.modal.active').forEach(function (modal) {
            modal.classList.remove('active');
        });
    });

}); // end DOMContentLoaded

// ============================================================
// GLOBAL EXPORTS (for use from inline scripts)
// ============================================================
window.addToCart       = addToCart;
window.updateCartCount = updateCartCount;