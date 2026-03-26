<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Dashboard - {{ now()->format('d/m/Y') }}</title>
    @vite(['resources/css/admin/dashboard/dashboard-pdf.css'])
</head>
<body>

    {{-- Report header --}}
    <div class="header">
        <h1>Reporte del Dashboard</h1>
        <p>Sistema de Gestión Ciclo Finca 4</p>
        <p>Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}</p>
    </div>

    {{-- KPI summary cards --}}
    <div class="section">
        <h2>Resumen Ejecutivo</h2>
        <div class="kpis-grid">
            <div class="kpi-card">
                <h3>Total Productos</h3>
                <p class="value">{{ $totalProducts ?? 0 }}</p>
            </div>
            <div class="kpi-card">
                <h3>Ventas Hoy</h3>
                <p class="value">₡{{ number_format($todaySales ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="kpi-card">
                <h3>Proveedores</h3>
                <p class="value">{{ $totalSuppliers ?? 0 }}</p>
            </div>
            <div class="kpi-card">
                <h3>Stock Bajo</h3>
                <p class="value">{{ $lowStockProducts ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Low stock products table (only rendered if data exists) --}}
    @if(isset($lowStockProductsList) && $lowStockProductsList->count() > 0)
    <div class="section">
        <h2>Productos con Stock Bajo</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStockProductsList as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->stock_current }}</td>
                    <td>{{ $product->stock_minimum }}</td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td><span class="status-badge status-warning">Stock Bajo</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Recent sales table (only rendered if data exists) --}}
    @if(isset($recentSales) && $recentSales->count() > 0)
    <div class="section">
        <h2>Ventas Recientes</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentSales as $sale)
                <tr>
                    {{-- Prefer invoice number, fallback to sale ID --}}
                    <td>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</td>
                    {{-- Concatenate customer name safely, fallback to N/A --}}
                    <td>{{ $sale->customer ? trim(($sale->customer->nombre ?? '') . ' ' . ($sale->customer->apellido ?? '')) : 'N/A' }}</td>
                    <td>₡{{ number_format($sale->total, 0, ',', '.') }}</td>
                    <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                    <td>
                        {{-- Dynamic status badge based on sale status --}}
                        <span class="status-badge status-{{ $sale->status === 'completed' ? 'success' : ($sale->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Product distribution by category (only rendered if data exists) --}}
    @if(isset($productsByCategory) && $productsByCategory->count() > 0)
    <div class="section">
        <h2>Distribución por Categorías</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Total Productos</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php $totalProducts = $productsByCategory->sum('total'); @endphp
                @foreach($productsByCategory as $category)
                <tr>
                    <td>{{ $category->categoria }}</td>
                    <td>{{ $category->total }}</td>
                    <td>{{ $totalProducts > 0 ? round(($category->total / $totalProducts) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Report footer --}}
    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión Ciclo Finca 4</p>
        <p>Para más información, consulte el dashboard en línea</p>
    </div>

</body>
</html>