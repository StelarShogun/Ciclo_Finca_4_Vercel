<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido cancelado - Ciclo Finca 4</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#1f2937;">
    @php
        $configuredAppUrl = rtrim((string) config('app.url', ''), '/');
        $baseUrl = $configuredAppUrl !== '' && !str_contains($configuredAppUrl, 'localhost')
            ? $configuredAppUrl
            : 'https://ciclo-finca-4-app-main.onrender.com';
        $logoUrl = $baseUrl.'/assets/images/brand/logo-ciclo-finca-icon.png';
        $mailContact = (string) config('mail.from.address', 'ciclo.finca4@gmail.com');
    @endphp

    <div style="max-width:620px;margin:20px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        <div style="background:#14532d;padding:20px 24px;text-align:center;">
            <img src="{{ $logoUrl }}" alt="Ciclo Finca 4" style="height:64px;max-width:100%;object-fit:contain;">
            <h1 style="margin:10px 0 0 0;font-size:20px;color:#ffffff;">Ciclo Finca 4</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 14px 0;font-size:16px;"><strong>Hola, {{ $clientName }}.</strong></p>
            <p style="margin:0 0 18px 0;font-size:16px;line-height:1.5;">
                Te informamos que tu pedido fue cancelado.
            </p>

            <div style="background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;padding:14px 16px;margin:0 0 18px 0;">
                <p style="margin:0 0 8px 0;"><strong>Pedido:</strong> #{{ $sale->sale_id }}</p>
                <p style="margin:0 0 8px 0;"><strong>Motivo:</strong> {{ $reason }}</p>
                <p style="margin:0;"><strong>Fecha y hora de cancelación:</strong> {{ $cancelledAt->format('d/m/Y H:i') }}</p>
            </div>

            <p style="margin:0 0 18px 0;font-size:15px;line-height:1.5;">
                Si tienes dudas, puedes contactarnos para brindarte más información.
            </p>
        </div>

        <div style="background:#f3f4f6;border-top:1px solid #e5e7eb;padding:16px 24px;font-size:13px;color:#374151;">
            <p style="margin:0 0 6px 0;"><strong>Contactos Ciclo Finca 4</strong></p>
            <p style="margin:0 0 4px 0;">Correo: {{ $mailContact }}</p>
            <p style="margin:0;">Sitio web: {{ $baseUrl }}</p>
        </div>
    </div>
</body>
</html>
