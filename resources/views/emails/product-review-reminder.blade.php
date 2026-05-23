@extends('emails.layouts.base')

@section('title', 'Reseña de productos comprados - Ciclo Finca 4')

@section('preheader', 'Te invitamos a reseñar tus productos comprados.')

@section('header-title', 'Reseña de productos')

@section('content')
    <p>Estimado {{ $clientName }},</p>

    <p>Favor reseñar {{ $productPhrase }}.</p>

    <p>Para esto, acceda a Facturas &gt; Historial de compras:</p>

    <p style="margin-top:24px;">
        @include('emails.partials.button', [
            'href' => $historyUrl,
            'label' => 'Ir al historial de compras',
        ])
    </p>
@endsection

@section('footer-note')
    Gracias por comprar en Ciclo Finca 4.
@endsection
