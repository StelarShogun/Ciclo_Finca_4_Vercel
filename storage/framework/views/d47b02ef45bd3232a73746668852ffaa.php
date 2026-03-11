<?php $__env->startSection('title', 'Carrito de Compras - Ciclo Pérez'); ?>

<?php $__env->startSection('content'); ?>
<div class="cart-container">
    <div class="container">
        <div class="cart-header">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i>
                Carrito de Compras
            </h1>
            <a href="<?php echo e(route('clientes.catalogo')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Continuar Comprando
            </a>
        </div>

        <?php if(count($cartItems) > 0): ?>
            <div class="cart-layout">
                <!-- Lista de Productos -->
                <div class="cart-items">
                    <?php $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="cart-item" data-product-id="<?php echo e($item['producto_id']); ?>">
                            <div class="cart-item-image">
                                <img src="<?php echo e(asset('assets/images/products/' . $item['imagen'])); ?>" 
                                     alt="<?php echo e($item['nombre']); ?>"
                                     onerror="this.src='<?php echo e(asset('favicon.svg')); ?>'">
                            </div>
                            <div class="cart-item-info">
                                <h3 class="cart-item-name"><?php echo e($item['nombre']); ?></h3>
                                <p class="cart-item-price">₡<?php echo e(number_format($item['precio'], 0, ',', '.')); ?> c/u</p>
                                <p class="cart-item-stock">Stock disponible: <?php echo e($item['stock_disponible']); ?></p>
                            </div>
                            <div class="cart-item-quantity">
                                <label>Cantidad:</label>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" data-action="decrease" data-product-id="<?php echo e($item['producto_id']); ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo e($item['cantidad']); ?>" 
                                           min="1" 
                                           max="<?php echo e($item['stock_disponible']); ?>"
                                           data-product-id="<?php echo e($item['producto_id']); ?>">
                                    <button class="quantity-btn" data-action="increase" data-product-id="<?php echo e($item['producto_id']); ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="cart-item-subtotal">
                                <span class="subtotal-label">Subtotal:</span>
                                <span class="subtotal-amount">₡<?php echo e(number_format($item['subtotal'], 0, ',', '.')); ?></span>
                            </div>
                            <div class="cart-item-actions">
                                <button class="btn btn-danger btn-sm remove-from-cart-btn" 
                                        data-product-id="<?php echo e($item['producto_id']); ?>"
                                        data-product-name="<?php echo e($item['nombre']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- Resumen del Carrito -->
                <aside class="cart-summary">
                    <div class="summary-card">
                        <h3 class="summary-title">Resumen del Pedido</h3>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="cart-subtotal">₡<?php echo e(number_format($total, 0, ',', '.')); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Impuestos:</span>
                                <span>₡0</span>
                            </div>
                            <div class="summary-row summary-total">
                                <span>Total:</span>
                                <span id="cart-total-amount">₡<?php echo e(number_format($total, 0, ',', '.')); ?></span>
                            </div>
                        </div>
                        <div class="summary-actions">
                            <button class="btn btn-primary btn-block btn-lg" id="proceed-checkout">
                                <i class="fas fa-check"></i>
                                Proceder con el Pedido
                            </button>
                            <p class="checkout-note">
                                <i class="fas fa-info-circle"></i>
                                Te contactaremos para confirmar tu pedido
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        <?php else: ?>
            <div class="cart-empty">
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Tu carrito está vacío</h2>
                    <p>Agrega productos desde nuestro catálogo</p>
                    <a href="<?php echo e(route('clientes.catalogo')); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-th"></i>
                        Ver Catálogo
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Actualizar cantidad
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(this.value);
            const max = parseInt(this.max);
            
            if (quantity < 1) {
                this.value = 1;
                updateCart(productId, 1);
            } else if (quantity > max) {
                this.value = max;
                Swal.fire('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning');
                updateCart(productId, max);
            } else {
                updateCart(productId, quantity);
            }
        });
    });
    
    // Botones de incrementar/decrementar
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const productId = this.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            let quantity = parseInt(input.value);
            const max = parseInt(input.max);
            
            if (action === 'increase' && quantity < max) {
                quantity++;
            } else if (action === 'decrease' && quantity > 1) {
                quantity--;
            }
            
            input.value = quantity;
            updateCart(productId, quantity);
        });
    });
    
    // Eliminar del carrito
    document.querySelectorAll('.remove-from-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
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
        });
    });
    
    // Proceder con el pedido
    document.getElementById('proceed-checkout')?.addEventListener('click', function() {
        Swal.fire({
            title: '¡Gracias por tu pedido!',
            text: 'Nos pondremos en contacto contigo pronto para confirmar los detalles.',
            icon: 'success',
            confirmButtonText: 'Entendido'
        });
    });
    
    function updateCart(productId, quantity) {
        fetch('/cart/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                producto_id: productId,
                cantidad: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para actualizar los totales
                window.location.reload();
            } else {
                Swal.fire('Error', data.message || 'No se pudo actualizar el carrito', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al actualizar el carrito', 'error');
        });
    }
    
    function removeFromCart(productId) {
        fetch(`/cart/remove/${productId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar el elemento del DOM
                document.querySelector(`.cart-item[data-product-id="${productId}"]`)?.remove();
                
                // Si no hay más items, recargar para mostrar el estado vacío
                if (document.querySelectorAll('.cart-item').length === 1) {
                    window.location.reload();
                } else {
                    // Actualizar totales
                    updateCartTotals(data.cart_total);
                }
                
                Swal.fire('Eliminado', 'Producto eliminado del carrito', 'success');
            } else {
                Swal.fire('Error', data.message || 'No se pudo eliminar el producto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al eliminar el producto', 'error');
        });
    }
    
    function updateCartTotals(total) {
        document.getElementById('cart-subtotal').textContent = '₡' + total.toLocaleString('es-CR');
        document.getElementById('cart-total-amount').textContent = '₡' + total.toLocaleString('es-CR');
    }
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('clientes.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/clientes/carrito.blade.php ENDPATH**/ ?>