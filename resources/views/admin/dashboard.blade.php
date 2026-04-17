<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Ciclo Finca 4 Admin</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Styles & Fonts --}}
    @vite(['resources/css/admin/dashboard/dashboard.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Chart.js for data visualization --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="dashboard-container">

            {{-- ==================== HEADER ==================== --}}
            <header class="dashboard-header">
                <div class="header-content">

                    <div class="welcome-section">
                        <h1>¡Bienvenido al Dashboard!</h1>
                        <p>Gestión integral del sistema Ciclo Finca 4</p>
                        <div class="current-time" id="current-time"></div>

                        {{-- Data load error notice --}}
                        @if(isset($error))
                            <div class="alert alert-warning alert-inline-error">
                                <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                            </div>
                        @endif
                    </div>

                    <div class="header-actions">
                        <button class="btn btn-primary" id="refresh-dashboard">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>
                    </div>

                </div>
            </header>

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
                        <div class="kpi-change positive" id="sales-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8%</span>
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
                        <div id="category-chart-legend" class="category-chart-legend" role="list" aria-label="Leyenda de categorías"></div>
                    </div>
                </div>

            </section>

            {{-- ==================== DATA TABLES ==================== --}}
            <section class="tables-section">

                {{-- Low stock products table (top 10 strictly below stock_minimum) --}}
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            Top 10 Productos con Stock Bajo
                            @if(($lowStockProducts ?? 0) > 0)
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
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-table" class="tbody-scroll">
                                @forelse(($lowStockProductsList ?? collect())->take(10) as $product)
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                                     alt="{{ $product->name }}"
                                                     class="product-thumb">
                                                <span>{{ $product->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="stock-badge danger">{{ $product->stock_current }}</span>
                                        </td>
                                        <td>{{ $product->stock_minimum }}</td>
                                        <td>
                                            {{-- Porcentaje real respecto al mínimo configurado --}}
                                            @php
                                                $pct = $product->stock_minimum > 0
                                                    ? round(($product->stock_current / $product->stock_minimum) * 100)
                                                    : 0;
                                            @endphp
                                            <span class="status-badge {{ $pct <= 0 ? 'danger' : 'warning' }}"
                                                  title="{{ $pct }}% del mínimo requerido">
                                                {{ $pct <= 0 ? 'Sin Stock' : 'Stock Bajo ('.$pct.'%)' }}
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
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recent-sales-table" class="tbody-scroll">
                                @forelse(($recentSales ?? collect())->take(10) as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</td>
                                        <td>
                                            @if($sale->client)
                                                {{ trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?? '')) }}
                                            @elseif($sale->buyer_name)
                                                {{ $sale->buyer_name }}
                                            @else
                                                Mostrador / sin datos
                                            @endif
                                        </td>
                                        <td>₡{{ number_format($sale->total, 0, ',', '.') }}</td>
                                        <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                        <td>
                                            {{-- Status badge: success / warning / danger --}}
                                            <span class="status-badge {{ $sale->status === 'completed' ? 'success' : ($sale->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($sale->status) }}
                                            </span>
                                        </td>
                                    </tr>
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

                    <a href="{{ route('suppliers.create') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-truck"></i></div>
                        <div class="action-content">
                            <h4>Nuevo Proveedor</h4>
                            <p>Registrar un nuevo proveedor</p>
                        </div>
                    </a>

                </div>
            </section>

        </div>
    </main>

    {{-- Dashboard scripts --}}
    @vite(['resources/js/admin/dashboard/dashboard.js'])


    {{-- ==================== LOW-STOCK TOAST ==================== --}}
    {{-- Se muestra automáticamente al cargar si hay productos bajo su stock mínimo --}}
    @if(($lowStockProducts ?? 0) > 0)
    <div id="low-stock-toast" class="ls-toast ls-toast--visible" role="alert" aria-live="assertive">
        <div class="ls-toast__icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="ls-toast__body">
            <strong class="ls-toast__title">Alerta de inventario</strong>
            <p class="ls-toast__msg">
                {{ $lowStockProducts }} producto{{ $lowStockProducts > 1 ? 's' : '' }}
                {{ $lowStockProducts > 1 ? 'están' : 'está' }} por debajo del stock mínimo configurado.
            </p>
            <a href="{{ route('inventory') }}" class="ls-toast__link">
                Ver inventario <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <button class="ls-toast__close" id="close-low-stock-toast" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <script>
        (function () {
            var toast    = document.getElementById('low-stock-toast');
            var closeBtn = document.getElementById('close-low-stock-toast');
            if (!toast) return;

            function hideToast() {
                toast.classList.add('ls-toast--hiding');
                toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
            }

            var autoTimer = setTimeout(hideToast, 7000);

            closeBtn.addEventListener('click', function () {
                clearTimeout(autoTimer);
                hideToast();
            });
        })();
    </script>
    @endif

</body>
</html>