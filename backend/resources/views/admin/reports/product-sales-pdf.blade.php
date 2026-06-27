@extends('admin.exports.layouts.pdf-master')

@section('pdf_body')
    <div class="section">
        <h2>Top 10 ({{ $top10Metric === 'units' ? 'unidades vendidas' : 'ingresos (₡)' }})</h2>
        @if($top10->count() === 0)
            <div class="empty-state">No hay ventas completadas en este periodo para los criterios seleccionados.</div>
        @else
            <div class="bar-chart">
                @foreach($top10 as $i => $row)
                    @php
                        $pct = $top10Metric === 'units'
                            ? min(100, round(($row['units_sold'] / $maxBarUnits) * 100, 1))
                            : min(100, round(($row['revenue'] / $maxBarRevenue) * 100, 1));
                    @endphp
                    <div class="bar-row">
                        <div class="bar-label">
                            {{ $i + 1 }}. {{ $row['name'] }} —
                            @if($top10Metric === 'units')
                                {{ $row['units_sold'] }} u.
                            @else
                                ₡{{ number_format($row['revenue'], 0, ',', '.') }}
                            @endif
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th class="num">Unidades</th>
                        <th class="num">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top10 as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['sku'] }}</td>
                            <td class="num">{{ $row['units_sold'] }}</td>
                            <td class="num">₡{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2>Productos con ventas (tabla)</h2>
        @if($tableRows->count() === 0)
            <div class="empty-state">Sin filas para mostrar.</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th class="num">Unidades</th>
                        <th class="num">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['sku'] }}</td>
                            <td class="num">{{ $row['units_sold'] }}</td>
                            <td class="num">₡{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
