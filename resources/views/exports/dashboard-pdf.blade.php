<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Dashboard - {{ now()->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2e7d32;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2e7d32;
            margin: 0;
            font-size: 2.5rem;
        }
        
        .header p {
            margin: 10px 0 0 0;
            color: #666;
            font-size: 1.1rem;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section h2 {
            color: #2e7d32;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .kpis-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .kpi-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .kpi-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2e7d32;
            margin: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .table th {
            background-color: #2e7d32;
            color: white;
            font-weight: bold;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        
        @media print {
            body { margin: 0; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte del Dashboard</h1>
        <p>Sistema de Gestión Ciclo Finca 4</p>
        <p>Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}</p>
    </div>

    <!-- KPIs Principales -->
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

    <!-- Productos con Stock Bajo -->
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
                    <td>{{ $product->nombre }}</td>
                    <td>{{ $product->stock_actual }}</td>
                    <td>{{ $product->stock_minimo }}</td>
                    <td>{{ $product->categoria->nombre ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge status-warning">Stock Bajo</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Ventas Recientes -->
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
                    <td>{{ $sale->numero_factura ?? '#' . $sale->venta_id }}</td>
                    <td>{{ $sale->cliente->nombre ?? 'N/A' }} {{ $sale->cliente->apellido ?? '' }}</td>
                    <td>₡{{ number_format($sale->total, 0, ',', '.') }}</td>
                    <td>{{ $sale->fecha_venta->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="status-badge status-{{ $sale->estado === 'completada' ? 'success' : ($sale->estado === 'pendiente' ? 'warning' : 'danger') }}">
                            {{ ucfirst($sale->estado) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Productos por Categoría -->
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
                @php
                    $totalProducts = $productsByCategory->sum('total');
                @endphp
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

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión Ciclo Finca 4</p>
        <p>Para más información, consulte el dashboard en línea</p>
    </div>
</body>
</html>
