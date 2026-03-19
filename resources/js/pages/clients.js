/**
 * clients.js
 * JavaScript for the Ciclo Pérez client interface.
 * Handles cart, modals, and interactive features.
 */

// ============================================================
// GLOBAL UTILITIES
// ============================================================

/** Returns the CSRF token from the meta tag or a hidden form input. */
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
    const cartLinkEl = document.getElementById('cart-link');

    if (cartCountEl) {
        cartCountEl.textContent = count;
        // Hide the badge when the cart is empty
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
            console.error('Error adding to cart:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
        });
}

// ============================================================
// ADD-TO-CART MODAL (catalog & home)
// ============================================================

/** Product currently being added via the quantity modal. */
var currentProductId = null;

/** Populates and opens the quantity modal from a product card button. */
function openAddToCartModal(btn) {
    currentProductId = btn.dataset.productId;
    var productName = btn.dataset.productName;
    var productPrice = parseFloat(btn.dataset.productPrice);
    var productStock = parseInt(btn.dataset.productStock, 10);

    var nameEl = document.getElementById('preview-name');
    var priceEl = document.getElementById('preview-price');
    var stockEl = document.getElementById('preview-stock');
    var qtyEl = document.getElementById('cart-quantity');

    if (nameEl) nameEl.textContent = productName;
    if (priceEl) priceEl.textContent = '₡' + productPrice.toLocaleString('es-CR');
    if (stockEl) stockEl.textContent = 'Stock disponible: ' + productStock;
    if (qtyEl) {
        qtyEl.max = productStock;
        qtyEl.value = 1;
    }

    // Pull the product image from the nearest card
    var productCard = btn.closest('.product-card');
    var productImage = productCard ? productCard.querySelector('.product-image img') : null;
    var previewImg = document.getElementById('preview-image');
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
    var userDropdown = document.getElementById('user-dropdown');
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userDropdown) userDropdown.classList.remove('active');
    if (userMenuTrigger) userMenuTrigger.setAttribute('aria-expanded', 'false');
}

function toggleUserDropdown() {
    var userDropdown = document.getElementById('user-dropdown');
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

    // — Initialise cart counter from the data attribute —
    var cartLinkEl = document.getElementById('cart-link');
    var cartGuestEl = document.getElementById('cart-guest');
    var cartRef = cartLinkEl || cartGuestEl;
    if (cartRef) {
        var initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }

    // — Guest cart: prompt to log in —
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
        var userMenu = document.getElementById('user-menu');
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

    // — Login form submission via AJAX —
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            // Redirect to login if the CSRF token has expired
            if (!csrfToken) {
                window.location.href = '/login?session_expired=1';
                return;
            }

            var formData = new FormData(this);
            var submitBtn = document.getElementById('login-submit-btn');
            var loadingSpan = document.getElementById('login-loading');
            var submitSpan = submitBtn ? submitBtn.querySelector('span:not(.btn-loading)') : null;

            // Show spinner while waiting for the response
            if (submitBtn) submitBtn.disabled = true;
            if (submitSpan) submitSpan.classList.add('hidden');
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
                    // 419 means expired session/CSRF token
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
                        if (submitBtn) submitBtn.disabled = false;
                        if (submitSpan) submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                    if (submitBtn) submitBtn.disabled = false;
                    if (submitSpan) submitSpan.classList.remove('hidden');
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

        // Guest: prompt to log in
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
            var qtyEl = document.getElementById('cart-quantity');
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
        var itemId = btn.dataset.productId;
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

                    // Update the displayed total without reloading
                    var totalFormatted = (data.cart_total != null)
                        ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                        : '₡0';
                    var subtotalEl = document.getElementById('cart-subtotal');
                    var totalEl = document.getElementById('cart-total-amount');
                    if (subtotalEl) subtotalEl.textContent = totalFormatted;
                    if (totalEl) totalEl.textContent = totalFormatted;

                    updateCartCount(data.cart_count || 0);

                    // If no items remain, show the empty state
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
            var quantity = parseInt(this.value, 10);
            var max = parseInt(this.max, 10);
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
            var action = this.dataset.action;
            var productId = this.dataset.productId;
            var input = document.querySelector('.quantity-input[data-product-id="' + productId + '"]');
            if (!input) return;
            var quantity = parseInt(input.value, 10);
            var max = parseInt(input.max, 10);
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
            if (value < 1) { this.value = 1; productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else { productQty = value; }
        });

        // Add to cart using the quantity from the detail page selector
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

                proceedBtn.disabled = true;
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
                            proceedBtn.disabled = false;
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
                        console.error('Checkout error:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
                        proceedBtn.disabled = false;
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
window.addToCart = addToCart;
window.updateCartCount = updateCartCount;

// ============================================================
// USER MENU DROPDOWN (header)
// ============================================================

/** Opens or closes the user menu, syncing the class, aria-hidden and aria-expanded. */
function setUserMenuOpen(open) {
    var wrap = document.getElementById('user-menu');
    var panel = document.getElementById('user-dropdown');
    var trigger = document.getElementById('user-menu-trigger');
    if (!wrap) return;
    wrap.classList.toggle('open', open);
    if (panel) panel.setAttribute('aria-hidden', String(!open));
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
}

// ============================================================
// MY PROFILE PAGE (profile.blade.php)
// ============================================================

var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

/** Saves current field values so the cancel action can restore them. */
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

/** Enables form fields and switches the header button to save mode. */
function enableEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.removeAttribute('readonly');
    });

    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        btn.className = 'btn btn-sm btn-primary';
        btn.setAttribute('onclick', 'submitProfile()');
    }

    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.remove('hidden');

    // Focus the first editable field
    var nameField = document.getElementById('name');
    if (nameField) nameField.focus();
}

