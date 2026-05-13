<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido cancelado - Ciclo Finca 4</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f4f7;font-family:Arial,Helvetica,sans-serif;">
    @php
        $mailContact = (string) config('mail.from.address', 'ciclo.finca4@gmail.com');
        $siteUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
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
                                Te informamos que tu pedido fue cancelado.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:18px;line-height:1.6;color:#1f2937;">
                                        <div style="margin-bottom:8px;"><strong>Pedido:</strong> #{{ $sale->sale_id }}</div>
                                        <div style="margin-bottom:8px;"><strong>Motivo:</strong> {{ $reason }}</div>
                                        <div><strong>Fecha y hora de cancelación:</strong> {{ $cancelledAt->format('d/m/Y H:i') }}</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0 0;font-size:18px;line-height:1.6;">
                                Si tienes dudas, puedes contactarnos para brindarte más información.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f3f4f6;border-top:1px solid #dfe3e8;padding:14px 28px;color:#374151;">
                            <p style="margin:0 0 8px 0;font-size:16px;"><strong>Contactos Ciclo Finca 4</strong></p>
                            <p style="margin:0 0 6px 0;font-size:16px;">Correo: <a href="mailto:{{ $mailContact }}" style="color:#235347;">{{ $mailContact }}</a></p>
                            <p style="margin:0;font-size:16px;">Sitio web: <a href="{{ $siteUrl }}" style="color:#235347;">{{ $siteUrl }}</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
