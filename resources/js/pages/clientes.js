/**
 * JavaScript para la interfaz de clientes
 * Maneja el carrito, modales y funcionalidades interactivas
 */

// ===== CARRITO SIDEBAR =====
const cartToggle = document.getElementById('cart-toggle');
const cartSidebar = document.getElementById('cart-sidebar');
const cartOverlay = document.getElementById('cart-overlay');
const cartClose = document.getElementById('cart-close');

if (cartToggle) {
    cartToggle.addEventListener('click', function() {
        cartSidebar.classList.add('active');
        cartOverlay.classList.add('active');
        loadCartContent();
    });
}

if (cartClose) {
    cartClose.addEventListener('click', function() {
        closeCartSidebar();
    });
}

if (cartOverlay) {
    cartOverlay.addEventListener('click', function() {
        closeCartSidebar();
    });
}

function closeCartSidebar() {
    cartSidebar.classList.remove('active');
    cartOverlay.classList.remove('active');
}

// Cargar contenido del carrito en el sidebar
function loadCartContent() {
    const cartUrl = '/cart';
    
    fetch(cartUrl)
        .then(response => response.text())
        .then(html => {
            // Extraer solo el contenido del carrito del HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const cartItems = doc.querySelector('.cart-items');
            const cartFooter = doc.querySelector('.cart-summary');
            
            const cartContent = document.getElementById('cart-content');
            const cartFooterElement = document.getElementById('cart-footer');
            
            if (cartItems && cartItems.children.length > 0) {
                cartContent.innerHTML = '';
                cartItems.querySelectorAll('.cart-item').forEach(item => {
                    cartContent.appendChild(item.cloneNode(true));
                });
                cartFooterElement.style.display = 'block';
                
                // Actualizar total
                const totalElement = doc.querySelector('.summary-total span:last-child');
                if (totalElement) {
                    document.getElementById('cart-total').textContent = totalElement.textContent;
                }
            } else {
                cartContent.innerHTML = `
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty</p>
                        <a href="/catalog" class="btn btn-primary">View Catalog</a>
                    </div>
                `;
                cartFooterElement.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
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
            // Actualizar contador del carrito
            updateCartCount(data.cart_count);
            
            // Cerrar modal si existe
            const addToCartModal = document.getElementById('add-to-cart-modal');
            if (addToCartModal) {
                addToCartModal.classList.remove('active');
            }
            
            // Mostrar notificación
            Swal.fire({
                icon: 'success',
                title: '¡Agregado!',
                text: data.message || 'Producto agregado al carrito',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            
            // Recargar contenido del carrito si está abierto
            if (cartSidebar && cartSidebar.classList.contains('active')) {
                loadCartContent();
            }
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

// Actualizar contador del carrito
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    const cartToggleBtn = document.getElementById('cart-toggle');
    
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'flex' : 'none';
    }
    
    if (cartToggleBtn) {
        cartToggleBtn.setAttribute('data-cart-count', count);
    }
}

// ===== MODALES =====
// Cerrar modales al hacer clic fuera
document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
});

// Cerrar modales con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        closeCartSidebar();
    }
});

// ===== FILTROS DEL CATÁLOGO =====
const filterForm = document.getElementById('filter-form');
if (filterForm) {
    // Auto-submit en algunos campos
    const autoSubmitFields = filterForm.querySelectorAll('#categoria_id, #ordenar, #direccion');
    autoSubmitFields.forEach(field => {
        field.addEventListener('change', function() {
            // Opcional: auto-submit o mantener botón manual
        });
    });
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contador del carrito desde el atributo data
    const cartToggleBtn = document.getElementById('cart-toggle');
    if (cartToggleBtn) {
        const initialCount = parseInt(cartToggleBtn.getAttribute('data-cart-count') || 0);
        updateCartCount(initialCount);
    }
    
    // Agregar event listeners a botones de agregar al carrito que se carguen dinámicamente
    document.addEventListener('click', function(event) {
        if (event.target.closest('.add-to-cart-btn')) {
            const btn = event.target.closest('.add-to-cart-btn');
            const productId = btn.dataset.productId;
            const quantity = 1; // Cantidad por defecto
            
            // Si hay modal, abrirlo, sino agregar directamente
            const addToCartModal = document.getElementById('add-to-cart-modal');
            if (!addToCartModal || !addToCartModal.classList.contains('active')) {
                // Si no hay modal activo, agregar directamente con cantidad 1
                addToCart(productId, quantity);
            }
        }
    });
    
    // Manejar botones de eliminar del carrito en el sidebar
    document.addEventListener('click', function(event) {
        if (event.target.closest('.remove-from-cart-btn')) {
            const btn = event.target.closest('.remove-from-cart-btn');
            const productId = btn.dataset.productId;
            const productName = btn.dataset.productName;
            
            Swal.fire({
                title: '¿Eliminar producto?',
                text: `¿Deseas eliminar "${productName}" del carrito?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    removeFromCart(productId);
                }
            });
        }
    });
});

// Función para eliminar del carrito (usada en sidebar)
function removeFromCart(productId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    fetch(`/cart/remove/${productId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            loadCartContent();
            
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'Producto eliminado del carrito',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo eliminar el producto'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al eliminar el producto'
        });
    });
}

// Exportar funciones para uso global
window.addToCart = addToCart;
window.updateCartCount = updateCartCount;
window.removeFromCart = removeFromCart;

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

// ===== LOGIN  =====
const loginModalTrigger = document.getElementById('login-modal-trigger');

if (loginModalTrigger) {
    loginModalTrigger.addEventListener('click', function() {
        window.location.href = '/login'; 
    });
}

// Cerrar modales con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUserDropdown();
        if (loginModal && loginModal.classList.contains('active')) {
            closeLoginModalFunc();
        }
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        closeCartSidebar();
    }
});

// Función para cerrar el modal de login
function closeLoginModalFunc() {
    loginModal.classList.remove('active');
    loginModalOverlay.classList.remove('active');
}

// Manejar submit del formulario de login
if (publicLoginForm) {
    publicLoginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        const submitBtn = document.getElementById('login-submit-btn');
        const loadingSpan = document.getElementById('login-loading');
        const submitSpan = submitBtn.querySelector('span:not(.btn-loading)');
        
        // Cambiar estado del botón
        submitBtn.disabled = true;
        if (submitSpan) submitSpan.classList.add('hidden');
        if (loadingSpan) loadingSpan.classList.remove('hidden');
        
        fetch('/login', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            let data;
            try {
                data = await response.json();
            } catch (e) {
                // Si no es JSON, probablemente es HTML por error de validación o redirect
                window.location.reload(); // O redirige al catálogo si la sesión está activa
                return;
            }
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
                // Restaurar botón
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
            // Restaurar botón
            submitBtn.disabled = false;
            if (submitSpan) submitSpan.classList.remove('hidden');
            if (loadingSpan) loadingSpan.classList.add('hidden');
        });
    });
}