<?php $__env->startSection('title', $producto->name . ' - Ciclo Pérez'); ?>

<?php $__env->startSection('content'); ?>
<div class="product-detail-container">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo e(route('clientes.home')); ?>">Inicio</a>
            <span>/</span>
            <a href="<?php echo e(route('clientes.catalogo')); ?>">Catálogo</a>
            <span>/</span>
            <span><?php echo e($producto->name); ?></span>
        </nav>

        <div class="product-detail-layout">
            <div class="product-detail-image">
                <img src="<?php echo e(asset('assets/images/products/' . ($producto->image ?? 'default.png'))); ?>" 
                     alt="<?php echo e($producto->name); ?>"
                     onerror="this.src='<?php echo e(asset('favicon.svg')); ?>'">
            </div>

            <div class="product-detail-info">
                <div class="product-detail-category"><?php echo e($producto->category->name ?? 'Uncategorized'); ?></div>
                <h1 class="product-detail-name"><?php echo e($producto->name); ?></h1>
                
                <?php if($producto->description): ?>
                    <div class="product-detail-description">
                        <p><?php echo e($producto->description); ?></p>
                    </div>
                <?php endif; ?>

                <div class="product-detail-price">
                    <span class="price-label">Precio:</span>
                    <span class="price-amount">₡<?php echo e(number_format($producto->sale_price, 0, ',', '.')); ?></span>
                </div>

                <div class="product-detail-stock">
                    <?php if($producto->stock_current > 0): ?>
                        <span class="stock-available">
                            <i class="fas fa-check-circle"></i>
                            En stock (<?php echo e($producto->stock_current); ?> disponibles)
                        </span>
                    <?php else: ?>
                        <span class="stock-unavailable">
                            <i class="fas fa-times-circle"></i>
                            Sin stock
                        </span>
                    <?php endif; ?>
                </div>

                <?php if($producto->stock_current > 0): ?>
                    <div class="product-detail-actions">
                        <div class="quantity-selector">
                            <label>Cantidad:</label>
                            <div class="quantity-controls">
                                <button class="quantity-btn" id="decrease-qty">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="product-quantity" value="1" min="1" max="<?php echo e($producto->stock_current); ?>">
                                <button class="quantity-btn" id="increase-qty">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg add-to-cart-btn" 
                                data-product-id="<?php echo e($producto->product_id); ?>"
                                data-product-name="<?php echo e($producto->name); ?>"
                                data-product-price="<?php echo e($producto->sale_price); ?>"
                                data-product-stock="<?php echo e($producto->stock_current); ?>">
                            <i class="fas fa-cart-plus"></i>
                            Agregar al Carrito
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($productosRelacionados->count() > 0): ?>
            <section class="related-products">
                <h2 class="section-title">Productos Relacionados</h2>
                <div class="products-grid">
                    <?php $__currentLoopData = $productosRelacionados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relacionado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="<?php echo e(route('clientes.producto', $relacionado->product_id)); ?>">
                                    <img src="<?php echo e(asset('assets/images/products/' . ($relacionado->image ?? 'default.png'))); ?>" 
                                         alt="<?php echo e($relacionado->name); ?>"
                                         onerror="this.src='<?php echo e(asset('favicon.svg')); ?>'">
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo e($relacionado->category->name ?? 'Uncategorized'); ?></div>
                                <h3 class="product-name">
                                    <a href="<?php echo e(route('clientes.producto', $relacionado->product_id)); ?>">
                                        <?php echo e($relacionado->name); ?>

                                    </a>
                                </h3>
                                <div class="product-footer">
                                    <div class="product-price">₡<?php echo e(number_format($relacionado->sale_price, 0, ',', '.')); ?></div>
                                    <a href="<?php echo e(route('clientes.producto', $relacionado->product_id)); ?>" class="btn btn-primary btn-sm">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Control de cantidad
    let quantity = 1;
    const maxQuantity = <?php echo e($producto->stock_current); ?>;
    
    document.getElementById('decrease-qty')?.addEventListener('click', function() {
        if (quantity > 1) {
            quantity--;
            document.getElementById('product-quantity').value = quantity;
        }
    });
    
    document.getElementById('increase-qty')?.addEventListener('click', function() {
        if (quantity < maxQuantity) {
            quantity++;
            document.getElementById('product-quantity').value = quantity;
        }
    });
    
    document.getElementById('product-quantity')?.addEventListener('change', function() {
        const value = parseInt(this.value);
        if (value < 1) {
            this.value = 1;
            quantity = 1;
        } else if (value > maxQuantity) {
            this.value = maxQuantity;
            quantity = maxQuantity;
        } else {
            quantity = value;
        }
    });
    
    // Agregar al carrito
    document.querySelector('.add-to-cart-btn')?.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const qty = parseInt(document.getElementById('product-quantity').value);
        addToCart(productId, qty);
    });
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('clientes.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/clientes/producto.blade.php ENDPATH**/ ?>