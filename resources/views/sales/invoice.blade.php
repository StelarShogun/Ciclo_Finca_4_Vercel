<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $sale->invoice_number ?? '#' . $sale->sale_id }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
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
                                <img src="{{ asset('assets/images/logo.png') }}" style="width:100%; max-width:300px;" alt="Logo">
                            </td>
                            <td>
                                Invoice: {{ $sale->invoice_number ?? '#' . $sale->sale_id }}<br>
                                Date: {{ $sale->sale_date->format('d/m/Y') }}<br>
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
                                {{ $sale->client
                                    ? trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?: ''))
                                    : ($sale->buyer_name ?: 'Walk-in / Sin datos') }}<br>
                                {{ $sale->client ? ($sale->client->gmail ?: '') : ($sale->buyer_email ?: '') }}<br>
                                N/A
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Payment Method</td>
                <td>{{ ucfirst($sale->payment_method) }}</td>
            </tr>
            <tr class="details">
                <td>{{ ucfirst($sale->payment_method) }}</td>
                <td>{{ number_format($sale->total, 2) }}</td>
            </tr>
            <tr class="heading">
                <td>Product</td>
                <td>Price</td>
            </tr>
            @foreach($sale->saleItems as $item)
            <tr class="item">
                <td>{{ $item->product->name ?? 'N/A' }} (x{{ $item->quantity }})</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total"><td></td><td>Subtotal: {{ number_format($sale->subtotal, 2) }}</td></tr>
            <tr class="total"><td></td><td>IVA: {{ number_format($sale->iva, 2) }}</td></tr>
            <tr class="total"><td></td><td>Discount: {{ number_format($sale->discount, 2) }}</td></tr>
            <tr class="total"><td></td><td>Total: {{ number_format($sale->total, 2) }}</td></tr>
        </table>
    </div>
</body>
</html>
