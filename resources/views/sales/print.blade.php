<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale #{{ $sale->sale_id }} - Ciclo Finca #4</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
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
        <p>Sale #{{ $sale->sale_id }}</p>
        <p>Invoice: {{ $sale->invoice_number }}</p>
        <p>Date: {{ $sale->sale_date->format('d/m/Y H:i') }}</p>
    </div>

    <div class="sale-info">
        <div class="info-section">
            <h3>Sale Information</h3>
            <div class="info-item"><span>Sale ID:</span><strong>#{{ $sale->sale_id }}</strong></div>
            <div class="info-item"><span>Status:</span><strong>{{ ucfirst($sale->status) }}</strong></div>
            <div class="info-item"><span>Payment Method:</span><strong>{{ ucfirst($sale->payment_method) }}</strong></div>
            <div class="info-item"><span>Seller:</span><strong>
                {{ $sale->sellerAdmin ? trim($sale->sellerAdmin->name . ' ' . $sale->sellerAdmin->first_surname . ' ' . ($sale->sellerAdmin->second_surname ?: '')) : 'Not assigned' }}
            </strong></div>
        </div>
        <div class="info-section">
            <h3>Customer Information</h3>
            <div class="info-item"><span>Name:</span><strong>
                {{ $sale->client
                    ? trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?: ''))
                    : ($sale->buyer_name ?: 'Walk-in / Sin datos') }}
            </strong></div>
            <div class="info-item"><span>Email:</span><strong>
                {{ $sale->client ? ($sale->client->gmail ?: 'N/A') : ($sale->buyer_email ?: 'N/A') }}
            </strong></div>
            <div class="info-item"><span>Phone:</span><strong>N/A</strong></div>
        </div>
    </div>

    <h3>Products</h3>
    <table class="products-table">
        <thead>
            <tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Product not found' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>₡{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>₡{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row"><span>Subtotal:</span><span>₡{{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
        <div class="total-row"><span>Discount:</span><span>₡{{ number_format($sale->discount, 0, ',', '.') }}</span></div>
        <div class="total-row"><span>IVA:</span><span>₡{{ number_format($sale->iva, 0, ',', '.') }}</span></div>
        <div class="total-row total-final"><span>Total:</span><span>₡{{ number_format($sale->total, 0, ',', '.') }}</span></div>
    </div>

    @if($sale->notes)
    <div class="info-section"><h3>Notes</h3><p>{{ $sale->notes }}</p></div>
    @endif

    <div class="footer">
        <p>Thank you for your purchase at Ciclo Finca 4</p>
        <p>Sarapiquí, Costa Rica</p>
    </div>

    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