/** Restores original values and switches back to read-only mode. */
function cancelEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.setAttribute('readonly', true);
            el.value = profileOriginalValues[id];
        }
    });

    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
        btn.className = 'btn btn-sm btn-outline-primary';
        btn.setAttribute('onclick', 'enableEdit()');
    }

    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.add('hidden');
}

/** Shows a confirmation dialog then submits the profile form via AJAX. */
function submitProfile() {
    var form = document.getElementById('formPerfil');
    if (!form) return;

    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán tus datos personales.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;
        sendProfile(form);
    });
}

/** Performs the profile fetch request (called after confirmation). */
function sendProfile(form) {

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            // 422: server-side validation errors
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success) {
                // Persist the new values and lock fields back to read-only
                profileEditableFields.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        profileOriginalValues[id] = el.value;
                        el.setAttribute('readonly', true);
                    }
                });

                // Update the hero section: full name, avatar initials and email
                var name = (document.getElementById('name') || {}).value || '';
                var fs = (document.getElementById('first_surname') || {}).value || '';
                var ss = (document.getElementById('second_surname') || {}).value || '';
                var gmail = (document.getElementById('gmail') || {}).value || '';

                var heroName = document.getElementById('heroName');
                var initials = document.getElementById('avatarInitials');
                var heroEmail = document.querySelector('.profile-email');

                if (heroName) heroName.textContent = [name, fs, ss].filter(Boolean).join(' ');
                if (initials) initials.textContent = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (heroEmail) heroEmail.textContent = gmail;

                // Update the header/navbar without reloading
                var headerInitials = document.querySelector('.user-avatar-bubble');
                var headerShortName = document.querySelector('.user-trigger-name');
                var headerFullName = document.querySelector('.user-dropdown-fullname');
                var headerEmail = document.querySelector('.user-dropdown-email');

                if (headerInitials) headerInitials.textContent = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (headerShortName) headerShortName.textContent = name;
                if (headerFullName) headerFullName.textContent = [name, fs].filter(Boolean).join(' ');
                if (headerEmail) headerEmail.textContent = gmail;

                // Reset the edit button to its default state
                var btn = document.getElementById('btnEditarPerfil');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
                    btn.className = 'btn btn-sm btn-outline-primary';
                    btn.setAttribute('onclick', 'enableEdit()');
                }
                var actions = document.getElementById('accionesEdicion');
                if (actions) actions.classList.add('hidden');

                showProfileAlert(res.message || 'Cambios guardados correctamente', 'success');
            } else {
                showProfileAlert(res.message || 'Error al guardar los cambios.', 'danger');
            }
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        });
} // end sendProfile

