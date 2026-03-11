<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos - <?php echo e($fecha_exportacion); ?></title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c5530;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2c5530;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .info-section {
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2c5530;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c5530;
        }
        
        .info-value {
            color: #666;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px;
        }
        
        .table th {
            background-color: #2c5530;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1a3d1f;
        }
        
        .table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .table tr:hover {
            background-color: #e9ecef;
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-discontinued {
            color: #6c757d;
            font-weight: bold;
        }
        
        .stock-high {
            color: #28a745;
            font-weight: bold;
        }
        
        .stock-medium {
            color: #ffc107;
            font-weight: bold;
        }
        
        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }
        
        .price {
            text-align: right;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Inventario de Productos</h1>
        <p class="subtitle">Sistema de Gestión de Inventario - Ciclo Finca 4</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha de Exportación:</span>
            <span class="info-value"><?php echo e($fecha_exportacion); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Total de Productos:</span>
            <span class="info-value"><?php echo e($total); ?> productos</span>
        </div>
        <div class="info-row">
            <span class="info-label">Generado por:</span>
            <span class="info-value">Sistema de Inventario</span>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 25%;">Producto</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Proveedor</th>
                <th style="width: 8%;">Stock</th>
                <th style="width: 8%;">Mínimo</th>
                <th style="width: 10%;">Precio Compra</th>
                <th style="width: 10%;">Precio Venta</th>
                <th style="width: 8%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="text-center"><?php echo e($product->id); ?></td>
                <td>
                    <strong><?php echo e($product->name); ?></strong>
                    <?php if($product->description && $product->description !== 'No description'): ?>
                        <br><small style="color: #666;"><?php echo e(Str::limit($product->description, 50)); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo e($product->category); ?></td>
                <td><?php echo e($product->supplier); ?></td>
                <td class="text-center">
                    <?php if($product->stock_current > 10): ?>
                        <span class="stock-high"><?php echo e($product->stock_current); ?></span>
                    <?php elseif($product->stock_current > 0): ?>
                        <span class="stock-medium"><?php echo e($product->stock_current); ?></span>
                    <?php else: ?>
                        <span class="stock-low"><?php echo e($product->stock_current); ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?php echo e($product->stock_minimum); ?></td>
                <td class="price">₡<?php echo e($product->purchase_price); ?></td>
                <td class="price">₡<?php echo e($product->sale_price); ?></td>
                <td class="text-center">
                    <?php if($product->status === 'Active'): ?>
                        <span class="status-active"><?php echo e($product->status); ?></span>
                    <?php elseif($product->status === 'Inactive'): ?>
                        <span class="status-inactive"><?php echo e($product->status); ?></span>
                    <?php else: ?>
                        <span class="status-discontinued"><?php echo e($product->status); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="9" class="text-center">No hay productos para mostrar</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p class="mb-0">Este reporte fue generado automáticamente el <?php echo e($fecha_exportacion); ?></p>
        <p class="mb-0">Sistema de Gestión de Inventario - Ciclo Finca 4</p>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/products/products-pdf.blade.php ENDPATH**/ ?>