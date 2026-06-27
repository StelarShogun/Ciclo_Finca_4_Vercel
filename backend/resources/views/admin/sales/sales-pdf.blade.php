@extends('admin.exports.layouts.pdf-master')

@section('pdf_body')
    <div class="section">
        <h2>Totales (filtros aplicados)</h2>
        <table class="kpis-grid">
            <tr>
                <td>
                    <p class="kpi-title">Ventas (filas)</p>
                    <p class="kpi-value">{{ $totals['count'] ?? 0 }}</p>
                </td>
                <td>
                    <p class="kpi-title">Suma totales</p>
                    <p class="kpi-value">₡{{ number_format((float) ($totals['sum_total'] ?? 0), 0, ',', '.') }}</p>
                </td>
                <td>
                    <p class="kpi-title">Suma subtotales</p>
                    <p class="kpi-value">₡{{ number_format((float) ($totals['sum_subtotal'] ?? 0), 0, ',', '.') }}</p>
                </td>
                <td>
                    <p class="kpi-title">Suma IVA / desc.</p>
                    <p class="kpi-value">₡{{ number_format((float) ($totals['sum_iva'] ?? 0), 0, ',', '.') }} / ₡{{ number_format((float) ($totals['sum_discount'] ?? 0), 0, ',', '.') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Detalle</h2>
        @if($sales->count() === 0)
            <div class="empty-state">No hay ventas que coincidan con los filtros seleccionados.</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Factura / ID</th>
                        <th>Cliente</th>
                        <th>Fecha de venta</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                        <tr>
                            <td>{{ $sale->invoice_number ?? '#'.$sale->sale_id }}</td>
                            <td>
                                @if($sale->client)
                                    {{ trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? '')) }}
                                @else
                                    {{ $sale->buyer_name ?: 'Mostrador / Sin datos' }}
                                @endif
                            </td>
                            <td>{{ $sale->adminSaleDateLabel() }}</td>
                            <td>{{ ucfirst($sale->status) }}</td>
                            <td>{{ ucfirst($sale->payment_method) }}</td>
                            <td class="num">₡{{ number_format((float) $sale->total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
