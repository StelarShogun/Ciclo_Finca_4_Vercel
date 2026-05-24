@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Movimientos — ' . $product->name . ' - Reportes')

@push('vite-body')
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/reports/inventory-movements.js'])
@endpush

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/reports-hub.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $currentType   = request('type', '');
        $currentOrigin = request('origin', '');
        $currentFrom   = request('date_from', '');
        $currentTo     = request('date_to', '');
    @endphp

    <div
        id="inventory-movements-root"
        class="inventory-movements-report"
        data-movements-url="{{ route('admin.inventory.movements.json', $product->product_id) }}"
        data-page-url="{{ route('admin.inventory.movements.show', $product->product_id) }}"
        data-initial-type="{{ e($currentType) }}"
        data-initial-origin="{{ e($currentOrigin) }}"
        data-initial-from="{{ e($currentFrom) }}"
        data-initial-to="{{ e($currentTo) }}"
    >

        {{-- Reports breadcrumb --}}
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <a href="{{ route('admin.inventory.movements.index') }}">Movimientos de inventario</a>
            <span class="sep">/</span>
            <span>{{ $product->name }}</span>
        </nav>

        {{-- Product header --}}
@component('admin.partials.page-header', ['title' => 'Movimientos de inventario de ' . $product->name])
    <p>
        Consulta el historial detallado de entradas, salidas, ajustes y devoluciones registradas para este producto.
        <br>
        Stock actual: <strong class="stock-badge stock-badge--{{ $product->adminInventoryStockBadgeClass() }}">{{ number_format($product->stock_current) }} unid.</strong>
    </p>

    @slot('actions')
        <a href="{{ route('admin.inventory.movements.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    @endslot
