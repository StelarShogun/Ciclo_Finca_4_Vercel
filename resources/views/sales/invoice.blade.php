@extends('sales')

@section('Titulo pagina', 'Ventas - Ciclo Finca 4 Admin')

@push('styles')
        @vite(['resources/css/sales/sales.css'])
@endpush

{{-- Invoice is a standalone view (no sidebar or sales JS) --}}
@section('aside')@endsection

@section('contenido')
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <img src="{{ asset('assets/images/logo.png') }}"
                                     style="width:100%; max-width:300px;" alt="Logo">
                            </td>
                            <td>
                                Factura: {{ $sale->invoice_number ?? '#' . $sale->sale_id }}<br>
                                Fecha: {{ $sale->sale_date->format('d/m/Y') }}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                Ciclo Finca 4<br>
                                Sarapiquí, Costa Rica<br>
                                info@cicloperez.com
                            </td>
                            <td>
                                {{ $sale->client
                                    ? trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?: ''))
                                    : ($sale->buyer_name ?: 'Mostrador / Sin datos') }}<br>
                                {{ $sale->client ? ($sale->client->gmail ?: '') : ($sale->buyer_email ?: '') }}<br>
                                N/A
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Método de Pago</td>
                <td>{{ ucfirst($sale->payment_method) }}</td>
            </tr>
            <tr class="details">
                <td>{{ ucfirst($sale->payment_method) }}</td>
                <td>{{ number_format($sale->total, 2) }}</td>
            </tr>

            <tr class="heading">
                <td>Producto</td>
                <td>Precio</td>
            </tr>
            @foreach($sale->saleItems as $item)
            <tr class="item">
                <td>{{ $item->product->name ?? 'N/A' }} (x{{ $item->quantity }})</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach

            <tr class="total"><td></td><td>Subtotal: {{ number_format($sale->subtotal, 2) }}</td></tr>
            <tr class="total"><td></td><td>IVA: {{ number_format($sale->iva, 2) }}</td></tr>
            <tr class="total"><td></td><td>Descuento: {{ number_format($sale->discount, 2) }}</td></tr>
            <tr class="total"><td></td><td>Total: {{ number_format($sale->total, 2) }}</td></tr>
        </table>
    </div>
@endsection