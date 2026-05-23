@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Compras por cliente - Reportes')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/client-purchase-history.css', 'resources/css/admin/sales/sales.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/reports/client-purchase-client-show.js'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $listQuery = array_filter(
            [
                'period' => $backParams['back_period'] ?? '30d',
                'sort' => $backParams['back_sort'] ?? null,
                'dir' => $backParams['back_dir'] ?? null,
                'page' => $backParams['back_page'] ?? null,
                'per_page' => $backParams['back_per_page'] ?? null,
                'q' => $backParams['back_q'] ?? null,
            ],
            fn($v) => $v !== null && $v !== '',
        );
        $listUrl = route('admin.reports.client-purchases', $listQuery);
    @endphp

    <div id="client-purchase-client-show-root" class="client-purchases-report client-purchases-client-show"
        data-sale-json-url-template="{{ url('/sales') }}/__SALE__">
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <a href="{{ $listUrl }}">Compras por cliente</a>
            <span class="sep">/</span>
            <span>{{ $displayName }}</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Historial de compras del cliente'])
            <p class="client-purchases-show-meta">
                Consulta las ventas completadas registradas para <strong>{{ $displayName }}</strong>.
                @if ($gmail)
                    <span class="client-purchases-show-email">{{ $gmail }}</span>
                @endif
            </p>

            @slot('actions')
                <a href="{{ $listUrl }}" class="btn-back-to-list">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al listado
                </a>
            @endslot
        @endcomponent

        <section class="client-purchases-section" aria-labelledby="orders-heading">
            <h2 id="orders-heading" class="section-title">Todas las ventas completadas</h2>
            @if ($orders->isEmpty())
                <p class="empty-msg">Este cliente no tiene ventas completadas registradas.</p>
            @else
                <div class="table-wrap">
                    <table class="report-table client-purchases-show-table admin-table">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Fecha</th>
                                <th class="num">Total</th>
                                <th class="col-actions admin-table__col--actions" scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $dt = \Carbon\Carbon::parse($order->sale_date, config('app.timezone'));
                                @endphp
                                <tr>
                                    <td><code class="client-orders-invoice">{{ $order->invoice_number }}</code></td>
                                    <td>{{ $dt->format('d/m/Y H:i') }}</td>
                                    <td class="num">₡{{ number_format((float) $order->total, 0, ',', '.') }}</td>
                                    <td class="col-actions">
                                        <button type="button" class="btn-ver-venta btn-open-sale-detail"
                                            data-sale-id="{{ (int) $order->sale_id }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <dialog id="sale-detail-dialog" class="client-orders-dialog client-sale-detail-dialog"
            aria-labelledby="sale-detail-dialog-title">
            <div class="client-orders-dialog-inner">
                <header class="client-orders-dialog-header">
                    <div class="client-orders-dialog-title-wrap">
                        <h2 id="sale-detail-dialog-title">Detalle de la venta</h2>
                        <p class="client-orders-dialog-hint">Resumen de la factura y líneas de producto.</p>
                    </div>
                    <button type="button" class="client-orders-dialog-close" id="sale-detail-dialog-close"
                        aria-label="Cerrar">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </header>
                <div id="sale-detail-dialog-body" class="sale-detail-dialog-body">
                    <p class="loading-cell">Cargando…</p>
                </div>
            </div>
        </dialog>
    </div>
@endsection
