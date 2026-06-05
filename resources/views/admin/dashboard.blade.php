@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Dashboard - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/dashboard/dashboard.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/admin/dashboard/dashboard.ts'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="dashboard-page">
@component('admin.partials.page-header', ['title' => 'Panel de control'])
                <p>Consulta los indicadores principales de ventas, inventario, proveedores y actividad reciente del sistema.
                </p>
                <p class="current-time" id="current-time"></p>

                @slot('actions')
                    <div class="header-actions">
                        <button class="btn btn-primary" id="refresh-dashboard">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>

                        <button class="btn btn-secondary" id="btn-open-weekly-report-modal">
                            <i class="fas fa-envelope"></i>
                            Reporte semanal
                        </button>
                    </div>
                @endslot
            @endcomponent
            {{-- Data load error notice --}}
            @if (isset($error))
                <div class="alert alert-warning alert-inline-error">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @endif

            {{-- ==================== KPI CARDS ==================== --}}
            <section class="kpis-section">

                {{-- Total products --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Total Productos</h3>
                        <div class="kpi-value" id="total-products">{{ $totalProducts ?? 0 }}</div>
                        <div class="kpi-change positive" id="products-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>
                </div>

                {{-- Today's sales --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Ventas Hoy</h3>
                        <div class="kpi-value" id="today-sales">₡{{ number_format($todaySales ?? 0, 0, ',', '.') }}</div>
                        <div class="kpi-change {{ ($salesTrend ?? 0) >= 0 ? 'positive' : 'negative' }}" id="sales-change">
                            <i class="fas fa-arrow-{{ ($salesTrend ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                            <span>{{ abs($salesTrend ?? 0) }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Total suppliers --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Proveedores</h3>
                        <div class="kpi-value" id="total-suppliers">{{ $totalSuppliers ?? 0 }}</div>
                        <div class="kpi-change neutral" id="suppliers-change">
                            <i class="fas fa-minus"></i>
                            <span>0%</span>
                        </div>
                    </div>
                </div>

                {{-- Low stock alert --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Stock Bajo</h3>
                        <div class="kpi-value" id="low-stock">{{ $lowStockProducts ?? 0 }}</div>
                        <div class="kpi-change negative" id="stock-change">
                            <i class="fas fa-arrow-down"></i>
                            <span>-3%</span>
                        </div>
                    </div>
                </div>

            </section>

            {{-- ==================== CHARTS ==================== --}}
            <section class="charts-section">

                {{-- Sales trend chart with period toggle --}}
                <div class="chart-container chart-container--sales">
                    <div class="chart-header">
                        <h3>Ventas de los Últimos 7 Días</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-period="7d">7 días</button>
                            <button class="chart-btn" data-period="30d">30 días</button>
                            <button class="chart-btn" data-period="90d">90 días</button>
                        </div>
                    </div>
                    <div class="chart-wrapper chart-wrapper--sales">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>

                {{-- Product distribution by category --}}
                <div class="chart-container chart-container--category">
                    <div class="chart-header">
                        <h3>Productos por Categoría</h3>
                    </div>
                    <div class="category-chart-body">
                        <div class="chart-wrapper chart-wrapper--category-donut">
                            <canvas id="category-chart"></canvas>
                        </div>
                        <div id="category-chart-legend" class="category-chart-legend" role="list"
                            aria-label="Leyenda de categorías"></div>
                    </div>
                </div>

            </section>

            {{-- ==================== DATA TABLES ==================== --}}
            <section class="tables-section">

                {{-- Low stock products table (top 10 strictly below stock_minimum) --}}
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            Productos con stock bajo
                            @if (($lowStockProducts ?? 0) > 0)
                                <span class="badge-count">{{ $lowStockProducts }}</span>
                            @endif
                        </h3>
                        <a href="{{ route('inventory') }}" class="btn btn-sm btn-primary">
                            Ver Todos
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content table-content--scroll">
                        <div class="table-scroll-wrapper">
                            <table class="dashboard-table dashboard-table--low-stock admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="dashboard-table__col-product">Producto</th>
                                        <th scope="col" class="dashboard-table__col-num" title="Stock actual">Actual</th>
                                        <th scope="col" class="dashboard-table__col-num" title="Stock mínimo">Mín.</th>
                                        <th scope="col" class="dashboard-table__col-status">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="low-stock-table" class="tbody-scroll">
                                    @forelse($lowStockProductsList ?? collect() as $product)
                                        <tr>
                                            <td class="dashboard-table__col-product">
                                                <div class="product-info">
                                                    @include('shared.media.product-media', [
                                                        'product' => $product,
                                                        'variant' => 'thumb-table',
                                                        'alt' => $product->name,
                                                        'imgClass' => 'product-thumb',
                                                    ])
                                                    <span class="dashboard-table__product-name"
                                                        title="{{ $product->name }}">{{ $product->name }}</span>
                                                </div>
                                            </td>
                                            <td class="dashboard-table__col-num">
                                                <span class="stock-badge danger">{{ $product->stock_current }}</span>
                                            </td>
                                            <td class="dashboard-table__col-num">{{ $product->stock_minimum }}</td>
                                            <td class="dashboard-table__col-status">
                                                <span
                                                    class="status-badge {{ $product->adminDashboardLowStockStatusBadgeClass() }}"
                                                    title="{{ $product->adminDashboardLowStockStatusTitle() }}">
                                                    {{ $product->adminDashboardLowStockStatusLabel() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-check-circle"></i>
                                                    <p>No hay productos con stock bajo</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Recent sales table --}}
                <div class="table-container">
                    <div class="table-header">
                        <h3>Ventas Recientes</h3>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-primary">
                            Ver Todas
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content table-content--scroll">
                        <div class="table-scroll-wrapper">
                            <table class="dashboard-table dashboard-table--recent-sales admin-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="dashboard-table__col-invoice">Factura</th>
                                        <th scope="col" class="dashboard-table__col-client">Cliente</th>
                                        <th scope="col" class="dashboard-table__col-total">Total</th>
                                        <th scope="col" class="dashboard-table__col-date" title="Fecha de venta">Fecha</th>
                                        <th scope="col" class="dashboard-table__col-status">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-sales-table" class="tbody-scroll">
                                    @forelse($recentSales ?? collect() as $sale)
                                        @include('admin.dashboard.partials.recent-sale-row', ['sale' => $sale])
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    <p>No hay ventas recientes</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>

            {{-- ==================== QUICK ACTIONS ==================== --}}
            <section class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">

                    <a href="{{ route('inventory') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-plus"></i></div>
                        <div class="action-content">
                            <h4>Gestionar Productos</h4>
                            <p>Agregar y administrar productos del inventario</p>
                        </div>
                    </a>

                    <a href="{{ route('sales.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-cash-register"></i></div>
                        <div class="action-content">
                            <h4>Gestionar Ventas</h4>
                            <p>Procesar y administrar ventas del sistema</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="action-content">
                            <h4>Ver Reportes</h4>
                            <p>Ingresar al módulo de reportes del sistema</p>
                        </div>
                    </a>

                    <a href="{{ route('suppliers.create') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-truck"></i></div>
                        <div class="action-content">
                            <h4>Nuevo proveedor</h4>
                            <p>Registrar un proveedor en el sistema</p>
                        </div>
                    </a>

                </div>
            </section>

    </div>

    @include('admin.dashboard.partials.low-stock-toast')

    <div id="cf4-toast" class="cf4-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="cf4-toast__icon-wrap">
            <i class="cf4-toast__icon fas"></i>
        </div>
        <div class="cf4-toast__body">
            <strong class="cf4-toast__title"></strong>
            <p class="cf4-toast__msg"></p>
        </div>
        <button class="cf4-toast__close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    @include('admin.dashboard.partials.weekly-report-modal')
@endsection
