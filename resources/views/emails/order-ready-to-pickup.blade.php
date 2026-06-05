@extends('emails.layouts.base')

@section('title', 'Pedido listo para recoger - Ciclo Finca 4')

@section('preheader', 'Tu pedido ya está listo para retirar en nuestra tienda.')

@section('header-title', 'Pedido listo para recoger')

@php
    use App\Services\Client\Storefront\ClientPickupPolicy;

    $invoiceLabel = $sale->invoice_number ?? '#'.$sale->sale_id;
@endphp

@section('content')
    <p><strong>Hola, {{ $clientName }}.</strong></p>

    <p>
        ¡Buenas noticias! Su pedido <strong>{{ $invoiceLabel }}</strong> ya está listo para ser retirado en nuestra tienda.
    </p>

    <p style="margin:0 0 14px 0;padding:12px 14px;background:#ecfdf5;border:1px solid #86efac;border-radius:8px;color:#14532d;">
        <strong>Plazo de retiro:</strong> {{ ClientPickupPolicy::summaryLine() }}
        {{ ClientPickupPolicy::expiryConsequenceLine() }}
    </p>

    @if($sale->saleItems && $sale->saleItems->isNotEmpty())
        <p><strong>Productos:</strong></p>
        <ul style="margin:0 0 18px 0;padding-left:20px;">
            @foreach($sale->saleItems as $item)
                <li>{{ $item->product ? $item->product->name : 'Producto' }} × {{ $item->quantity }}</li>
            @endforeach
        </ul>
    @endif

    <p><strong>Total:</strong> ₡{{ number_format((float) $sale->total, 0, ',', '.') }}</p>

    <p>Recuerde traer su número de pedido o identificación al momento de recogerlo.</p>

    <div style="margin-top:24px;">
        @include('emails.partials.button', [
            'href' => $invoicesUrl,
            'label' => 'Ver pedido en Facturas',
        ])
    </div>
@endsection

@section('footer-note')
    Gracias por comprar en Ciclo Finca 4.
@endsection
