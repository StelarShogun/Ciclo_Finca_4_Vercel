@extends('admin.layouts.sales')

@section('Titulo pagina', 'Factura ' . ($sale->invoice_number ?? $sale->sale_id) . ' — Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/admin/fonts.css', 'resources/css/admin/fontawesome.css', 'resources/css/admin/sales/invoice-document.css'])
@endpush

@push('scripts')
    @vite(['resources/ts/admin/sales/invoice-print.ts'])
@endpush

@push('extra-meta')
    <meta name="invoice-label" content="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}">
@endpush

@section('aside')@endsection

@section('contenido')
    <div class="invoice-doc">
        <div class="invoice-doc__toolbar no-print">
            <button type="button" class="btn-print" data-confirm-print data-invoice-label="{{ $sale->invoice_number ?? '#' . $sale->sale_id }}">
                <i class="fas fa-print" aria-hidden="true"></i> Imprimir
            </button>
            @php
                $previousUrl  = url()->previous();
                $currentUrl   = url()->current();
                // Avoid navigating back to the sales "show" route, which returns JSON (/sales/{id}).
                $previousPath = is_string($previousUrl) ? parse_url($previousUrl, PHP_URL_PATH) : null;
                $looksLikeJsonSaleShow = is_string($previousPath)
                    && preg_match('~/(?:admin/)?sales/\d+/?$~', $previousPath) === 1;
                $historyUrl = ($previousUrl && $previousUrl !== $currentUrl && ! $looksLikeJsonSaleShow)
                    ? $previousUrl
                    : '/admin/sales';

                $backUrl = match(request('from')) {
                    'orders' => '/admin/orders',
                    'sales'  => '/admin/sales',
                    default  => $historyUrl,
                };
            @endphp

            <a href="{{ $backUrl }}" class="btn-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
            </a>
        </div>

        @include('admin.sales.partials.invoice-sheet', ['sale' => $sale, 'documentKind' => 'invoice'])
    </div>
@endsection
