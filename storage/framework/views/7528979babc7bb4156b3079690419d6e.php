<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo e($sale->invoice_number ?? '#' . $sale->sale_id); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #555; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,.15); font-size: 16px; line-height: 24px; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last-item td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <img src="<?php echo e(asset('assets/images/logo.png')); ?>" style="width:100%; max-width:300px;" alt="Logo">
                            </td>
                            <td>
                                Invoice: <?php echo e($sale->invoice_number ?? '#' . $sale->sale_id); ?><br>
                                Date: <?php echo e($sale->sale_date->format('d/m/Y')); ?><br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                Ciclo Finca 4<br>
                                Sarapiquí, Costa Rica<br>
                                info@cicloperez.com
                            </td>
                            <td>
                                <?php echo e($sale->customer->nombre ?? ''); ?> <?php echo e($sale->customer->apellido ?? ''); ?><br>
                                <?php echo e($sale->customer->telefono ?? $sale->customer->phone ?? ''); ?><br>
                                <?php echo e($sale->customer->email ?? ''); ?>

                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Payment Method</td>
                <td><?php echo e(ucfirst($sale->payment_method)); ?></td>
            </tr>
            <tr class="details">
                <td><?php echo e(ucfirst($sale->payment_method)); ?></td>
                <td><?php echo e(number_format($sale->total, 2)); ?></td>
            </tr>
            <tr class="heading">
                <td>Product</td>
                <td>Price</td>
            </tr>
            <?php $__currentLoopData = $sale->saleItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="item">
                <td><?php echo e($item->product->name ?? 'N/A'); ?> (x<?php echo e($item->quantity); ?>)</td>
                <td><?php echo e(number_format($item->total, 2)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <tr class="total"><td></td><td>Subtotal: <?php echo e(number_format($sale->subtotal, 2)); ?></td></tr>
            <tr class="total"><td></td><td>IVA: <?php echo e(number_format($sale->iva, 2)); ?></td></tr>
            <tr class="total"><td></td><td>Discount: <?php echo e(number_format($sale->discount, 2)); ?></td></tr>
            <tr class="total"><td></td><td>Total: <?php echo e(number_format($sale->total, 2)); ?></td></tr>
        </table>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/sales/invoice.blade.php ENDPATH**/ ?>