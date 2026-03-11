<?php $__env->startSection('title', 'Catálogo - Ciclo Pérez'); ?>

<?php $__env->startSection('content'); ?>
<div class="catalog-container">
    <!-- Header del Catálogo -->
    <div class="catalog-header">
        <div class="container">
            <h1 class="catalog-title">Catálogo de Productos</h1>
            <p class="catalog-subtitle">Explora nuestra amplia selección de productos</p>
        </div>
    </div>

    <div class="container">
        <div class="catalog-layout">
            <!-- Sidebar de Filtros -->
            <aside class="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>
                    
                    <form method="GET" action="<?php echo e(route('clientes.catalogo')); ?>" id="filter-form">
                        <!-- Búsqueda -->
                        <div class="filter-group">
                            <label for="buscar">Buscar</label>
                            <input type="text" 
                                   id="buscar" 
                                   name="buscar" 
                                   class="form-control" 
                                   placeholder="Nombre o descripción..."
                                   value="<?php echo e(request('buscar')); ?>">
                        </div>
                        
                        <!-- Categoría -->
                        <div class="filter-group">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-control">
                                <option value="">Todas las categorías</option>
                                <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($categoria->category_id); ?>" 
                                            <?php echo e(request('categoria_id') == $categoria->category_id ? 'selected' : ''); ?>>
                                        <?php echo e($categoria->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        
                        <!-- Rango de Precio -->
                        <div class="filter-group">
                            <label>Rango de Precio</label>
                            <div class="price-range">
                                <input type="number" 
                                       id="precio_min" 
                                       name="precio_min" 
                                       class="form-control" 
                                       placeholder="Mínimo"
                                       value="<?php echo e(request('precio_min')); ?>">
                                <span class="price-separator">-</span>
                                <input type="number" 
                                       id="precio_max" 
                                       name="precio_max" 
                                       class="form-control" 
                                       placeholder="Máximo"
                                       value="<?php echo e(request('precio_max')); ?>">
                            </div>
                        </div>
                        
                        <!-- Ordenar -->
                        <div class="filter-group">
                            <label for="ordenar">Ordenar por</label>
                            <select id="ordenar" name="ordenar" class="form-control">
                                <option value="fecha_creacion" <?php echo e(request('ordenar') == 'fecha_creacion' ? 'selected' : ''); ?>>Más recientes</option>
                                <option value="precio" <?php echo e(request('ordenar') == 'precio' ? 'selected' : ''); ?>>Precio</option>
                                <option value="nombre" <?php echo e(request('ordenar') == 'nombre' ? 'selected' : ''); ?>>Nombre</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="direccion">Dirección</label>
                            <select id="direccion" name="direccion" class="form-control">
                                <option value="desc" <?php echo e(request('direccion') == 'desc' ? 'selected' : ''); ?>>Descendente</option>
                                <option value="asc" <?php echo e(request('direccion') == 'asc' ? 'selected' : ''); ?>>Ascendente</option>
                            </select>
                        </div>
                        
                        <!-- Botones -->
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                                Aplicar Filtros
                            </button>
                            <a href="<?php echo e(route('clientes.catalogo')); ?>" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Contenido Principal -->
            <main class="catalog-content">
                <!-- Resultados -->
                <div class="catalog-results">
                    <div class="results-header">
                        <p class="results-count">
                            Mostrando <?php echo e($productos->firstItem() ?? 0); ?>-<?php echo e($productos->lastItem() ?? 0); ?> de <?php echo e($productos->total()); ?> productos
                        </p>
                    </div>
                    
                    <?php if($productos->count() > 0): ?>
                        <div class="products-grid">
                            <?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="<?php echo e(route('clientes.producto', $producto->product_id)); ?>">
                                            <img src="<?php echo e(asset('assets/images/products/' . ($producto->image ?? 'default.png'))); ?>" 
                                                 alt="<?php echo e($producto->name); ?>"
                                                 onerror="this.src='<?php echo e(asset('favicon.svg')); ?>'">
                                        </a>
                                        <?php if($producto->stock_current <= 10): ?>
                                            <span class="product-badge stock-low">Stock Bajo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-category"><?php echo e($producto->category->name ?? 'Uncategorized'); ?></div>
                                        <h3 class="product-name">
                                            <a href="<?php echo e(route('clientes.producto', $producto->product_id)); ?>">
                                                <?php echo e($producto->name); ?>

                                            </a>
                                        </h3>
                                        <?php if($producto->description): ?>
                                            <p class="product-description"><?php echo e(Str::limit($producto->description, 100)); ?></p>
                                        <?php endif; ?>
                                        <div class="product-footer">
                                            <div class="product-price">₡<?php echo e(number_format($producto->sale_price, 0, ',', '.')); ?></div>
                                            <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                                    data-product-id="<?php echo e($producto->product_id); ?>"
                                                    data-product-name="<?php echo e($producto->name); ?>"
                                                    data-product-price="<?php echo e($producto->sale_price); ?>"
                                                    data-product-stock="<?php echo e($producto->stock_current); ?>">
                                                <i class="fas fa-cart-plus"></i>
                                                Agregar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        
                        <!-- Paginación -->
                        <div class="pagination-wrapper">
                            <?php echo e($productos->links()); ?>

                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No se encontraron productos</h3>
                            <p>Intenta ajustar tus filtros de búsqueda</p>
                            <a href="<?php echo e(route('clientes.catalogo')); ?>" class="btn btn-primary">
                                Ver Todos los Productos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal para agregar al carrito (mismo que en home) -->
<div class="modal" id="add-to-cart-modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Agregar al Carrito</h3>
            <button class="modal-close" id="close-add-to-cart-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="product-preview" id="product-preview">
                <img id="preview-image" src="" alt="">
                <div class="preview-info">
                    <h4 id="preview-name"></h4>
                    <p class="preview-price" id="preview-price"></p>
                    <p class="preview-stock" id="preview-stock"></p>
                </div>
            </div>
            <div class="form-group">
                <label for="cart-quantity">Cantidad:</label>
                <input type="number" id="cart-quantity" class="form-control" min="1" value="1">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-add-to-cart">Cancelar</button>
            <button class="btn btn-primary" id="confirm-add-to-cart">Agregar al Carrito</button>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Manejo del modal de agregar al carrito (mismo código que en home)
    let currentProductId = null;
    
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentProductId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = parseFloat(this.dataset.productPrice);
            const productStock = parseInt(this.dataset.productStock);
            
            document.getElementById('preview-name').textContent = productName;
            document.getElementById('preview-price').textContent = '₡' + productPrice.toLocaleString('es-CR');
            document.getElementById('preview-stock').textContent = 'Stock disponible: ' + productStock;
            document.getElementById('cart-quantity').max = productStock;
            document.getElementById('cart-quantity').value = 1;
            
            const productCard = this.closest('.product-card');
            const productImage = productCard.querySelector('.product-image img');
            if (productImage) {
                document.getElementById('preview-image').src = productImage.src;
            }
            
            document.getElementById('add-to-cart-modal').classList.add('active');
        });
    });
    
    document.getElementById('confirm-add-to-cart').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('cart-quantity').value);
        
        if (quantity < 1) {
            Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
            return;
        }
        
        addToCart(currentProductId, quantity);
    });
    
    document.getElementById('cancel-add-to-cart').addEventListener('click', function() {
        document.getElementById('add-to-cart-modal').classList.remove('active');
    });
    
    document.getElementById('close-add-to-cart-modal').addEventListener('click', function() {
        document.getElementById('add-to-cart-modal').classList.remove('active');
    });
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('clientes.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/clientes/catalogo.blade.php ENDPATH**/ ?>