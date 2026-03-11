<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Gestión de Inventario - Ciclo Pérez Admin</title>
    
    <!-- Favicons modernos -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    <?php echo $__env->make('partes.aside', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="admin-main">
        <div class="inventory-container">
            <header class="usuarios-header">
                <div>
                    <h1>Gestión de Inventario</h1>
                    <p>Administra los productos y el stock del sistema</p>
                </div>
                <div class="usuarios-actions">
                    <button class="btn btn-primary" id="open-new-product-modal">
                        <i class="fas fa-plus"></i>
                        Nuevo Producto
                    </button>
                    <button class="btn btn-secondary" id="export-btn">
                        <i class="fas fa-download"></i>
                        Exportar
                    </button>
                    <button class="btn btn-secondary" id="import-btn">
                        <i class="fas fa-upload"></i>
                        Importar
                    </button>
                </div>
            </header>

        <?php if(session('status')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

            <!-- Filtros -->
            <div class="filters-section">
                <h2 class="filters-title">Filtros de Búsqueda</h2>
                <form class="filter-form">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="category">Categoría</label>
                            <select id="category" name="category_id">
                                <option value="">Todas las categorías</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->category_id); ?>" <?php if(request('category_id')==='<?php echo e($category->category_id); ?>'): echo 'selected'; endif; ?>>
                                        <?php echo e($category->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="stock">Estado de Stock</label>
                            <select id="stock" name="stock_status">
                                <option value="">Todos los estados</option>
                                <option value="in-stock" <?php if(request('stock_status')==='in-stock' ): echo 'selected'; endif; ?>>En Stock</option>
                                <option value="low" <?php if(request('stock_status')==='low' ): echo 'selected'; endif; ?>>Stock Bajo</option>
                                <option value="out" <?php if(request('stock_status')==='out' ): echo 'selected'; endif; ?>>Sin Stock</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="status">Estado del Producto</label>
                            <select id="status" name="status">
                                <option value="">Todos los estados</option>
                                <option value="active" <?php if(request('status')==='active' ): echo 'selected'; endif; ?>>Activo</option>
                                <option value="inactive" <?php if(request('status')==='inactive' ): echo 'selected'; endif; ?>>Inactivo</option>
                                <option value="out_of_stock" <?php if(request('status')==='out_of_stock' ): echo 'selected'; endif; ?>>Agotado</option>
                                <option value="discontinued" <?php if(request('status')==='discontinued' ): echo 'selected'; endif; ?>>Descontinuado</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search">Buscar</label>
                            <input type="text" id="search" name="search" placeholder="Nombre o código" value="<?php echo e(request('search')); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Aplicar Filtros
                            </button>
                        </div>
                        <div class="filter-group">
                            <button type="button" class="btn btn-primary" id="clear-filters">
                                <i class="fas fa-times"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de productos -->
            <section class="products-section">
                <div class="loading-spinner-overlay">
                    <div class="spinner"></div>
                </div>
                <div class="products-header">
                    <div class="products-count">
                        <span><?php echo e($paginator->total()); ?> products</span>
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" data-view="table">
                            <i class="fas fa-table"></i>
                        </button>
                        <button class="view-btn" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>

                <!-- Vista de tabla -->
                <div class="products-table table-view active">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $paginator; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="product-cell">
                                    <?php if($product->image): ?>
                                        <img src="<?php echo e(asset('assets/images/products/' . $product->image)); ?>" alt="<?php echo e($product->name); ?>">
                                    <?php else: ?>
                                        <img src="<?php echo e(asset('assets/images/products/default.png')); ?>" alt="<?php echo e($product->name); ?>">
                                    <?php endif; ?>
                                    <div class="product-info">
                                        <h4><?php echo e($product->name); ?></h4>
                                        <span class="sku">SKU: <?php echo e('BK-' . str_pad($product->product_id, 3, '0', STR_PAD_LEFT)); ?></span>
                                    </div>
                                </td>
                                <td><?php echo e($product->category->name); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo e($product->stock_current > 10 ? 'success' : ($product->stock_current > 0 ? 'warning' : 'danger')); ?>"><?php echo e($product->stock_current); ?></span>
                                </td>
                                <td>₡<?php echo e(number_format($product->sale_price, 0, ',', '.')); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'warning' : 'secondary')); ?>"><?php echo e($product->status === 'active' ? 'Activo' : ($product->status === 'inactive' ? 'Inactivo' : ($product->status === 'out_of_stock' ? 'Agotado' : 'Descontinuado'))); ?></span>
                                </td>
                                <td>
                                    <div class="actions-container">
                                        <button class="action-btn view view-details-btn" data-product-id="<?php echo e($product->product_id); ?>" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit edit-btn" data-product-id="<?php echo e($product->product_id); ?>" title="Edit product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete" data-action="delete"
                                            data-product-id="<?php echo e($product->product_id); ?>"
                                            data-product-name="<?php echo e($product->name); ?>" title="Delete product">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista de cuadrícula -->
                <div class="products-table grid-view">
                    <div class="products-grid">
                        <?php $__currentLoopData = $paginator; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="product-card">
                            <div class="product-card-header">
                                <?php if($product->image): ?>
                                    <img src="<?php echo e(asset('assets/images/products/' . $product->image)); ?>" alt="<?php echo e($product->name); ?>" class="product-card-image">
                                <?php else: ?>
                                    <img src="<?php echo e(asset('assets/images/products/default.png')); ?>" alt="<?php echo e($product->name); ?>" class="product-card-image">
                                <?php endif; ?>
                                <div class="product-card-info">
                                    <h4><?php echo e($product->name); ?></h4>
                                    <span class="sku">SKU: <?php echo e('BK-' . str_pad($product->product_id, 3, '0', STR_PAD_LEFT)); ?></span>
                                </div>
                            </div>
                            <div class="product-card-details">
                                <div class="product-card-detail">
                                    <span class="product-card-detail-label">Categoría</span>
                                    <span class="product-card-detail-value"><?php echo e($product->category->name); ?></span>
                                </div>
                                <div class="product-card-detail">
                                    <span class="product-card-detail-label">Stock</span>
                                    <span class="product-card-detail-value">
                                        <span class="stock-badge <?php echo e($product->stock_current > 10 ? 'success' : ($product->stock_current > 0 ? 'warning' : 'danger')); ?>"><?php echo e($product->stock_current); ?></span>
                                    </span>
                                </div>
                                <div class="product-card-detail">
                                    <span class="product-card-detail-label">Precio</span>
                                    <span class="product-card-detail-value">₡<?php echo e(number_format($product->sale_price, 0, ',', '.')); ?></span>
                                </div>
                                <div class="product-card-detail">
                                    <span class="product-card-detail-label">Estado</span>
                                    <span class="product-card-detail-value">
                                        <span class="status-badge <?php echo e($product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'warning' : 'secondary')); ?>"><?php echo e($product->status === 'active' ? 'Activo' : ($product->status === 'inactive' ? 'Inactivo' : ($product->status === 'out_of_stock' ? 'Agotado' : 'Descontinuado'))); ?></span>
                                    </span>
                                </div>
                            </div>
                            <div class="product-card-actions">
                                <div class="actions-container">
                                    <button class="action-btn view view-details-btn" data-product-id="<?php echo e($product->product_id); ?>" title="View details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit edit-btn" data-product-id="<?php echo e($product->product_id); ?>" title="Edit product">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete" data-action="delete"
                                        data-product-id="<?php echo e($product->product_id); ?>"
                                        data-product-name="<?php echo e($product->name); ?>" title="Delete product">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                                <?php if (isset($component)) { $__componentOriginal41032d87daf360242eb88dbda6c75ed1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41032d87daf360242eb88dbda6c75ed1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $paginator,'label' => 'inventory']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($paginator),'label' => 'inventory']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $attributes = $__attributesOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $component = $__componentOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__componentOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Modal de creación de producto (único) -->
    <div class="edit-modal" id="new-product-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nuevo Producto</h3>
                <button class="modal-close" id="close-new-product-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="new-product-form" action="<?php echo e(route('products.store')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-name">Nombre del Producto *</label>
                            <input type="text" id="new-name" name="name" placeholder="e.g., Bike tire" required>
                        </div>
                        <div class="form-group">
                            <label for="new-description">Descripción</label>
                            <textarea id="new-description" name="description" rows="3" placeholder="e.g., High quality off-road tire"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-image">Imagen del Producto </label>
                        <input type="file" id="new-image" name="image" accept="image/*">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-category">Categoría *</label>
                            <select id="new-category" name="category_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php $__currentLoopData = \App\Models\Category::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->category_id); ?>"><?php echo e($category->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new-provider">Proveedor *</label>
                            <select id="new-provider" name="supplier_id" required>
                                <option value="">Seleccionar proveedor</option>
                                <?php $__currentLoopData = \App\Models\Supplier::where('status', 'active')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($supplier->supplier_id); ?>"><?php echo e($supplier->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-price-buy">Precio de Compra (₡) *</label>
                            <input type="number" id="new-price-buy" name="purchase_price" min="0" step="0.01" placeholder="e.g., 10000" required>
                        </div>
                        <div class="form-group">
                            <label for="new-price-sell">Precio de Venta (₡) *</label>
                            <input type="number" id="new-price-sell" name="sale_price" min="0" step="0.01" placeholder="e.g., 15000" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-stock">Stock Actual *</label>
                            <input type="number" id="new-stock" name="stock_current" min="0" placeholder="e.g., 50" required>
                        </div>
                        <div class="form-group">
                            <label for="new-stock-min">Stock Mínimo *</label>
                            <input type="number" id="new-stock-min" name="stock_minimum" min="0" placeholder="e.g., 10" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-status">Estado *</label>
                        <select id="new-status" name="status" required>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="out_of_stock">Agotado</option>
                            <option value="discontinued">Descontinuado</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-new-product">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="save-new-product">
                    <i class="fas fa-plus"></i>
                    Crear Producto
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div class="edit-modal" id="edit-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Producto</h3>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- El formulario se completará dinámicamente a través de JavaScript -->
                <form id="edit-product-form" method="POST">
                    <?php echo csrf_field(); ?>
                    <!-- Campo oculto para simular método PUT -->
                    <input type="hidden" name="_method" value="PUT">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-name">Nombre del Producto *</label>
                            <input type="text" id="edit-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-description">Descripción</label>
                            <textarea id="edit-description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-image">Imagen del Producto </label>
                        <input type="file" id="edit-image" name="image" accept="image/*">
                        <div id="current-image-preview" style="margin-top: 10px;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-category">Categoría *</label>
                            <select id="edit-category" name="category_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php $__currentLoopData = \App\Models\Category::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->category_id); ?>"><?php echo e($category->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-provider">Proveedor *</label>
                            <select id="edit-provider" name="supplier_id" required>
                                <option value="">Seleccionar proveedor</option>
                                <?php $__currentLoopData = \App\Models\Supplier::where('status', 'active')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($supplier->supplier_id); ?>"><?php echo e($supplier->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-price-buy">Precio de Compra (₡) *</label>
                            <input type="number" id="edit-price-buy" name="purchase_price" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-price-sell">Precio de Venta (₡) *</label>
                            <input type="number" id="edit-price-sell" name="sale_price" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-stock">Stock Actual *</label>
                            <input type="number" id="edit-stock" name="stock_current" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-stock-min">Stock Mínimo *</label>
                            <input type="number" id="edit-stock-min" name="stock_minimum" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-status">Estado *</label>
                        <select id="edit-status" name="status" required>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="out_of_stock">Agotado</option>
                            <option value="discontinued">Descontinuado</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-edit">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="save-edit">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para exportar -->
    <div class="edit-modal" id="export-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Exportar Productos</h3>
                <button class="modal-close" id="close-export-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <div class="export-option">
                        <div class="export-icon">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <div class="export-info">
                            <h4>XML</h4>
                            <p>Formato estructurado para intercambio de datos</p>
                        </div>
                        <a href="<?php echo e(route('products.export', 'xml')); ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            Exportar XML
                        </a>
                    </div>
                    <div class="export-option">
                        <div class="export-icon">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div class="export-info">
                            <h4>CSV</h4>
                            <p>Formato de hoja de cálculo compatible con Excel</p>
                        </div>
                        <a href="<?php echo e(route('products.export', 'csv')); ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            Exportar CSV
                        </a>
                    </div>
                    <div class="export-option">
                        <div class="export-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="export-info">
                            <h4>JSON</h4>
                            <p>Formato ligero para aplicaciones web</p>
                        </div>
                        <a href="<?php echo e(route('products.export', 'json')); ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            Exportar JSON
                        </a>
                    </div>
                    <div class="export-option">
                        <div class="export-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="export-info">
                            <h4>PDF</h4>
                            <p>Documento profesional para impresión</p>
                        </div>
                        <a href="<?php echo e(route('products.export', 'pdf')); ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i>
                            Exportar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para importar -->
    <div class="edit-modal" id="import-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-file-upload"></i> Importar Productos</h3>
                <button class="modal-close" id="close-import-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?php echo e(route('products.import')); ?>" method="POST" enctype="multipart/form-data" id="import-form">
                    <?php echo csrf_field(); ?>
                    
                    <!-- Área de carga de archivo -->
                    <div class="form-group">
                        <label for="import_file" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Arrastra tu archivo aquí o haz clic para seleccionar</span>
                        </label>
                        <input type="file" id="import_file" name="import_file" accept=".xml,.csv,.json,.txt" required style="display: none;">
                        <div id="file-info" class="file-info hidden">
                            <div class="file-info-content">
                                <i class="fas fa-file" id="file-icon"></i>
                                <div class="file-details">
                                    <strong id="file-name"></strong>
                                    <span id="file-format" class="file-format-badge"></span>
                                    <small id="file-size"></small>
                                </div>
                                <button type="button" class="btn-remove-file" id="remove-file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Información del formato detectado -->
                    <div id="format-detected" class="alert alert-success hidden">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Formato detectado:</strong> <span id="detected-format-text"></span>
                            <small class="format-help-text"></small>
                        </div>
                    </div>

                    <!-- Guía de formatos soportados -->
                    <div class="form-group">
                        <div class="alert alert-info">
                            <div class="alert-header">
                                <i class="fas fa-info-circle"></i>
                                <strong>Formatos soportados</strong>
                            </div>
                            <div class="formats-guide">
                                <div class="format-item">
                                    <i class="fas fa-file-code format-icon xml"></i>
                                    <div>
                                        <strong>XML</strong>
                                        <small>Formato estructurado con etiquetas</small>
                                    </div>
                                </div>
                                <div class="format-item">
                                    <i class="fas fa-file-csv format-icon csv"></i>
                                    <div>
                                        <strong>CSV</strong>
                                        <small>Compatible con Excel y hojas de cálculo</small>
                                    </div>
                                </div>
                                <div class="format-item">
                                    <i class="fas fa-file-alt format-icon json"></i>
                                    <div>
                                        <strong>JSON</strong>
                                        <small>Formato ligero para aplicaciones web</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos requeridos -->
                    <div class="form-group">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Campos requeridos en el archivo:</strong>
                                <ul class="required-fields-list">
                                    <li><code>nombre</code> - Nombre del producto</li>
                                    <li><code>categoria</code> - Nombre de la categoría (debe existir)</li>
                                    <li><code>proveedor</code> - Nombre del proveedor (debe existir)</li>
                                    <li><code>precio_compra</code> - Precio de compra</li>
                                    <li><code>precio_venta</code> - Precio de venta</li>
                                    <li><code>stock_actual</code> - Cantidad en stock</li>
                                    <li><code>stock_minimo</code> - Stock mínimo</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-import">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="confirm-import" disabled>
                    <i class="fas fa-upload"></i>
                    <span>Importar Productos</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del producto -->
    <div class="edit-modal" id="view-product-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del Producto</h3>
                <button class="modal-close" id="close-view-product-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-product-body">
                <!-- Los detalles del producto se cargarán aquí dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-view-product">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/inventory.js']); ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Modal de exportar
        document.getElementById('export-btn').addEventListener('click', function() {
            document.getElementById('export-modal').classList.add('active');
        });

        document.getElementById('close-export-modal').addEventListener('click', function() {
            document.getElementById('export-modal').classList.remove('active');
        });


        // Cerrar modales al hacer clic en el backdrop
        document.querySelector('#export-modal .modal-backdrop').addEventListener('click', function() {
            document.getElementById('export-modal').classList.remove('active');
        });


        
    </script>
    
</body>

</html>

</html><?php /**PATH /var/www/html/resources/views/products/inventory.blade.php ENDPATH**/ ?>