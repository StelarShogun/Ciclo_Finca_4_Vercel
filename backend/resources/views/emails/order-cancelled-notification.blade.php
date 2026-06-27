@extends('emails.layouts.base')

@section('title', 'Pedido cancelado - Ciclo Finca 4')

@section('preheader', 'Te informamos que tu pedido fue cancelado.')

@section('header-title', 'Pedido cancelado')

@section('styles')
    .order-summary {
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        background-color: #f4faf5;
        padding: 16px 18px;
        margin: 20px 0;
    }
    .order-summary div {
        margin-bottom: 8px;
    }
    .order-summary div:last-child {
        margin-bottom: 0;
    }
@endsection

@section('content')
    <p><strong>Hola, {{ $clientName }}.</strong></p>

    <p>Te informamos que tu pedido fue cancelado.</p>

    <div class="order-summary">
        <div><strong>Pedido:</strong> #{{ $sale->sale_id }}</div>
        <div><strong>Motivo:</strong> {{ $reason }}</div>
        <div><strong>Fecha y hora de cancelación:</strong> {{ $cancelledAt->format('d/m/Y H:i') }}</div>
    </div>

    <p>Si tienes dudas, puedes contactarnos para brindarte más información.</p>
@endsection
