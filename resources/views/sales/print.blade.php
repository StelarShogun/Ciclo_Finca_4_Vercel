@extends('sales')

@section('Titulo pagina', 'Ventas - Ciclo Finca 4 Admin')

@push('styles')
        @vite(['resources/css/sales/sales.css'])
@endpush

{{-- Print is a standalone view (no sidebar or sales JS) --}}
@section('aside')@endsection

@section('contenido')
    <div class="print-header">
        <h1>Ciclo Finca 4</h1>
        <p>Venta #{{ $sale->sale_id }}</p>
        <p>Factura: {{ $sale->invoice_number }}</p>
        <p>Fecha: {{ $sale->sale_date->format('d/m/Y H:i') }}</p>
    </div>

    <div class="print-sale-info">
        <div class="print-info-section">
            <h3>Información de la Venta</h3>
            <div class="print-info-item"><span>ID de Venta:</span><strong>#{{ $sale->sale_id }}</strong></div>
            <div class="print-info-item"><span>Estado:</span><strong>{{ ucfirst($sale->status) }}</strong></div>
            <div class="print-info-item">
                <span>Método de Pago:</span>
                <strong>{{ ucfirst($sale->payment_method) }}</strong>
            </div>
            <div class="print-info-item">
                <span>Vendedor:</span>
                <strong>
                    {{ $sale->sellerAdmin
                        ? trim($sale->sellerAdmin->name . ' ' . $sale->sellerAdmin->first_surname . ' ' . ($sale->sellerAdmin->second_surname ?: ''))
                        : 'No asignado' }}
                </strong>
            </div>
        </div>

        <div class="print-info-section">
            <h3>Información del Cliente</h3>
            <div class="print-info-item">
                <span>Nombre:</span>
                <strong>
                    {{ $sale->client
                        ? trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?: ''))
                        : ($sale->buyer_name ?: 'Mostrador / Sin datos') }}
                </strong>
            </div>
            <div class="print-info-item">
                <span>Correo:</span>
                <strong>{{ $sale->client ? ($sale->client->gmail ?: 'N/A') : ($sale->buyer_email ?: 'N/A') }}</strong>
            </div>
            <div class="print-info-item"><span>Teléfono:</span><strong>N/A</strong></div>
        </div>
    </div>

    <h3>Productos</h3>
    <table class="print-products-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Producto no encontrado' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>₡{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>₡{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="print-total-section">
        <div class="print-total-row">
            <span>Subtotal:</span><span>₡{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
        </div>
        <div class="print-total-row">
            <span>Descuento:</span><span>₡{{ number_format($sale->discount, 0, ',', '.') }}</span>
        </div>
        <div class="print-total-row">
            <span>IVA:</span><span>₡{{ number_format($sale->iva, 0, ',', '.') }}</span>
        </div>
        <div class="print-total-row print-total-final">
            <span>Total:</span><span>₡{{ number_format($sale->total, 0, ',', '.') }}</span>
        </div>
    </div>

    @if($sale->notes)
    <div class="print-info-section">
        <h3>Notas</h3>
        <p>{{ $sale->notes }}</p>
    </div>
    @endif

    <div class="print-footer">
        <p>Gracias por su compra en Ciclo Finca 4</p>
        <p>Sarapiquí, Costa Rica</p>
    </div>

    {{-- Signal for sales.js to execute window.print() --}}
    <meta name="auto-print" content="1">
@endsection