@extends('admin.layouts.admin-shell')
@section('Titulo pagina', 'Ventas por Categoría')

@push('styles')
    @vite(['resources/css/admin/sales/sales.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
<div class="sales-container">

    <nav class="reports-breadcrumb" aria-label="Migas de pan">
        <a href="{{ route('admin.reports.index') }}">Reportes</a>
        <span class="sep">/</span>
        <span>Ventas por Categoría</span>
    </nav>

    {{-- ==================== HEADER ==================== --}}
    <header class="sales-header">
        <div>
            <h1>Ventas por Categoría</h1>
            <p>Análisis de participación por línea de producto.</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Reportes
        </a>
    </header>

    {{-- ==================== FILTROS ==================== --}}
    <div class="filters-section">
        <div class="filters-header">
            <h2 class="filters-title">Filtros de Búsqueda</h2>
        </div>
        <form method="GET" action="{{ route('sales.reports.byCategory') }}" id="filters-form">
            <div class="filters-grid">

                <div class="filter-group">
                    <label for="date-range">Rango de Fecha</label>
                    <select id="date-range" name="date_range">
                        <option value="today"  {{ $dateRange == 'today'  ? 'selected' : '' }}>Hoy</option>
                        <option value="week"   {{ $dateRange == 'week'   ? 'selected' : '' }}>Esta semana</option>
                        <option value="month"  {{ $dateRange == 'month'  ? 'selected' : '' }}>Este mes</option>
                        <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </div>

                <div class="filter-group" id="custom-from" style="{{ $dateRange == 'custom' ? '' : 'display:none' }}">
                    <label for="date_from">Desde</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="filter-group" id="custom-to" style="{{ $dateRange == 'custom' ? '' : 'display:none' }}">
                    <label for="date_to">Hasta</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>

                <div class="filter-group filter-buttons">
                    <button type="submit" class="btn btn-primary filter-btn">
                        <i class="fas fa-search"></i> Aplicar
                    </button>
                    <a href="{{ route('sales.reports.byCategory') }}" class="btn btn-secondary filter-btn">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>

            </div>
        </form>
    </div>

    {{-- ==================== CONTENIDO PRINCIPAL ==================== --}}
    @if($rows->isEmpty())

        <div class="alert alert-info report-empty-state">
            <i class="fas fa-info-circle fa-2x"></i>
            No hay ventas confirmadas en el periodo seleccionado.
        </div>

    @else

        {{-- KPIs --}}
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Ingresos del Periodo</h3>
                    <div class="kpi-icon success"><i class="fas fa-dollar-sign"></i></div>
                </div>
                <p class="kpi-value">₡{{ number_format($grandTotal, 0, ',', '.') }}</p>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Categorías Activas</h3>
                    <div class="kpi-icon info"><i class="fas fa-tags"></i></div>
                </div>
                <p class="kpi-value">{{ $rows->count() }}</p>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3 class="kpi-title">Unidades Vendidas</h3>
                    <div class="kpi-icon"><i class="fas fa-box"></i></div>
                </div>
                <p class="kpi-value">{{ number_format($rows->sum('total_units'), 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Gráfica + Tabla --}}
        <div class="report-content-grid">

            {{-- Pie chart --}}
            <div class="sales-table-container report-chart-panel">
                <h3>Distribución por Categoría</h3>
                <canvas id="category-chart" data-chart="{{ json_encode($chartData) }}"></canvas>
            </div>

            {{-- Tabla --}}
            <div class="report-table-panel">
                <div class="sales-table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-right">Ingresos</th>
                                <th class="text-right">Participación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                            <tr>
                                <td>{{ $row->category_name }}</td>
                                <td class="text-center">{{ number_format($row->total_units, 0, ',', '.') }}</td>
                                <td class="text-right">₡{{ number_format($row->total_revenue, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <span class="pct-cell">
                                        <span class="pct-label">{{ $row->percentage }}%</span>
                                        <span class="pct-bar-track">
                                            <span class="pct-bar-fill" style="width:{{ $row->percentage }}%"></span>
                                        </span>
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="tfoot-total">
                                <td>Total</td>
                                <td class="text-center">{{ number_format($rows->sum('total_units'), 0, ',', '.') }}</td>
                                <td class="text-right">₡{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td class="text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    @endif

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @vite(['resources/js/admin/sales/reports-by-category.js'])
@endpush