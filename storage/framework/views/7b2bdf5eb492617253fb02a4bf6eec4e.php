<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale #<?php echo e($sale->sale_id); ?> - Ciclo Finca #4</title>
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; }
        .header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #2e7d32; margin: 0; }
        .header p { color: #666; margin: 5px 0; }
        .sale-info { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .info-section h3 { color: #2e7d32; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .info-item { display: flex; justify-content: space-between; margin: 10px 0; }
        .products-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .products-table th, .products-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .products-table th { background: #f5f5f5; font-weight: bold; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-row { display: flex; justify-content: flex-end; gap: 20px; margin: 10px 0; padding: 5px 0; }
        .total-final { font-size: 1.2em; font-weight: bold; border-top: 2px solid #2e7d32; padding-top: 10px; margin-top: 10px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 0.9em; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ciclo Finca 4</h1>
        <p>Sale #<?php echo e($sale->sale_id); ?></p>
        <p>Invoice: <?php echo e($sale->invoice_number); ?></p>
        <p>Date: <?php echo e($sale->sale_date->format('d/m/Y H:i')); ?></p>
    </div>

    <div class="sale-info">
        <div class="info-section">
            <h3>Sale Information</h3>
            <div class="info-item"><span>Sale ID:</span><strong>#<?php echo e($sale->sale_id); ?></strong></div>
            <div class="info-item"><span>Status:</span><strong><?php echo e(ucfirst($sale->status)); ?></strong></div>
            <div class="info-item"><span>Payment Method:</span><strong><?php echo e(ucfirst($sale->payment_method)); ?></strong></div>
            <div class="info-item"><span>Seller:</span><strong><?php echo e($sale->seller->nombre ?? $sale->seller->name ?? 'Not assigned'); ?></strong></div>
        </div>
        <div class="info-section">
            <h3>Customer Information</h3>
            <div class="info-item"><span>Name:</span><strong><?php echo e($sale->customer->nombre ?? ''); ?> <?php echo e($sale->customer->apellido ?? ''); ?></strong></div>
            <div class="info-item"><span>Email:</span><strong><?php echo e($sale->customer->email ?? 'N/A'); ?></strong></div>
            <div class="info-item"><span>Phone:</span><strong><?php echo e($sale->customer->telefono ?? $sale->customer->phone ?? 'N/A'); ?></strong></div>
        </div>
    </div>

    <h3>Products</h3>
    <table class="products-table">
        <thead>
            <tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $sale->saleItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item->product->name ?? 'Product not found'); ?></td>
                <td><?php echo e($item->quantity); ?></td>
                <td>₡<?php echo e(number_format($item->unit_price, 0, ',', '.')); ?></td>
                <td>₡<?php echo e(number_format($item->total, 0, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row"><span>Subtotal:</span><span>₡<?php echo e(number_format($sale->subtotal, 0, ',', '.')); ?></span></div>
        <div class="total-row"><span>Discount:</span><span>₡<?php echo e(number_format($sale->discount, 0, ',', '.')); ?></span></div>
        <div class="total-row"><span>IVA:</span><span>₡<?php echo e(number_format($sale->iva, 0, ',', '.')); ?></span></div>
        <div class="total-row total-final"><span>Total:</span><span>₡<?php echo e(number_format($sale->total, 0, ',', '.')); ?></span></div>
    </div>

    <?php if($sale->notes): ?>
    <div class="info-section"><h3>Notes</h3><p><?php echo e($sale->notes); ?></p></div>
    <?php endif; ?>

    <div class="footer">
        <p>Thank you for your purchase at Ciclo Finca 4</p>
        <p>Sarapiquí, Costa Rica</p>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/sales/print.blade.php ENDPATH**/ ?>