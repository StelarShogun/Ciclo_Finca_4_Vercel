/**
 * JavaScript para la interfaz de clientes
 * Maneja el carrito, modales y funcionalidades interactivas
 */

// ===== CARRITO: vista de página /cart =====
const cartLink = document.getElementById('cart-link');
const cartGuest = document.getElementById('cart-guest');

if (cartGuest) {
    cartGuest.addEventListener('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Inicia sesión',
            text: 'Debes iniciar sesión para ver tu carrito.',
            confirmButtonText: 'Entendido'
        });
    });
}

// ===== AGREGAR AL CARRITO =====
function addToCart(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            
            const addToCartModal = document.getElementById('add-to-cart-modal');
            if (addToCartModal) {
                addToCartModal.classList.remove('active');
            }
            
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
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al agregar el producto al carrito'
        });
    });
}

// Actualizar contador del carrito en el navbar
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    const cartLinkEl = document.getElementById('cart-link');

    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// ===== MODALES =====
document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

// ===== FILTROS DEL CATÁLOGO =====
const filterForm = document.getElementById('filter-form');
if (filterForm) {
    const autoSubmitFields = filterForm.querySelectorAll('#categoria_id, #ordenar, #direccion');
    autoSubmitFields.forEach(field => {
        field.addEventListener('change', function() {
        });
    });
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    const cartLinkEl = document.getElementById('cart-link');
    const cartGuestEl = document.getElementById('cart-guest');
    const el = cartLinkEl || cartGuestEl;
    if (el) {
        const initialCount = parseInt(el.getAttribute('data-cart-count') || 0, 10);
        updateCartCount(initialCount);
    }
    
    // Delegated listener para agregar al carrito
    document.addEventListener('click', function(event) {
        if (event.target.closest('.add-to-cart-btn')) {
            const btn = event.target.closest('.add-to-cart-btn');
            const productId = btn.dataset.productId;
            const quantity = 1;
            
            const addToCartModal = document.getElementById('add-to-cart-modal');
            if (!addToCartModal || !addToCartModal.classList.contains('active')) {
                addToCart(productId, quantity);
            }
        }
    });

    // ===== ELIMINACIÓN DINÁMICA DEL CARRITO =====
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.cart-remove-item');
        if (!btn) return;

        const cartItem = btn.closest('.cart-item');
        const itemId = btn.dataset.productId;
        const itemName = btn.dataset.productName || 'este producto';

        if (!cartItem || !itemId) return;

        Swal.fire({
            title: '¿Eliminar producto?',
            text: '¿Deseas eliminar "' + itemName + '" del carrito?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            fetch('/cart/remove/' + itemId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar' });
                    return;
                }

                cartItem.remove();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Producto eliminado del carrito',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });

                const totalFormatted = (data.cart_total != null)
                    ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                    : '₡0';
                const subtotalEl = document.getElementById('cart-subtotal');
                const totalEl = document.getElementById('cart-total-amount');
                if (subtotalEl) subtotalEl.textContent = totalFormatted;
                if (totalEl) totalEl.textContent = totalFormatted;

                updateCartCount(data.cart_count ?? 0);

                if (document.querySelectorAll('.cart-item').length === 0) {
                    showCartEmptyState();
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
            });
        });
    });

    // ===== CONTROLES DE CANTIDAD Y CHECKOUT (página /cart) =====
    function updateCartQuantity(productId, quantity) {
        fetch('/cart/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ producto_id: productId, cantidad: quantity })
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(data) {
            if (data.success) {
                window.location.reload();
            } else {
                Swal.fire('Error', data.message || 'No se pudo actualizar el carrito', 'error');
            }
        })
        .catch(function() {
            Swal.fire('Error', 'Ocurrió un error al actualizar el carrito', 'error');
        });
    }

    document.querySelectorAll('.quantity-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            let quantity = parseInt(this.value, 10);
            const max = parseInt(this.max, 10);
            if (quantity < 1) { this.value = 1; updateCartQuantity(productId, 1); }
            else if (quantity > max) {
                this.value = max;
                Swal.fire('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning');
                updateCartQuantity(productId, max);
            } else {
                updateCartQuantity(productId, quantity);
            }
        });
    });

    document.querySelectorAll('.quantity-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const productId = this.dataset.productId;
            const input = document.querySelector('.quantity-input[data-product-id="' + productId + '"]');
            if (!input) return;
            let quantity = parseInt(input.value, 10);
            const max = parseInt(input.max, 10);
            if (action === 'increase' && quantity < max) quantity++;
            else if (action === 'decrease' && quantity > 1) quantity--;
            input.value = quantity;
            updateCartQuantity(productId, quantity);
        });
    });

    const proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            Swal.fire({
                title: '¿Confirmar compra?',
                text: 'Se enviará tu pedido para retiro en tienda.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                proceedBtn.disabled = true;
                proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                fetch('/cart/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
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
                    }).then(function() {
                        window.location.reload();
                    });
                })
                .catch(function(err) {
                    console.error('Error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
                    proceedBtn.disabled = false;
                    proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                });
            });
        });
    }
});

