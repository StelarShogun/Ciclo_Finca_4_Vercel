@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Desempeño de ventas - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/sales-performance.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/admin/reports/sales-performance.js'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $initialPreset = $initialPreset ?? 'month';
        $initialFrom = $initialFrom ?? '';
        $initialTo = $initialTo ?? '';
    @endphp

    <div
        id="sales-performance-root"
        class="sales-performance-report"
        data-metrics-url="{{ route('admin.reports.sales.metrics') }}"
        data-page-url="{{ url('/reports/desempeno-ventas') }}"
        data-initial-preset="{{ e($initialPreset) }}"
        data-initial-from="{{ e($initialFrom) }}"
        data-initial-to="{{ e($initialTo) }}"
    >
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Desempeño de ventas</span>
        </nav>

        <header class="sales-performance-header">
            <h1>Desempeño de ventas</h1>
            <p class="sales-performance-lead">Totales de órdenes completadas e ingresos facturados, comparados con el periodo anterior de la misma duración.</p>
        </header>

        <div class="sales-perf-layout">
            <aside class="sales-perf-filters" aria-label="Filtros de periodo">
                <p class="sales-perf-filters-title">Periodo</p>
                <div class="period-toggle-wrap">
                    <div class="period-toggle" role="group" aria-label="Opciones de periodo">
                        <button type="button" class="period-btn" data-preset="today">Hoy</button>
                        <button type="button" class="period-btn" data-preset="week">Esta semana</button>
                        <button type="button" class="period-btn" data-preset="month">Este mes</button>
                        <button type="button" class="period-btn" data-preset="year">Este año</button>
                        <button type="button" class="period-btn" data-preset="custom">Personalizado</button>
                    </div>
                </div>
                <div id="sales-custom-range" class="sales-custom-range" hidden>
                    <p class="sales-custom-title">Rango</p>
                    <div class="sales-date-block">
                        <span class="sales-date-block-label">Desde</span>
                        <div class="sales-date-parts" data-date-group="from">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="sales-num-part" id="sales-from-d" placeholder="DD" aria-label="Día desde" autocomplete="off">
                            <span class="sales-date-sep">/</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="sales-num-part" id="sales-from-m" placeholder="MM" aria-label="Mes desde" autocomplete="off">
                            <span class="sales-date-sep">/</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="sales-num-part sales-num-part--year" id="sales-from-y" placeholder="AAAA" aria-label="Año desde" autocomplete="off">
                        </div>
                    </div>
                    <div class="sales-date-block">
                        <span class="sales-date-block-label">Hasta</span>
                        <div class="sales-date-parts" data-date-group="to">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="sales-num-part" id="sales-to-d" placeholder="DD" aria-label="Día hasta" autocomplete="off">
                            <span class="sales-date-sep">/</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="sales-num-part" id="sales-to-m" placeholder="MM" aria-label="Mes hasta" autocomplete="off">
                            <span class="sales-date-sep">/</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="sales-num-part sales-num-part--year" id="sales-to-y" placeholder="AAAA" aria-label="Año hasta" autocomplete="off">
                        </div>
                    </div>
                    <button type="button" class="sales-apply-btn" id="sales-apply-custom">Aplicar rango</button>
                </div>
            </aside>

            <div class="sales-perf-main">
                <div id="sales-performance-error" class="sales-performance-error" role="alert" hidden></div>

                <section class="sales-perf-results" aria-live="polite">
                    <div id="sales-performance-loading" class="sales-performance-loading" hidden>
                        <span class="loading-dot" aria-hidden="true"></span>
                        <span>Cargando datos…</span>
                    </div>

                    <div id="sales-performance-content" class="sales-performance-content" hidden>
                        <div id="sales-empty-state" class="sales-empty-state" hidden>
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <p><strong>No hay ventas completadas</strong> en el periodo elegido. Probá otro rango o revisá más adelante.</p>
                        </div>

                        <div class="sales-metrics-compare-wrap">
                            <div class="sales-metrics-column">
                                <h3 class="sales-col-heading">Periodo elegido</h3>
                                <p class="sales-col-range" id="sales-range-current-label"></p>
                                <div class="sales-metrics-grid">
                                    <article class="sales-metric-card">
                                        <div class="sales-metric-icon" aria-hidden="true"><i class="fas fa-receipt"></i></div>
                                        <h2 class="sales-metric-title">Ventas</h2>
                                        <p class="sales-metric-value" id="sales-metric-count">—</p>
                                        <p class="sales-metric-hint">Órdenes completadas</p>
                                    </article>
                                    <article class="sales-metric-card sales-metric-card--primary">
                                        <div class="sales-metric-icon" aria-hidden="true"><i class="fas fa-coins"></i></div>
                                        <h2 class="sales-metric-title">Ingresos</h2>
                                        <p class="sales-metric-value" id="sales-metric-revenue">—</p>
                                        <p class="sales-metric-hint">Total facturado</p>
                                    </article>
                                </div>
                            </div>
                            <div class="sales-metrics-column sales-metrics-column--previous">
                                <h3 class="sales-col-heading">Periodo anterior <span class="sales-col-heading-note">(misma duración, para comparar)</span></h3>
                                <p class="sales-col-range" id="sales-range-previous-label"></p>
                                <div class="sales-metrics-grid">
                                    <article class="sales-metric-card sales-metric-card--secondary">
                                        <div class="sales-metric-icon" aria-hidden="true"><i class="fas fa-receipt"></i></div>
                                        <h2 class="sales-metric-title">Ventas</h2>
                                        <p class="sales-metric-value" id="sales-prev-metric-count">—</p>
                                        <p class="sales-metric-hint">Órdenes completadas</p>
                                    </article>
                                    <article class="sales-metric-card sales-metric-card--secondary sales-metric-card--primary-soft">
                                        <div class="sales-metric-icon" aria-hidden="true"><i class="fas fa-coins"></i></div>
                                        <h2 class="sales-metric-title">Ingresos</h2>
                                        <p class="sales-metric-value" id="sales-prev-metric-revenue">—</p>
                                        <p class="sales-metric-hint">Total facturado</p>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <section class="sales-comparison-section" aria-labelledby="sales-comparison-heading">
                            <h2 id="sales-comparison-heading" class="sales-comparison-heading sales-comparison-heading--subtle">
                                Diferencia real respecto al periodo anterior
                            </h2>
                            <ul class="sales-comparison-list">
                                <li class="sales-comparison-row">
                                    <span class="sales-comparison-label">Ingresos (actual - anterior)</span>
                                    <span class="sales-comparison-value" id="sales-compare-revenue"></span>
                                </li>
                                <li class="sales-comparison-row">
                                    <span class="sales-comparison-label">Ventas (actual - anterior)</span>
                                    <span class="sales-comparison-value" id="sales-compare-count"></span>
                                </li>
                            </ul>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
