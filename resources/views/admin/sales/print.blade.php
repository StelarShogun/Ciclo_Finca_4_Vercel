@extends('admin.layouts.sales')

@section('Titulo pagina', 'Imprimir ' . ($sale->invoice_number ?? 'pedido #' . $sale->sale_id) . ' — Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/invoice-document.css'])
@endpush

@push('extra-meta')
    <meta name="auto-print" content="1">
    <meta name="invoice-label" content="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}">
@endpush

@section('aside')@endsection

@section('contenido')
    @php
        $documentKind = $sale->status === 'completed' ? 'invoice' : 'receipt';
    @endphp

    <div class="invoice-doc invoice-doc--auto-print">
        <div class="invoice-doc__toolbar no-print">
            <button type="button" class="btn-print" data-confirm-print data-invoice-label="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}">
                <i class="fas fa-print" aria-hidden="true"></i> Imprimir
            </button>
            <a href="{{ route('sales.index') }}" class="btn-back">
                <i class="fas fa-list" aria-hidden="true"></i> Lista de ventas
            </a>
        </div>

        @include('admin.sales.partials.invoice-sheet', ['sale' => $sale, 'documentKind' => $documentKind])
    </div>
@endsection