function showCartEmptyState() {
    const card = document.querySelector('.cart-page-card');
    if (!card) return;
    const catalogUrl = (card.querySelector('.cart-header a[href]')?.getAttribute('href')) || '/catalog';
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

// Exportar funciones para uso global
window.addToCart = addToCart;
window.updateCartCount = updateCartCount;

// ===== USER MENU (PROFILE DROPDOWN) =====
const userMenu = document.getElementById('user-menu');
const userMenuTrigger = document.getElementById('user-menu-trigger');
const userDropdown = document.getElementById('user-dropdown');

function closeUserDropdown() {
    if (userDropdown) userDropdown.classList.remove('active');
    if (userMenuTrigger) userMenuTrigger.setAttribute('aria-expanded', 'false');
}

function toggleUserDropdown() {
    if (!userDropdown) return;

    const willOpen = !userDropdown.classList.contains('active');
    if (willOpen) {
        userDropdown.classList.add('active');
        if (userMenuTrigger) userMenuTrigger.setAttribute('aria-expanded', 'true');
    } else {
        closeUserDropdown();
    }
}

if (userMenuTrigger) {
    userMenuTrigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleUserDropdown();
    });
}

document.addEventListener('click', function (event) {
    if (!userDropdown || !userDropdown.classList.contains('active')) return;
    if (userMenu && userMenu.contains(event.target)) return;
    closeUserDropdown();
});

// ===== LOGIN MODAL =====
const loginModalTrigger = document.getElementById('login-modal-trigger');
const loginModal = document.getElementById('login-modal');
const loginModalOverlay = document.getElementById('login-modal-overlay');
const closeLoginModal = document.getElementById('close-login-modal');
const publicLoginForm = document.getElementById('public-login-form');

if (loginModalTrigger) {
    loginModalTrigger.addEventListener('click', function() {
        loginModal.classList.add('active');
        loginModalOverlay.classList.add('active');
    });
}

// Botón "Agregar" para invitados
document.addEventListener('click', function(e) {
    const guestBtn = e.target.closest('.guest-add-btn');
    if (guestBtn) {
        e.preventDefault();
        Swal.fire({
            icon: 'info',
            title: 'Inicia sesión',
            text: 'Debes iniciar sesión para agregar productos al carrito.',
            confirmButtonText: 'Entendido'
        });
    }
});

if (closeLoginModal) {
    closeLoginModal.addEventListener('click', function() {
        closeLoginModalFunc();
    });
}

if (loginModalOverlay) {
    loginModalOverlay.addEventListener('click', function() {
        closeLoginModalFunc();
    });
}

function closeLoginModalFunc() {
    loginModal.classList.remove('active');
    loginModalOverlay.classList.remove('active');
}

if (publicLoginForm) {
    publicLoginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = document.getElementById('login-submit-btn');
        const loadingSpan = document.getElementById('login-loading');
        const submitSpan = submitBtn.querySelector('span:not(.btn-loading)');
        
        submitBtn.disabled = true;
        if (submitSpan) submitSpan.classList.add('hidden');
        if (loadingSpan) loadingSpan.classList.remove('hidden');
        
        fetch('/login', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Bienvenido!',
                    text: data.message || 'Inicio de sesión exitoso',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = data.redirect || '/';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al iniciar sesión'
                });
                
                submitBtn.disabled = false;
                if (submitSpan) submitSpan.classList.remove('hidden');
                if (loadingSpan) loadingSpan.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al iniciar sesión'
            });
            
            submitBtn.disabled = false;
            if (submitSpan) submitSpan.classList.remove('hidden');
            if (loadingSpan) loadingSpan.classList.add('hidden');
        });
    });
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUserDropdown();
        if (loginModal && loginModal.classList.contains('active')) {
            closeLoginModalFunc();
        }
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
