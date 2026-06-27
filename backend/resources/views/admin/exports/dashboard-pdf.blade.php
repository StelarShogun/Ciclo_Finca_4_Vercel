@extends('admin.exports.layouts.pdf-master')

@section('pdf_body')
    <div class="section">
        <h2>Resumen ejecutivo</h2>
        <table class="kpis-grid">
            <tr>
                <td>
                    <p class="kpi-title">Total productos</p>
                    <p class="kpi-value">{{ $totalProducts ?? 0 }}</p>
                </td>
                <td>
                    <p class="kpi-title">Ventas hoy</p>
                    <p class="kpi-value">₡{{ number_format((float) ($todaySales ?? 0), 0, ',', '.') }}</p>
                </td>
                <td>
                    <p class="kpi-title">Proveedores</p>
                    <p class="kpi-value">{{ $totalSuppliers ?? 0 }}</p>
                </td>
                <td>
                    <p class="kpi-title">Stock bajo (alertas)</p>
                    <p class="kpi-value">{{ $lowStockProducts ?? 0 }}</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p class="kpi-title">Categorías</p>
                    <p class="kpi-value">{{ $totalCategories ?? 0 }}</p>
                </td>
                <td>
                    <p class="kpi-title">Ventas del mes</p>
                    <p class="kpi-value">₡{{ number_format((float) ($monthlySales ?? 0), 0, ',', '.') }}</p>
                </td>
                <td>
                    <p class="kpi-title">Tendencia ventas hoy vs ayer</p>
                    <p class="kpi-value">{{ ($salesTrend ?? 0) >= 0 ? '+' : '' }}{{ $salesTrend ?? 0 }}%</p>
                </td>
                <td>
                    <p class="kpi-title">Tendencia mes vs anterior</p>
                    <p class="kpi-value">{{ ($monthlyTrend ?? 0) >= 0 ? '+' : '' }}{{ $monthlyTrend ?? 0 }}%</p>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($salesChartSeries) && count($salesChartSeries) > 0)
        @php
            $maxTotal = max(array_map(fn ($r) => (float) ($r['total'] ?? 0), $salesChartSeries)) ?: 1;
        @endphp
        <div class="section">
            <h2>Ventas por día (periodo: {{ $chartPeriodLabel ?? '' }})</h2>
            <p class="hint">Barras proporcionales al total diario (₡).</p>
            <div class="bar-chart">
                @foreach($salesChartSeries as $row)
                    @php
                        $pct = $maxTotal > 0 ? min(100, round(((float) ($row['total'] ?? 0) / $maxTotal) * 100, 1)) : 0;
                    @endphp
                    <div class="bar-row">
                        <div class="bar-label">{{ \Carbon\Carbon::parse($row['date'])->format('d/m') }} — ₡{{ number_format((float) ($row['total'] ?? 0), 0, ',', '.') }}</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($lowStockProductsList) && $lowStockProductsList->count() > 0)
        <div class="section">
            <h2>Productos con stock bajo (muestra)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="num">Stock</th>
                        <th class="num">Mínimo</th>
                        <th>Categoría</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockProductsList as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td class="num">{{ $product->stock_current }}</td>
                            <td class="num">{{ $product->stock_minimum }}</td>
                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(isset($recentSales) && $recentSales->count() > 0)
        <div class="section">
            <h2>Ventas recientes</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Factura / ID</th>
                        <th>Cliente</th>
                        <th class="num">Total</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $sale)
                        <tr>
                            <td>{{ $sale->invoice_number ?? '#'.$sale->sale_id }}</td>
                            <td>
                                @if($sale->client)
                                    {{ trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? '')) }}
                                @else
                                    {{ $sale->buyer_name ?: 'Mostrador / Sin datos' }}
                                @endif
                            </td>
                            <td class="num">₡{{ number_format((float) $sale->total, 0, ',', '.') }}</td>
                            <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                            <td>{{ ucfirst($sale->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(isset($productsByCategory) && $productsByCategory->count() > 0)
        @php $sumCat = $productsByCategory->sum(fn ($c) => (int) data_get($c, 'total', 0)); @endphp
        <div class="section">
            <h2>Distribución por categoría (productos activos)</h2>
            @if($sumCat > 0)
                @php $maxCat = $productsByCategory->max(fn ($c) => data_get($c, 'total', 0)); $maxCat = $maxCat > 0 ? $maxCat : 1; @endphp
                <div class="bar-chart">
                    @foreach($productsByCategory as $category)
                        @php
                            $name = data_get($category, 'categoria');
                            $tot = (int) data_get($category, 'total');
                            $pctBar = $maxCat > 0 ? min(100, round(($tot / $maxCat) * 100, 1)) : 0;
                            $pctShare = $sumCat > 0 ? round(($tot / $sumCat) * 100, 1) : 0;
                        @endphp
                        <div class="bar-row">
                            <div class="bar-label">{{ $name }} — {{ $tot }} ({{ $pctShare }}%)</div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ $pctBar }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <table class="table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="num">Productos activos</th>
                        <th class="num">% del total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productsByCategory as $category)
                        @php
                            $name = data_get($category, 'categoria');
                            $tot = (int) data_get($category, 'total');
                        @endphp
                        <tr>
                            <td>{{ $name }}</td>
                            <td class="num">{{ $tot }}</td>
                            <td class="num">{{ $sumCat > 0 ? round(($tot / $sumCat) * 100, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @php
        $lowN = isset($lowStockProductsList) ? $lowStockProductsList->count() : 0;
        $recentN = isset($recentSales) ? $recentSales->count() : 0;
        $catN = isset($productsByCategory) ? $productsByCategory->count() : 0;
        $chartN = ! empty($salesChartSeries) ? count($salesChartSeries) : 0;
        $hasAny = $lowN > 0 || $recentN > 0 || $catN > 0 || $chartN > 0;
    @endphp
    @if(! $hasAny && ($totalProducts ?? 0) === 0)
        <div class="empty-state">No hay datos suficientes para mostrar secciones detalladas del reporte.</div>
    @endif
@endsection
