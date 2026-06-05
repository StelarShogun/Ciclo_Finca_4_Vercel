@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Productos más vendidos - Reportes')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/product-sales.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/admin/shell.ts', 'resources/js/admin/reports/product-sales.ts'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $period = $period ?? '30d';
        $sort = $sort ?? 'revenue';
        $dir = $dir ?? 'desc';
        $q = $q ?? '';
    @endphp

    <div id="product-sales-root" class="product-sales-report" data-table-url="{{ route('admin.reports.product-sales.table') }}"
        data-page-url="{{ url('/reports/productos-vendidos') }}" data-period="{{ e($period) }}"
        data-sort="{{ e($sort) }}" data-dir="{{ e($dir) }}" data-top10="{{ e($top10 ?? 'revenue') }}"
        data-initial-q="{{ e($q) }}">
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Productos más vendidos</span>
        </nav>

        @component('admin.partials.page-header', [
            'title' => 'Productos más vendidos',
            'description' =>
                'Analiza los productos con mayor rendimiento por ingresos o unidades vendidas en el periodo seleccionado.',
        ])
        @endcomponent

        <div class="product-sales-toolbar">
            <div class="period-toggle" role="group" aria-label="Periodo">
                <button type="button" class="period-btn {{ $period === '7d' ? 'active' : '' }}" data-period="7d">7
                    días</button>
                <button type="button" class="period-btn {{ $period === '30d' ? 'active' : '' }}" data-period="30d">30
                    días</button>
                <button type="button" class="period-btn {{ $period === '90d' ? 'active' : '' }}" data-period="90d">90
                    días</button>
            </div>
            <div class="search-wrap">
                <label for="product-sales-search" class="sr-only">Filtrar por nombre o SKU</label>
                <input type="search" id="product-sales-search" class="product-sales-search"
                    placeholder="Filtrar por nombre o SKU…" value="{{ e($q) }}" autocomplete="off">
            </div>
        </div>

        <section class="top10-section" aria-labelledby="top10-heading">
            <div class="top10-header">
                <h2 id="top10-heading" class="section-title">Top 10</h2>
                <div class="top10-toggle" role="group" aria-label="Top 10 por">
                    <button type="button" class="top10-btn {{ ($top10 ?? 'revenue') === 'revenue' ? 'is-active' : '' }}"
                        data-top10="revenue">Ingresos</button>
                    <button type="button" class="top10-btn {{ ($top10 ?? 'revenue') === 'units' ? 'is-active' : '' }}"
                        data-top10="units">Unidades</button>
                </div>
            </div>
            <p class="section-hint">Top 10 por <span
                    id="top10-metric-label">{{ ($top10 ?? 'revenue') === 'units' ? 'unidades' : 'ingresos' }}</span> en el
                periodo.</p>
            <div class="table-wrap">
                <table class="report-table admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th class="num">Unidades</th>
                            <th class="num">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody id="top10-body">
                        <tr>
                            <td colspan="5" class="loading-cell">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="full-table-section" aria-labelledby="full-table-heading">
            <h2 id="full-table-heading" class="section-title">Todos los productos con ventas</h2>
            <div class="table-wrap">
                <table class="report-table admin-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th class="num">
                                <button type="button" class="nav-sort {{ $sort === 'units' ? 'is-active' : '' }}"
                                    data-sort="units">
                                    Unidades
                                    @if ($sort === 'units')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="num">
                                <button type="button" class="nav-sort {{ $sort === 'revenue' ? 'is-active' : '' }}"
                                    data-sort="revenue">
                                    Ingresos
                                    @if ($sort === 'revenue')
                                        <i class="fas fa-sort-{{ $dir === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="full-table-body">
                        <tr>
                            <td colspan="4" class="loading-cell">Cargando…</td>
                        </tr>
                    </tbody>
                </table>

                <div id="full-table-pagination" class="pagination-wrapper" aria-live="polite"></div>
            </div>
            <p id="product-sales-empty" class="empty-msg" hidden>No hay ventas completadas en este periodo para los
                criterios seleccionados.</p>

        </section>
    </div>
@endsection
