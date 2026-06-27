@extends('emails.layouts.base')

@section('title', 'Reporte semanal — Ciclo Finca 4')

@section('preheader', 'Resumen semanal del dashboard de administración.')

@section('header-title', 'Reporte semanal')

@section('header-subtitle')
    Período: {{ $periodStart->format('d/m/Y') }} al {{ $periodEnd->format('d/m/Y') }}
    · Generado el {{ now()->format('d/m/Y H:i') }}
@endsection

@section('styles')
    .section { margin-bottom: 28px; }
    .section h2 {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #235347;
        margin: 0 0 14px;
    }
    .kpi-grid { display: table; width: 100%; border-spacing: 0; }
    .kpi-row { display: table-row; }
    .kpi-cell {
        display: table-cell;
        width: 33.33%;
        padding: 0 6px 12px 0;
        vertical-align: top;
    }
    .kpi-box {
        background: #f4faf5;
        border-radius: 6px;
        padding: 14px 16px;
        border: 1px solid #c8e6c9;
    }
    .kpi-box .value { font-size: 22px; font-weight: 700; color: #163832; }
    .kpi-box .label { font-size: 12px; color: #555; margin-top: 4px; }
    .kpi-box.alert { background: #fff3e0; border-color: #ffe0b2; }
    .kpi-box.alert .value { color: #e65100; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table th {
        background: #f4faf5;
        color: #235347;
        font-weight: 600;
        text-align: left;
        padding: 8px 10px;
        border-bottom: 2px solid #c8e6c9;
    }
    .data-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #eeeeee;
        vertical-align: top;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .cta-wrap { text-align: center; margin: 24px 0 0; }
    @media only screen and (max-width: 620px) {
        .kpi-cell {
            display: block;
            width: 100% !important;
            padding: 0 0 10px 0 !important;
        }
    }
@endsection

@section('content')
    <div class="section">
        <h2>Indicadores del período</h2>

        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-cell">
                    <div class="kpi-box">
                        <div class="value">₡{{ number_format($kpis['periodSales'], 0, ',', '.') }}</div>
                        <div class="label">Ventas totales (7 días)</div>
                    </div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-box">
                        <div class="value">{{ $kpis['periodSalesCount'] }}</div>
                        <div class="label">Pedidos completados</div>
                    </div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-box {{ $kpis['lowStockCount'] > 0 ? 'alert' : '' }}">
                        <div class="value">{{ $kpis['lowStockCount'] }}</div>
                        <div class="label">Productos con stock bajo</div>
                    </div>
                </div>
            </div>

            <div class="kpi-row">
                <div class="kpi-cell">
                    <div class="kpi-box">
                        <div class="value">{{ $kpis['totalProducts'] }}</div>
                        <div class="label">Productos en catálogo</div>
                    </div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-box">
                        <div class="value">{{ $kpis['totalCategories'] }}</div>
                        <div class="label">Categorías activas</div>
                    </div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-box">
                        <div class="value">{{ $kpis['totalSuppliers'] }}</div>
                        <div class="label">Proveedores</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($kpis['salesByDay']))
    <div class="section">
        <h2>Ventas por día</h2>
        <table class="data-table" role="presentation">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th style="text-align:right;">Total (₡)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kpis['salesByDay'] as $day)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($day['date'])->locale('es')->translatedFormat('D d/m') }}</td>
                    <td style="text-align:right;">{{ number_format($day['total'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($kpis['lowStockCount'] > 0)
    <div class="section">
        <h2>Productos con stock bajo (top 5)</h2>
        <table class="data-table" role="presentation">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th style="text-align:right;">Stock actual</th>
                    <th style="text-align:right;">Stock mínimo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kpis['lowStockList'] as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? '—' }}</td>
                    <td style="text-align:right; color:#e65100; font-weight:600;">{{ $product->stock_current }}</td>
                    <td style="text-align:right;">{{ $product->stock_minimum }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:#999;">Sin datos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if (!empty($kpis['productsByCategory']) && $kpis['productsByCategory']->count() > 0)
    <div class="section">
        <h2>Categorías con mayor volumen</h2>
        <table class="data-table" role="presentation">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th style="text-align:right;">Productos activos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kpis['productsByCategory']->take(5) as $cat)
                <tr>
                    <td>{{ $cat['categoria'] }}</td>
                    <td style="text-align:right;">{{ $cat['total'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if (!empty($kpis['topProducts']) && $kpis['topProducts']->count() > 0)
    <div class="section">
        <h2>Productos más vendidos (últimos 7 días)</h2>
        <table class="data-table" role="presentation">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:right;">Uds. vendidas</th>
                    <th style="text-align:right;">Ingresos (₡)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kpis['topProducts'] as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td style="text-align:right;">{{ $p->total_vendido }}</td>
                    <td style="text-align:right;">{{ number_format($p->ingresos, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="cta-wrap">
        @include('emails.partials.button', [
            'href' => $dashboardUrl,
            'label' => 'Ver dashboard completo →',
        ])
    </div>
@endsection

@section('footer-note')
    Este correo fue generado automáticamente por el sistema de administración.
    Para ajustar la configuración del reporte, ingrese al panel de administración.
@endsection
