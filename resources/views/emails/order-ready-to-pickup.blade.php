@extends('emails.layouts.base')

@section('title', 'Pedido listo para recoger - Ciclo Finca 4')

@section('preheader', 'Tu pedido ya está listo para retirar en nuestra tienda.')

@section('header-title', 'Pedido listo para recoger')

@php
    $invoiceLabel = $sale->invoice_number ?? '#'.$sale->sale_id;
@endphp

@section('content')
    <p><strong>Hola, {{ $clientName }}.</strong></p>

    <p>
        ¡Buenas noticias! Su pedido <strong>{{ $invoiceLabel }}</strong> ya está listo para ser retirado en nuestra tienda.
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

    <p style="margin-top:24px;">
        @include('emails.partials.button', [
            'href' => $invoicesUrl,
            'label' => 'Ver pedido en Facturas',
        ])
    </p>
@endsection

@section('footer-note')
    Gracias por comprar en Ciclo Finca 4.
@endsection
