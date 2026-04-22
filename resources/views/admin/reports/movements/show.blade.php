{{-- resources/views/admin/reports/movements/show.blade.php --}}
{{--
  Vista: historial de movimientos de inventario de un producto.
  Estructura y convenciones alineadas con sales-performance.blade.php:
    - Breadcrumb de navegación
    - Header con título y subtítulo lead
    - Layout de dos columnas: filtros (aside) + resultados (main)
    - Métricas en tarjetas comparables
    - Estado vacío y estado de carga coherentes
--}}

@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Movimientos — ' . $product->name . ' - Reportes')


@push('vite-body')
    @vite(['resources/js/admin/reports/inventory-movements.js'])
@endpush

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css'])
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

        {{-- ── Breadcrumb ─────────────────────────────────────────────── --}}
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <a href="{{ route('admin.inventory.movements.index') }}">Movimientos de inventario</a>
            <span class="sep">/</span>
            <span>{{ $product->name }}</span>
        </nav>

        {{-- ── Header ─────────────────────────────────────────────────── --}}
        <header class="inventory-movements-header">
            <div class="inventory-movements-header-meta">
                <span class="inventory-movements-sku">{{ \App\Models\Product::skuFromId($product->product_id) }}</span>
                @if($product->category)
                    <span class="inventory-movements-category">{{ $product->category->name }}</span>
                @endif
            </div>
            <h1>{{ $product->name }}</h1>
            <p class="inventory-movements-lead">
                Historial de movimientos de inventario: entradas, salidas y devoluciones registradas.
                El stock actual es
                <strong class="stock-badge stock-badge--{{ $product->adminInventoryStockBadgeClass() }}">
                    {{ number_format($product->stock_current) }} unid.
                </strong>
            </p>
        </header>

        <div class="inv-mov-layout">

            {{-- ── Filtros (aside) ────────────────────────────────────── --}}
            <aside class="inv-mov-filters" aria-label="Filtros de movimientos">

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
                <button type="button" class="inv-mov-apply-btn" id="inv-apply-dates">Aplicar fechas</button>
                <button type="button" class="inv-mov-clear-btn" id="inv-clear-filters">Limpiar filtros</button>

            </aside>

            {{-- ── Contenido principal ────────────────────────────────── --}}
            <div class="inv-mov-main">

                <div id="inventory-movements-error" class="inventory-movements-error" role="alert" hidden></div>

                <section id="inv-mov-results" class="inv-mov-results" aria-live="polite">

                    <div id="inventory-movements-loading" class="inventory-movements-loading" hidden>
                        <span class="loading-dot" aria-hidden="true"></span>
                        <span>Cargando movimientos…</span>
                    </div>

                    <div id="inventory-movements-content" class="inventory-movements-content" hidden>

                        {{-- Estado vacío --}}
                        <div id="inv-empty-state" class="inv-empty-state" hidden>
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            <p><strong>No hay movimientos registrados</strong> con los filtros aplicados. Probá otro rango u otros criterios.</p>
                        </div>

                        {{-- Tarjetas de resumen --}}
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

                        {{-- Tabla de movimientos --}}
                        <section class="inv-table-section" aria-labelledby="inv-table-heading">
                            <h2 id="inv-table-heading" class="inv-table-heading">
                                Detalle de movimientos
                                <span class="inv-table-badge" id="inv-table-count"></span>
                            </h2>

                            <div class="inv-table-wrap">
                                <table class="inv-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha y hora</th>
                                            <th>Tipo</th>
                                            <th>Origen</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Stock antes</th>
                                            <th class="text-end">Stock después</th>
                                            <th>Administrador</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inv-table-body">
                                        {{-- Filas inyectadas por JS --}}
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginación — mismo componente que x-pagination, renderizado por JS --}}
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