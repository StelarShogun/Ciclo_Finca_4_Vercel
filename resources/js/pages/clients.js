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
                // Update totals without a full page reload.
                var totalFormatted = (data.cart_total != null)
                    ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                    : '₡0';

                var subtotalEl = document.getElementById('cart-subtotal');
                var totalEl = document.getElementById('cart-total-amount');
                if (subtotalEl) subtotalEl.textContent = totalFormatted;
                if (totalEl) totalEl.textContent = totalFormatted;

                updateCartCount(data.cart_count || 0);

                // Update the affected line subtotal, using unit price from the rendered UI.
                var cartItem = document.querySelector('.cart-item[data-product-id="' + productId + '"]');
                if (cartItem) {
                    var unitPriceEl = cartItem.querySelector('.item-price');
                    var unitPriceText = unitPriceEl ? unitPriceEl.textContent : '';
                    // Matches "₡1.234 c/u" => returns 1234
                    var unitPrice = parseInt(unitPriceText.replace(/[^\d]/g, ''), 10) || 0;

                    var newSubtotal = unitPrice * quantity;
                    var lineSubtotalEl = cartItem.querySelector('.subtotal-amount');
                    if (lineSubtotalEl) {
                        lineSubtotalEl.textContent = '₡' + newSubtotal.toLocaleString('es-CR');
                    }
                }
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
                    } else if (data.redirect) {
                        // Unverified email: offer to send code and redirect to verify
                        if (submitBtn) submitBtn.disabled = false;
                        if (submitSpan) submitSpan.classList.remove('hidden');
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
                            // El servidor ya envió el código al detectar el correo no verificado
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

        // Guest: redirect to login (no cart actions for unauthenticated users)
        if (e.target.closest('.guest-add-btn')) {
            window.location.href = '/login';
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
                        // Vaciar la UI del carrito inmediatamente tras confirmar.
                        updateCartCount(0);
                        showCartEmptyState();

                        Swal.fire({
                            icon: 'success',
                            text: 'Su pedido fue enviado con éxito. Tiene un lapso de 3 días para retirarlo en nuestro local. El pago se realiza de forma presencial mediante SINPE, efectivo o tarjeta.',
                            confirmButtonText: 'Entendido'
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

    // — Catalog pagination: same structure as admin (Prev, page indicator, Next, Ir) —
    (function initCatalogPagination() {
        var wrapper = document.querySelector('.pagination-wrapper .pagination');
        if (!wrapper) return;

        var goInput = wrapper.querySelector('#goToPageInput');
        var goBtn = wrapper.querySelector('#goToPageBtn');

        wrapper.querySelectorAll('.button[aria-label]').forEach(function (a) {
            if (a.getAttribute('aria-disabled') === 'true') {
                a.addEventListener('click', function (e) { e.preventDefault(); });
                a.classList.add('is-disabled');
            }
        });

        function goToPage() {
            var totalSpan = wrapper.querySelector('.button.button-primary');
            if (!totalSpan) return;
            var parts = totalSpan.textContent.trim().split('/');
            var lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
            var target = parseInt((goInput && goInput.value) ? goInput.value.trim() : '1', 10);
            if (isNaN(target)) target = 1;
            if (target < 1) target = 1;
            if (target > lastPage) target = lastPage;
            var url = new URL(window.location.href);
            url.searchParams.set('page', String(target));
            window.location.assign(url.toString());
        }

        if (goBtn) goBtn.addEventListener('click', goToPage);
        if (goInput) {
            goInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); goToPage(); }
            });
        }
    })();

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

// ============================================================
// REGISTER FORM utilities (create.blade.php)
// ============================================================

