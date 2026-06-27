@extends('emails.layouts.base')

@section('title', 'Recordatorio de pedido - Ciclo Finca 4')

@section('preheader', 'Tu pedido vence mañana. Recógelo antes de que se cancele automáticamente.')

@section('header-title', 'Recordatorio de pedido')

@section('styles')
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
    .order-card {
        background-color: #f4faf5;
        border: 1px solid #c8e6c9;
        border-radius: 6px;
        padding: 20px 24px;
        margin-bottom: 24px;
    }
    .order-card h2 {
        margin: 0 0 14px 0;
        font-size: 15px;
        color: #235347;
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
        border-top: 1px solid #c8e6c9;
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
    .divider {
        border: none;
        border-top: 1px solid #eeeeee;
        margin: 24px 0;
    }
@endsection

@section('content')
    <div class="alert-banner">
        Tu pedido vence mañana
    </div>

    <p>Hola, <strong>{{ $clientName }}</strong>.</p>

    <p>
        Te recordamos que tienes un pedido pendiente de retirar en nuestra tienda.
        <strong>Mañana es el último día</strong> para recogerlo antes de que se cancele automáticamente.
    </p>

    <div class="order-card">
        <h2>Resumen del pedido</h2>
        <table class="order-meta" role="presentation">
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

    <div class="expiry-highlight">
        Fecha límite de retiro: {{ $expiresAt->format('d/m/Y') }}
    </div>

    <p style="text-align:center;font-size:14px;color:#555555;">
        Si tienes alguna consulta sobre tu pedido, no dudes en contactarnos.
        Puedes visitarnos directamente en la tienda o escribirnos al correo de contacto.
    </p>

    <hr class="divider">

    <p style="font-size:13px;color:#777777;text-align:center;">
        Si ya recogiste tu pedido o no reconoces esta compra, por favor ignora este mensaje.
    </p>
@endsection