/** Toggles password field visibility. */
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn ? btn.querySelector('i') : null;
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/** Updates the password-strength bar based on length, uppercase, digits and symbols. */
function updateStrength(val) {
    var wrapper = document.getElementById('passStrength');
    var fill = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');

    if (!wrapper) return;

    if (!val) { wrapper.classList.add('hidden'); return; }
    wrapper.classList.remove('hidden');

    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { w: '25%', c: '#d32f2f', t: 'Débil' },
        { w: '50%', c: '#f57c00', t: 'Regular' },
        { w: '75%', c: '#fbc02d', t: 'Buena' },
        { w: '100%', c: '#2e7d32', t: 'Fuerte' }
    ];
    var lvl = levels[Math.max(score - 1, 0)];
    if (fill) { fill.style.width = lvl.w; fill.style.background = lvl.c; }
    if (label) { label.textContent = lvl.t; label.style.color = lvl.c; }
}

/** Shows the password-definition form for Google-only accounts. */
function showPasswordForm() {
    var form = document.getElementById('formPassword');
    var cta = document.getElementById('googlePassCta');
    if (form) form.classList.remove('hidden');
    if (cta) cta.classList.add('hidden');
}

/** Hides the password-definition form and re-shows the CTA. */
function hidePasswordForm() {
    var form = document.getElementById('formPassword');
    var cta = document.getElementById('googlePassCta');
    if (form) form.classList.add('hidden');
    if (cta) cta.classList.remove('hidden');
}

/** Shows a dismissible alert on the profile page; auto-closes after 5 seconds. */
function showProfileAlert(msg, tipo) {
    var alertEl = document.getElementById('profileAlert');
    var textEl = document.getElementById('profileAlertText');
    var iconEl = document.getElementById('profileAlertIcon');
    if (!alertEl) return;

    textEl.textContent = msg;
    alertEl.className = 'alert ' + (tipo === 'danger' ? 'alert-danger' : 'alert-success');
    iconEl.className = tipo === 'danger' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';

    alertEl.classList.remove('hidden');
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(alertEl.profileTimeout);
    alertEl.profileTimeout = setTimeout(closeProfileAlert, 5000);
}

/** Dismisses the profile alert. */
function closeProfileAlert() {
    var alertEl = document.getElementById('profileAlert');
    if (alertEl) alertEl.classList.add('hidden');
}

// ============================================================
// PROFILE & USER-MENU INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // — User menu: replace the node to avoid duplicate listeners —
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userMenuTrigger) {
        userMenuTrigger.replaceWith(userMenuTrigger.cloneNode(true));
        userMenuTrigger = document.getElementById('user-menu-trigger');

        userMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = document.getElementById('user-menu') &&
                document.getElementById('user-menu').classList.contains('open');
            setUserMenuOpen(!isOpen);
        });
    }

    // — Close user menu on outside click —
    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('user-menu');
        if (wrap && !wrap.contains(e.target)) {
            setUserMenuOpen(false);
        }
    });

    // — Profile: save originals and initialise the password-strength meter —
    if (document.getElementById('formPerfil')) {
        profileSaveOriginals();

        var passInput = document.getElementById('new_password');
        if (passInput) {
            passInput.addEventListener('input', function () {
                updateStrength(this.value);
            });
        }

        // Show flash messages passed from Blade via window.__profileFlash
        var flash = window.__profileFlash || {};
        if (flash.profile_updated) showProfileAlert('Cambios guardados correctamente.', 'success');
        if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
        if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');
    }

    // — Password form: confirm before submitting —
    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function (e) {
            e.preventDefault();

            var isGoogleOnly = !!document.getElementById('googlePassCta');
            var confirmMsg = isGoogleOnly
                ? 'Se definirá una contraseña para tu cuenta. Podrás iniciar sesión con correo y contraseña.'
                : 'Se actualizará la contraseña de tu cuenta.';
            var confirmBtn = isGoogleOnly
                ? '<i class="fas fa-key"></i> Sí, definir'
                : '<i class="fas fa-save"></i> Sí, actualizar';

            Swal.fire({
                title: '¿Confirmar cambio de contraseña?',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) return;
                sendPassword(formPassword);
            });
        });
    }

}); // end DOMContentLoaded (profile & user-menu)

