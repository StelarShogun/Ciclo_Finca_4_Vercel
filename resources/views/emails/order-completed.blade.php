@extends('emails.layouts.base')

@section('title', 'Pedido confirmado - Ciclo Finca 4')

@section('preheader', 'Su pedido fue confirmado como venta completada.')

@section('header-title', 'Pedido confirmado')

@php
    $invoiceLabel = $sale->invoice_number ?? '#'.$sale->sale_id;
@endphp

@section('content')
    <p><strong>Hola, {{ $clientName }}.</strong></p>

    <p>
        Su pedido <strong>{{ $invoiceLabel }}</strong> fue confirmado como venta completada.
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

    <p style="margin-top:24px;">
        @include('emails.partials.button', [
            'href' => $historyUrl,
            'label' => 'Ver historial de compras',
        ])
    </p>
@endsection

@section('footer-note')
    Gracias por comprar en Ciclo Finca 4.
@endsection
