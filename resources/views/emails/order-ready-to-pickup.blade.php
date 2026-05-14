<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido listo para recoger - Ciclo Finca 4</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4f7;font-family:Arial,Helvetica,sans-serif;">
    @php
        $invoiceLabel = $sale->invoice_number ?? '#'.$sale->sale_id;
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4f7;padding:20px 8px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dfe3e8;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#235347;padding:18px 18px;text-align:center;" align="center" valign="middle">
                            <div style="font-size:28px;line-height:1.2;color:#ffffff;font-weight:700;text-align:center;margin:0 auto;">
                                Ciclo Finca 4
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 28px;color:#1f2937;">
                            <p style="margin:0 0 14px 0;font-size:20px;line-height:1.4;"><strong>Hola, {{ $clientName }}.</strong></p>
                            <p style="margin:0 0 18px 0;font-size:18px;line-height:1.6;">
                                ¡Buenas noticias! Su pedido <strong>{{ $invoiceLabel }}</strong> ya está listo para ser retirado en nuestra tienda.
                            </p>

                            @if($sale->saleItems && $sale->saleItems->isNotEmpty())
                                <p style="margin:0 0 8px 0;font-size:16px;font-weight:700;">Productos:</p>
                                <ul style="margin:0 0 18px 0;padding-left:20px;font-size:16px;line-height:1.6;">
                                    @foreach($sale->saleItems as $item)
                                        <li>{{ $item->product ? $item->product->name : 'Producto' }} × {{ $item->quantity }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <p style="margin:0 0 18px 0;font-size:18px;line-height:1.6;">
                                <strong>Total:</strong> ₡{{ number_format((float) $sale->total, 0, ',', '.') }}
                            </p>

                            <p style="margin:0 0 18px 0;font-size:16px;line-height:1.6;">
                                Recuerde traer su número de pedido o identificación al momento de recogerlo.
                            </p>

                            <p style="margin:0;font-size:16px;line-height:1.6;">
                                Puede consultar el estado de sus pedidos en:<br>
                                <a href="{{ $historyUrl }}" style="color:#235347;">{{ $historyUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f3f4f6;border-top:1px solid #dfe3e8;padding:14px 28px;color:#374151;">
                            <p style="margin:0;font-size:14px;">Gracias por comprar en Ciclo Finca 4.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
