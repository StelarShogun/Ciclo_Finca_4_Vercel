<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de pedido - Ciclo Finca 4</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333333;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 32px 0;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #2e7d32;
            padding: 28px 40px;
            text-align: center;
        }
        .header img {
            height: 48px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .body {
            padding: 36px 40px;
        }
        .alert-banner {
            background-color: #fff3e0;
            border-left: 4px solid #f57c00;
            border-radius: 4px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #e65100;
            font-weight: 600;
        }
        .greeting {
            font-size: 16px;
            margin: 0 0 16px 0;
        }
        .intro {
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 24px 0;
            color: #555555;
        }
        .order-card {
            background-color: #f9fbe7;
            border: 1px solid #dce775;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .order-card h2 {
            margin: 0 0 14px 0;
            font-size: 15px;
            color: #33691e;
            font-weight: 700;
        }
        .order-meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .order-meta td {
            padding: 5px 0;
        }
        .order-meta td:first-child {
            color: #777777;
            width: 160px;
        }
        .order-meta td:last-child {
            color: #333333;
            font-weight: 600;
        }
        .products-list {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #dce775;
            font-size: 13px;
            color: #555555;
        }
        .products-list ul {
            margin: 6px 0 0 0;
            padding-left: 18px;
        }
        .products-list li {
            margin-bottom: 4px;
        }
        .expiry-highlight {
            background-color: #ffebee;
            border: 1px solid #ef9a9a;
            border-radius: 6px;
            padding: 14px 18px;
            font-size: 14px;
            color: #c62828;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 600;
        }
        .cta {
            text-align: center;
            margin-bottom: 28px;
        }
        .cta p {
            font-size: 14px;
            color: #555555;
            margin: 0 0 14px 0;
            line-height: 1.6;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #e0e0e0;
        }
        .footer a {
            color: #2e7d32;
            text-decoration: none;
        }
        .divider {
            border: none;
            border-top: 1px solid #eeeeee;
            margin: 24px 0;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <!-- HEADER -->
        <div class="header">
            <h1>🌿 Ciclo Finca 4</h1>
        </div>

        <!-- BODY -->
        <div class="body">

            <div class="alert-banner">
                ⏰ Tu pedido vence mañana
            </div>

            <p class="greeting">Hola, <strong>{{ $clientName }}</strong>.</p>

            <p class="intro">
                Te recordamos que tienes un pedido pendiente de retirar en nuestra tienda.
                <strong>Mañana es el último día</strong> para recogerlo antes de que se cancele automáticamente.
            </p>

            <!-- ORDER CARD -->
            <div class="order-card">
                <h2>Resumen del pedido</h2>
                <table class="order-meta">
                    <tr>
                        <td>Número de pedido</td>
                        <td>#{{ $sale->sale_id }}</td>
                    </tr>
                    @if($sale->invoice_number)
                    <tr>
                        <td>Factura</td>
                        <td>{{ $sale->invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Fecha del pedido</td>
                        <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td>₡{{ number_format($sale->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Estado</td>
                        <td>Pendiente de retiro</td>
                    </tr>
                </table>

                @if($sale->saleItems && $sale->saleItems->count() > 0)
                <div class="products-list">
                    <strong>Productos:</strong>
                    <ul>
                        @foreach($sale->saleItems as $item)
                            <li>{{ $item->quantity }} × {{ $item->product->name ?? 'Producto' }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <!-- EXPIRY DATE -->
            <div class="expiry-highlight">
                Fecha límite de retiro: {{ $expiresAt->format('d/m/Y') }}
            </div>

            <!-- CTA -->
            <div class="cta">
                <p>
                    Si tienes alguna consulta sobre tu pedido, no dudes en contactarnos.
                    Puedes responder este correo o visitarnos directamente en la tienda.
                </p>
            </div>

            <hr class="divider">

            <p style="font-size:13px; color:#777777; text-align:center; margin:0;">
                Si ya recogiste tu pedido o no reconoces esta compra, por favor ignora este mensaje.
            </p>

        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p style="margin:0 0 6px 0;">
                © {{ date('Y') }} <strong>Ciclo Finca 4</strong>. Todos los derechos reservados.
            </p>
            <p style="margin:0;">
                Este es un correo automático, por favor no respondas directamente a esta dirección.
            </p>
        </div>

    </div>
</div>
</body>
</html>
