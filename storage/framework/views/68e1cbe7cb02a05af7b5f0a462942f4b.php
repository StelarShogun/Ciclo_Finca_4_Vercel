<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Dashboard - Ciclo Pérez Admin</title>
    
    <!-- Favicons modernos -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="admin-layout">
    <?php echo $__env->make('partes.aside', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="admin-main">
        <div class="dashboard-container">
            <!-- Header del Dashboard -->
            <header class="dashboard-header">
                <div class="header-content">
                    <div class="welcome-section">
                        <h1>¡Bienvenido al Dashboard!</h1>
                        <p>Gestión integral del sistema Ciclo Finca 4</p>
                        <div class="current-time" id="current-time"></div>
                        <?php if(isset($error)): ?>
                            <div class="alert alert-warning" style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo e($error); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary" id="refresh-dashboard">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>
                        <button class="btn btn-secondary" id="export-report">
                            <i class="fas fa-download"></i>
                            Exportar Reporte
                        </button>
                    </div>
                </div>
            </header>

            <!-- KPIs Principales -->
            <section class="kpis-section">
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Total Productos</h3>
                        <div class="kpi-value" id="total-products"><?php echo e($totalProducts ?? 0); ?></div>
                        <div class="kpi-change positive" id="products-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Ventas Hoy</h3>
                        <div class="kpi-value" id="today-sales">₡<?php echo e(number_format($todaySales ?? 0, 0, ',', '.')); ?></div>
                        <div class="kpi-change positive" id="sales-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8%</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Proveedores</h3>
                        <div class="kpi-value" id="total-suppliers"><?php echo e($totalSuppliers ?? 0); ?></div>
                        <div class="kpi-change neutral" id="suppliers-change">
                            <i class="fas fa-minus"></i>
                            <span>0%</span>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Stock Bajo</h3>
                        <div class="kpi-value" id="low-stock"><?php echo e($lowStockProducts ?? 0); ?></div>
                        <div class="kpi-change negative" id="stock-change">
                            <i class="fas fa-arrow-down"></i>
                            <span>-3%</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Gráficos y Estadísticas -->
            <section class="charts-section">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Ventas de los Últimos 7 Días</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-period="7d">7 días</button>
                            <button class="chart-btn" data-period="30d">30 días</button>
                            <button class="chart-btn" data-period="90d">90 días</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Productos por Categoría</h3>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="category-chart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Tablas de Información -->
            <section class="tables-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3>Productos con Stock Bajo</h3>
                        <a href="<?php echo e(route('inventory')); ?>" class="btn btn-sm btn-primary">
                            Ver Todos
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-table">
                                <?php $__empty_1 = true; $__currentLoopData = $lowStockProductsList ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <img src="<?php echo e(asset('assets/images/products/' . ($product->image ?? 'default.png'))); ?>" 
                                                 alt="<?php echo e($product->name); ?>" class="product-thumb">
                                            <span><?php echo e($product->name); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="stock-badge danger"><?php echo e($product->stock_current); ?></span>
                                    </td>
                                    <td><?php echo e($product->stock_minimum); ?></td>
                                    <td>
                                        <span class="status-badge warning">Stock Bajo</span>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <p>No hay productos con stock bajo</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>Ventas Recientes</h3>
                        <a href="<?php echo e(route('sales.index')); ?>" class="btn btn-sm btn-primary">
                            Ver Todas
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recent-sales-table">
                                <?php $__empty_1 = true; $__currentLoopData = $recentSales ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($sale->invoice_number ?? '#' . $sale->sale_id); ?></td>
                                    <td><?php echo e($sale->customer ? trim($sale->customer->nombre . ' ' . ($sale->customer->apellido ?? '')) : 'N/A'); ?></td>
                                    <td>₡<?php echo e(number_format($sale->total, 0, ',', '.')); ?></td>
                                    <td><?php echo e($sale->sale_date->format('d/m/Y H:i')); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo e($sale->status === 'completed' ? 'success' : ($sale->status === 'pending' ? 'warning' : 'danger')); ?>">
                                            <?php echo e(ucfirst($sale->status)); ?>

                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-cart"></i>
                                            <p>No hay ventas recientes</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Acciones Rápidas -->
            <section class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="<?php echo e(route('inventory')); ?>" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h4>Gestionar Productos</h4>
                            <p>Agregar y administrar productos del inventario</p>
                        </div>
                    </a>

                    <a href="<?php echo e(route('sales.index')); ?>" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="action-content">
                            <h4>Gestionar Ventas</h4>
                            <p>Procesar y administrar ventas del sistema</p>
                        </div>
                    </a>

                    <a href="<?php echo e(route('suppliers.create')); ?>" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="action-content">
                            <h4>Nuevo Proveedor</h4>
                            <p>Registrar un nuevo proveedor</p>
                        </div>
                    </a>

                    <a href="<?php echo e(route('products.export')); ?>" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="action-content">
                            <h4>Exportar Datos</h4>
                            <p>Exportar inventario y reportes</p>
                        </div>
                    </a>
                </div>
            </section>
        </div>
    </main>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/dashboard.js']); ?>
</body>

</html>
<?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>