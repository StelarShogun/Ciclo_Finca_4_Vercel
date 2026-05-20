<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte semanal — Ciclo Finca 4</title>
    <style>
        /* ── Reset ─────────────────────────────────────────────────────────── */
        body  { margin:0; padding:0; background:#f4f4f7; font-family:'Segoe UI',Arial,sans-serif; color:#333; }
        table { border-collapse:collapse; }
        a     { color:#235347; text-decoration:none; }
        img   { border:0; display:block; }

        /* ── Layout ─────────────────────────────────────────────────────────── */
        .wrapper   { width:100%; background:#f4f4f7; padding:32px 0; }
        .container { max-width:620px; margin:0 auto; background:#fff;
                     border-radius:8px; overflow:hidden;
                     box-shadow:0 2px 8px rgba(0,0,0,.08); }

        /* ── Header ─────────────────────────────────────────────────────────── */
        .header    { background:#235347; padding:28px 32px; }
        .header h1 { margin:0; color:#fff; font-size:22px; font-weight:700; }
        .header p  { margin:6px 0 0; color:#a5d6a7; font-size:13px; }

        /* ── Body ───────────────────────────────────────────────────────────── */
        .body      { padding:28px 32px; }
        .section   { margin-bottom:28px; }
        .section h2{ font-size:14px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.06em; color:#235347; margin:0 0 14px; }

        /* ── KPI grid ───────────────────────────────────────────────────────── */
        .kpi-grid  { display:table; width:100%; border-spacing:0; }
        .kpi-row   { display:table-row; }
        .kpi-cell  { display:table-cell; width:33.33%; padding:0 6px 12px 0;
                     vertical-align:top; }
        .kpi-box   { background:#f1f8e9; border-radius:6px; padding:14px 16px; }
        .kpi-box .value { font-size:22px; font-weight:700; color:#163832; }
        .kpi-box .label { font-size:12px; color:#555; margin-top:4px; }

        .kpi-box.alert  { background:#fff3e0; }
        .kpi-box.alert .value { color:#e65100; }

        /* ── Table ───────────────────────────────────────────────────────────── */
        .data-table { width:100%; border-collapse:collapse; font-size:13px; }
        .data-table th { background:#f1f8e9; color:#235347; font-weight:600;
                         text-align:left; padding:8px 10px; border-bottom:2px solid #c8e6c9; }
        .data-table td { padding:8px 10px; border-bottom:1px solid #eee; vertical-align:top; }
        .data-table tr:last-child td { border-bottom:none; }

        /* ── Sales chart (text sparkline) ────────────────────────────────────── */
        .day-row     { display:inline-block; margin-right:8px; text-align:center;
                       font-size:11px; color:#555; }
        .day-bar     { display:block; width:22px; background:#235347; border-radius:2px;
                       margin:0 auto 3px; }
        .bars-wrap   { display:flex; align-items:flex-end; gap:4px; padding-top:6px; }

        /* ── CTA ─────────────────────────────────────────────────────────────── */
        .cta-wrap  { text-align:center; margin:24px 0 0; }
        .cta-btn   { display:inline-block; background:#235347; color:#fff !important;
                     padding:12px 28px; border-radius:5px; font-size:14px; font-weight:600; }

        /* ── Footer ─────────────────────────────────────────────────────────── */
        .footer    { background:#f9f9f9; border-top:1px solid #e0e0e0;
                     padding:18px 32px; text-align:center; font-size:11px; color:#999; }
    </style>
</head>
<body>
<div class="wrapper">
<div class="container">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>Reporte semanal — Ciclo Finca 4</h1>
        <p>
            Período: {{ $periodStart->format('d/m/Y') }} al {{ $periodEnd->format('d/m/Y') }}
            &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>

    <div class="body">

        {{-- ── KPI destacados ──────────────────────────────────────────────── --}}
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

        {{-- ── Ventas diarias ───────────────────────────────────────────────── --}}
        @if (!empty($kpis['salesByDay']))
        <div class="section">
            <h2>Ventas por día</h2>

            @php
                $maxSale = collect($kpis['salesByDay'])->max('total') ?: 1;
                $barMaxPx = 48; // max bar height in px
            @endphp

            <table class="data-table">
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
                        <td style="text-align:right;">
                            {{ number_format($day['total'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Productos con stock bajo ─────────────────────────────────────── --}}
        @if ($kpis['lowStockCount'] > 0)
        <div class="section">
            <h2>⚠ Productos con stock bajo (top 5)</h2>

            <table class="data-table">
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
                        <td style="text-align:right; color:#e65100; font-weight:600;">
                            {{ $product->stock_current }}
                        </td>
                        <td style="text-align:right;">{{ $product->stock_minimum }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="color:#999;">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Categorías con más productos activos ────────────────────────── --}}
        @if (!empty($kpis['productsByCategory']) && $kpis['productsByCategory']->count() > 0)
        <div class="section">
            <h2>Categorías con mayor volumen</h2>

            <table class="data-table">
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

        {{-- ── Top productos vendidos ───────────────────────────────────────── --}}
        @if (!empty($kpis['topProducts']) && $kpis['topProducts']->count() > 0)
        <div class="section">
            <h2>Productos más vendidos (últimos 7 días)</h2>

            <table class="data-table">
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

        {{-- ── CTA ──────────────────────────────────────────────────────────── --}}
        <div class="cta-wrap">
            <a href="{{ $dashboardUrl }}" class="cta-btn">
                Ver dashboard completo →
            </a>
        </div>

    </div>{{-- /body --}}

    {{-- ── Footer ─────────────────────────────────────────────────────────── --}}
    <div class="footer">
        Este correo fue generado automáticamente por el sistema de administración de
        <strong>Ciclo Finca 4</strong>. No responda a este mensaje.<br>
        Para ajustar la configuración del reporte, ingrese al panel de administración.
    </div>

</div>{{-- /container --}}
</div>{{-- /wrapper --}}
</body>
</html>