/** Toggles password visibility; called via inline onclick in the register form. */
function togglePass(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/** Shows a field-level validation message below an input. */
function showMsg(msgId, type, text) {
    var el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

/** Clears a field-level message. */
function clearMsg(msgId) {
    var el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

/** Adds or removes input-error / input-ok classes from an input element. */
function setInputState(input, state) {
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ============================================================
// LOGIN PAGE + REGISTER FORM initialization
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // — Login page: toggle password visibility —
    var togglePwdBtn = document.getElementById('toggle-password');
    if (togglePwdBtn) {
        togglePwdBtn.addEventListener('click', function () {
            var input = document.getElementById('login-password');
            var icon  = document.getElementById('eye-icon');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
            }
        });
    }

    // — Register form: only initialise when the form exists —
    if (!document.getElementById('formRegistroCliente')) return;

    var invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',           required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',          required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido',  required: false },
    ].forEach(function (field) {
        var input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function () {
            if (invalidChars.test(this.value)) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
                showMsg(field.msgId, 'error', 'Solo se permiten letras y espacios, sin números ni símbolos.');
                setInputState(this, 'input-error');
                return;
            }
            var val = this.value.trim();
            if (val === '' && field.required) {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            } else if (val !== '' && val.length < 2) {
                showMsg(field.msgId, 'error', field.label + ' debe tener al menos 2 caracteres.');
                setInputState(this, 'input-error');
            } else if (val !== '') {
                showMsg(field.msgId, 'success', 'Campo válido.');
                setInputState(this, 'input-ok');
            } else {
                clearMsg(field.msgId);
                setInputState(this, null);
            }
        });

        input.addEventListener('blur', function () {
            if (field.required && this.value.trim() === '') {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    });

    var gmailRegInput = document.getElementById('gmail');
    if (gmailRegInput) {
        gmailRegInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-gmail'); setInputState(this, null); return; }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-gmail', 'success', 'Correo válido.');
                setInputState(this, 'input-ok');
            }
        });
        gmailRegInput.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    function checkPassMatch() {
        var passwordEl = document.getElementById('password');
        var pcInput    = document.getElementById('password_confirmation');
        if (!passwordEl || !pcInput) return;
        var p  = passwordEl.value;
        var pc = pcInput.value;
        if (pc.length === 0) { clearMsg('msg-pass-confirm'); setInputState(pcInput, null); return; }
        if (p !== pc) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcInput, 'input-error');
        } else {
            showMsg('msg-pass-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(pcInput, 'input-ok');
        }
    }

    var passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-pass'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkPassMatch();
        });
    }

    var passConfirmInput = document.getElementById('password_confirmation');
    if (passConfirmInput) passConfirmInput.addEventListener('input', checkPassMatch);

    document.getElementById('formRegistroCliente').addEventListener('submit', function (e) {
        var valid = true;

        if (document.getElementById('name').value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(document.getElementById('name'), 'input-error');
            valid = false;
        }
        if (document.getElementById('first_surname').value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(document.getElementById('first_surname'), 'input-error');
            valid = false;
        }

        var gv = document.getElementById('gmail').value.trim().toLowerCase();
        if (gv === '') {
            showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
            setInputState(document.getElementById('gmail'), 'input-error');
            valid = false;
        } else if (!gv.endsWith('@gmail.com')) {
            showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(document.getElementById('gmail'), 'input-error');
            valid = false;
        }

        var pv  = document.getElementById('password').value;
        var pcv = document.getElementById('password_confirmation').value;
        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(document.getElementById('password'), 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(document.getElementById('password'), 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(document.getElementById('password_confirmation'), 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(document.getElementById('password_confirmation'), 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        document.getElementById('btnRegistrarTexto').style.display = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled = true;
    });

}); // end DOMContentLoaded (login & register)

// ============================================================
// RECOVERY FORM initialization (recovery.blade.php)
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    if (!document.getElementById('formRecovery')) return;

    // — Eye toggle for new password —
    var toggleRecPass = document.getElementById('toggle-recovery-password');
    if (toggleRecPass) {
        toggleRecPass.addEventListener('click', function () {
            var input = document.getElementById('recovery-password');
            var icon  = document.getElementById('eye-recovery-password');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
            }
        });
    }

    // — Eye toggle for confirm password —
    var toggleRecConfirm = document.getElementById('toggle-recovery-confirm');
    if (toggleRecConfirm) {
        toggleRecConfirm.addEventListener('click', function () {
            var input = document.getElementById('recovery-password-confirm');
            var icon  = document.getElementById('eye-recovery-confirm');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
            }
        });
    }

    // — Real-time email validation —
    var recEmailInput = document.getElementById('recovery-email');
    if (recEmailInput) {
        recEmailInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-recovery-email'); setInputState(this, null); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                showMsg('msg-recovery-email', 'error', 'Ingresa un correo electrónico válido.');
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-recovery-email', 'success', 'Correo válido.');
                setInputState(this, 'input-ok');
            }
        });
    }

    // — Real-time password strength —
    var recPassInput = document.getElementById('recovery-password');
    if (recPassInput) {
        recPassInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-recovery-password'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-recovery-password', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkRecoveryMatch();
        });
    }

    // — Real-time confirm match —
    function checkRecoveryMatch() {
        var pass    = document.getElementById('recovery-password');
        var confirm = document.getElementById('recovery-password-confirm');
        if (!pass || !confirm) return;
        if (confirm.value.length === 0) { clearMsg('msg-recovery-confirm'); setInputState(confirm, null); return; }
        if (pass.value !== confirm.value) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(confirm, 'input-error');
        } else {
            showMsg('msg-recovery-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(confirm, 'input-ok');
        }
    }

    var recConfirmInput = document.getElementById('recovery-password-confirm');
    if (recConfirmInput) recConfirmInput.addEventListener('input', checkRecoveryMatch);

    // — AJAX form submission —
    document.getElementById('formRecovery').addEventListener('submit', function (e) {
        e.preventDefault();

        var emailVal = recEmailInput ? recEmailInput.value.trim() : '';
        var passVal  = recPassInput  ? recPassInput.value         : '';
        var confVal  = recConfirmInput ? recConfirmInput.value    : '';
        var valid    = true;

        if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal.toLowerCase())) {
            showMsg('msg-recovery-email', 'error', 'Ingresa un correo electrónico válido.');
            setInputState(recEmailInput, 'input-error');
            valid = false;
        }
        if (passVal.length < 8) {
            showMsg('msg-recovery-password', 'error', passVal.length === 0 ? 'La contraseña es obligatoria.' : 'Mínimo 8 caracteres.');
            setInputState(recPassInput, 'input-error');
            valid = false;
        }
        if (confVal.length === 0) {
            showMsg('msg-recovery-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        } else if (passVal !== confVal) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        }
        if (!valid) return;

        var btn        = document.getElementById('btnRecovery');
        var btnTexto   = document.getElementById('btnRecoveryTexto');
        var btnCargando = document.getElementById('btnRecoveryCargando');
        if (btn) btn.disabled = true;
        if (btnTexto) btnTexto.style.display = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';

        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(this)
        })
            .then(function (r) {
                if (r.status === 422) {
                    return r.json().then(function (data) {
                        var errors = data.errors || {};
                        var firstMsg = Object.values(errors)[0];
                        Swal.fire('Error', Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'), 'error');
                        return Promise.reject('validation');
                    });
                }
                return r.json();
            })
            .then(function (res) {
                if (res.success && res.needs_verification) {
                    // Redirect to code-entry form
                    window.location.href = res.redirect;
                    return;
                }
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: res.message || 'Contraseña actualizada correctamente.',
                    confirmButtonText: 'Ir al inicio de sesión'
                }).then(function () {
                    window.location.href = '/login';
                });
            })
            .catch(function (err) {
                if (err === 'validation') return;
                Swal.fire('Error', 'Ocurrió un error. Intenta de nuevo.', 'error');
            })
            .finally(function () {
                if (btn) btn.disabled = false;
                if (btnTexto) btnTexto.style.display = '';
                if (btnCargando) btnCargando.style.display = 'none';
            });
    });

}); // end DOMContentLoaded (recovery)

// — Global exports (register form inline onclick) —
window.togglePass = togglePass;
window.showMsg = showMsg;
window.clearMsg = clearMsg;
window.setInputState = setInputState;