// Performs the password fetch request (called after confirmation). 
function sendPassword(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    // isGooglePass: true cuando el CTA de Google está presente (provider === 'google')
    var isGooglePass = !!document.getElementById('googlePassCta');
    var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (!res.success) {
                showProfileAlert(res.message || 'Error al actualizar la contraseña.', 'danger');
                return;
            }

            form.reset();
            updateStrength('');

            // If the provider changed from Google to local, we need to update the form and UI to reflect that a password is now set
            if (res.provider_changed) {
                // Hide the Google-only CTA and remove the cancel button (since now there are real password fields to edit, not just a definition form)
                var cta = document.getElementById('googlePassCta');
                var cancelBtn = form.querySelector('.btn-secondary');
                if (cta) cta.classList.add('hidden');
                if (cancelBtn) cancelBtn.remove();

                // Update the badge in the hero section to reflect the new provider
                var heroBadge = document.querySelector('.profile-badge');
                if (heroBadge) {
                    heroBadge.className = 'profile-badge profile-badge--local';
                    heroBadge.innerHTML = '<i class="fas fa-envelope"></i> Cuenta local';
                }

                // Show the current password field if it doesn't exist (it won't exist en el caso de cuentas Google-only)
                var fieldsDiv = form.querySelector('.profile-fields');
                if (fieldsDiv && !document.getElementById('currentPassGroup')) {
                    var currentGroup = document.createElement('div');
                    currentGroup.id = 'currentPassGroup';
                    currentGroup.className = 'form-group profile-field-full';
                    currentGroup.innerHTML =
                        '<label for="current_password">Contraseña Actual</label>' +
                        '<div class="profile-input-pass">' +
                        '<input type="password" id="current_password" name="current_password"' +
                        ' class="form-control" placeholder="Tu contraseña actual"' +
                        ' autocomplete="current-password">' +
                        '<button type="button" class="profile-toggle-pass"' +
                        ' onclick="togglePassword(\'current_password\', this)">' +
                        '<i class="fas fa-eye"></i>' +
                        '</button>' +
                        '</div>';
                    fieldsDiv.insertBefore(currentGroup, fieldsDiv.firstChild);
                }

                // Update the form title and submit button text/icon
                var title = document.getElementById('passwordCardTitle');
                var saveBtn = document.getElementById('btnSavePassword');
                if (title) title.textContent = 'Cambiar Contraseña';
                if (saveBtn) saveBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar Contraseña';

                // The form was originally hidden for Google-only accounts, so show it now that a password is set
                form.classList.remove('hidden');
            }

            showProfileAlert(res.message || 'Contraseña actualizada correctamente.', 'success');
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                // Restore original button HTML if we didn't switch from Google to local; otherwise update the text/icon to reflect the new state
                var isNowLocal = !!document.getElementById('currentPassGroup');
                submitBtn.innerHTML = '<i class="fas fa-save"></i> ' +
                    (isNowLocal ? 'Actualizar Contraseña' : 'Guardar Contraseña');
            }
        });
}

// — Global exports —
window.enableEdit = enableEdit;
window.cancelEdit = cancelEdit;
window.submitProfile = submitProfile;
window.togglePassword = togglePassword;
window.showPasswordForm = showPasswordForm;
window.hidePasswordForm = hidePasswordForm;
window.showProfileAlert = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;