@extends('client.layouts.print')

@section('title', 'Imprimir ' . ($sale->invoice_number ?? 'pedido #' . $sale->sale_id) . ' — Ciclo Finca 4')

@section('content')
    @php
        $documentKind = $sale->clientInvoiceDocumentKind();
    @endphp

    <meta name="cf4-auto-print" content="1">

    <div class="invoice-doc invoice-doc--client-print">
        @include('admin.sales.partials.invoice-sheet', [
            'sale' => $sale,
            'documentKind' => $documentKind,
        ])
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/client/invoices-page.js'])
@endpush
