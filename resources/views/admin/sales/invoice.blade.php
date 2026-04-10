@extends('admin.layouts.sales')

@section('Titulo pagina', 'Factura ' . ($sale->invoice_number ?? $sale->sale_id) . ' — Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/admin/sales/invoice-document.css'])
@endpush

@section('aside')@endsection

@section('contenido')
    <div class="invoice-doc">
        <div class="invoice-doc__toolbar no-print">
            <button type="button" class="btn-print" onclick="window.print()">
                <i class="fas fa-print" aria-hidden="true"></i> Imprimir
            </button>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('sales.index') }}" class="btn-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
            </a>
        </div>

        @include('admin.sales.partials.invoice-sheet', ['sale' => $sale, 'documentKind' => 'invoice'])
    </div>
@endsection