@endcomponent

        <div class="inv-mov-layout">

            {{-- Filter sidebar --}}
            <aside class="inv-mov-filters" aria-label="Filtros de movimientos">

                {{-- Movement type filter --}}
                <p class="inv-mov-filters-title">Tipo</p>
                <div class="inv-mov-toggle-wrap">
                    <div class="inv-mov-toggle" role="group" aria-label="Tipo de movimiento">
                        <button type="button" class="inv-mov-btn" data-filter="type" data-value="">Todos</button>
                        @foreach($availableTypes as $t)
                            <button type="button" class="inv-mov-btn" data-filter="type" data-value="{{ $t->value }}">
                                {{ $t->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Movement origin filter --}}
                <p class="inv-mov-filters-title inv-mov-filters-title--spaced">Origen</p>
                <div class="inv-mov-toggle-wrap">
                    <div class="inv-mov-toggle" role="group" aria-label="Origen del movimiento">
                        <button type="button" class="inv-mov-btn" data-filter="origin" data-value="">Todos</button>
                        @foreach($availableOrigins as $o)
                            <button type="button" class="inv-mov-btn" data-filter="origin" data-value="{{ $o }}">
                                {{ ucwords(str_replace('_', ' ', $o)) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Date range filter --}}
                <p class="inv-mov-filters-title inv-mov-filters-title--spaced">Rango de fechas</p>
                <div class="inv-mov-date-block">
                    <span class="inv-mov-date-label">Desde</span>
                    <div class="inv-mov-date-parts" data-date-group="from">
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                               class="inv-mov-num-part" id="inv-from-d" placeholder="DD"
                               aria-label="Día desde" autocomplete="off">
                        <span class="inv-mov-date-sep">/</span>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                               class="inv-mov-num-part" id="inv-from-m" placeholder="MM"
                               aria-label="Mes desde" autocomplete="off">
                        <span class="inv-mov-date-sep">/</span>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                               class="inv-mov-num-part inv-mov-num-part--year" id="inv-from-y" placeholder="AAAA"
                               aria-label="Año desde" autocomplete="off">
                    </div>
                </div>
                <div class="inv-mov-date-block">
                    <span class="inv-mov-date-label">Hasta</span>
                    <div class="inv-mov-date-parts" data-date-group="to">
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                               class="inv-mov-num-part" id="inv-to-d" placeholder="DD"
                               aria-label="Día hasta" autocomplete="off">
                        <span class="inv-mov-date-sep">/</span>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                               class="inv-mov-num-part" id="inv-to-m" placeholder="MM"
                               aria-label="Mes hasta" autocomplete="off">
                        <span class="inv-mov-date-sep">/</span>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                               class="inv-mov-num-part inv-mov-num-part--year" id="inv-to-y" placeholder="AAAA"
                               aria-label="Año hasta" autocomplete="off">
                    </div>
                </div>
                <button type="button" class="inv-mov-apply-btn" id="inv-filter-today">Hoy</button>
                <button type="button" class="inv-mov-apply-btn" id="inv-apply-dates">Aplicar fechas</button>
                <button type="button" class="inv-mov-clear-btn" id="inv-clear-filters">Limpiar filtros</button>

            </aside>

            {{-- Main content --}}
            <div class="inv-mov-main">

                {{-- Request error container --}}
                <div id="inventory-movements-error" class="inventory-movements-error" role="alert" hidden></div>

                <section id="inv-mov-results" class="inv-mov-results" aria-live="polite">

                    {{-- Loading state --}}
                    <div id="inventory-movements-loading" hidden></div>

                    <div id="inventory-movements-content" class="inventory-movements-content" hidden>

                        {{-- Empty state --}}
                        <div id="inv-empty-state" class="inv-empty-state" hidden>
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <p><strong>No hay movimientos registrados</strong> con los filtros aplicados. Probá otro rango u otros criterios.</p>
                        </div>

                        {{-- Summary metrics --}}
                        <div class="inv-metrics-grid">
                            <article class="inv-metric-card">
                                <div class="inv-metric-icon" aria-hidden="true"><i class="fas fa-arrows-up-down"></i></div>
                                <h2 class="inv-metric-title">Total movimientos</h2>
                                <p class="inv-metric-value" id="inv-metric-total">—</p>
                                <p class="inv-metric-hint">En el periodo filtrado</p>
                            </article>
                            <article class="inv-metric-card inv-metric-card--entrada">
                                <div class="inv-metric-icon" aria-hidden="true"><i class="fas fa-arrow-down"></i></div>
                                <h2 class="inv-metric-title">Unidades ingresadas</h2>
                                <p class="inv-metric-value" id="inv-metric-entradas">—</p>
                                <p class="inv-metric-hint">Entradas + devoluciones</p>
                            </article>
                            <article class="inv-metric-card inv-metric-card--salida">
                                <div class="inv-metric-icon" aria-hidden="true"><i class="fas fa-arrow-up"></i></div>
                                <h2 class="inv-metric-title">Unidades salidas</h2>
                                <p class="inv-metric-value" id="inv-metric-salidas">—</p>
                                <p class="inv-metric-hint">Salidas registradas</p>
                            </article>
                        </div>

                        {{-- Movements table --}}
                        <section class="inv-table-section" aria-labelledby="inv-table-heading">
                            <h2 id="inv-table-heading" class="inv-table-heading">
                                Detalle de movimientos
                                <span class="inv-table-badge" id="inv-table-count"></span>
                            </h2>

                            <div class="inv-table-wrap">
                                <table class="inv-table admin-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha y hora</th>
                                            <th>Tipo</th>
                                            <th>Origen</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Stock antes</th>
                                            <th class="text-end">Stock después</th>
                                            <th>Administrador</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inv-table-body">
                                        {{-- Rows are injected by JavaScript --}}
                                    </tbody>
                                </table>
                            </div>

                            {{-- JS-rendered pagination --}}
                            <div class="inv-pagination" id="inv-pagination" hidden>
                                <div id="inv-pagination-controls"></div>
                            </div>
                        </section>

                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection