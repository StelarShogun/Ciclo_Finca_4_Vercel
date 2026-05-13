@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Compras por cliente - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/client-purchase-history.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/admin/reports/client-purchase-history.js'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $period = $period ?? '30d';
        $sort = $sort ?? 'total_purchased';
        $dir = $dir ?? 'desc';
        $q = $q ?? '';
    @endphp

    <div
        id="client-purchases-root"
        class="client-purchases-report"
        data-table-url="{{ route('admin.reports.client-purchases.table') }}"
        data-show-url-template="{{ url('/reports/client-purchases') }}/__CLIENT__"
        data-page-url="{{ url('/reports/client-purchases') }}"
        data-period="{{ e($period) }}"
        data-sort="{{ e($sort) }}"
        data-dir="{{ e($dir) }}"
        data-initial-q="{{ e($q) }}"
    >
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Compras por cliente</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Historial de compras por cliente'])
        @endcomponent

        <div class="client-purchases-toolbar">
            <div class="period-toggle" role="group" aria-label="Periodo">
                <button type="button" class="period-btn {{ $period === '7d' ? 'active' : '' }}" data-period="7d">7 días</button>
                <button type="button" class="period-btn {{ $period === '30d' ? 'active' : '' }}" data-period="30d">30 días</button>
                <button type="button" class="period-btn {{ $period === '90d' ? 'active' : '' }}" data-period="90d">90 días</button>
            </div>
            <div class="search-wrap">
                <label for="client-purchases-search" class="sr-only">Buscar por nombre, apellido o correo</label>
                <input type="search" id="client-purchases-search" class="client-purchases-search" placeholder="Nombre, apellido o correo…" value="{{ e($q) }}" autocomplete="off">
            </div>
        </div>

        <section class="client-purchases-section" aria-labelledby="table-heading">
            <h2 id="table-heading" class="section-title">Clientes con compras en el periodo</h2>
            <div class="table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Correo</th>
                            <th class="num">
                                <button type="button" class="nav-sort {{ $sort === 'total_purchased' ? 'is-active' : '' }}" data-sort="total_purchased">
                                    Total comprado
                                    @if ($sort === 'total_purchased')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="num">
                                <button type="button" class="nav-sort {{ $sort === 'orders_count' ? 'is-active' : '' }}" data-sort="orders_count">
                                    Compras
                                    @if ($sort === 'orders_count')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="num">
                                <button type="button" class="nav-sort {{ $sort === 'avg_ticket' ? 'is-active' : '' }}" data-sort="avg_ticket">
                                    Ticket promedio
                                    @if ($sort === 'avg_ticket')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="col-actions" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="client-purchases-body">
                        <tr><td colspan="6" class="loading-cell">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="client-purchases-pagination" class="pagination-wrapper" aria-live="polite"></div>
            <p id="client-purchases-empty" class="empty-msg" hidden>No hay clientes con compras completadas en este periodo para los criterios seleccionados.</p>
        </section>
    </div>
@endsection
