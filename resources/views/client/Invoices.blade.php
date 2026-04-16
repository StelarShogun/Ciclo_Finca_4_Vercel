@extends('client.layouts.app')

@section('hideFooter')
@endsection

@section('title', 'Mis Facturas - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')

    <meta name="cf4-invoice-count" content="{{ $invoiceCount }}">
    <meta name="cf4-invoice-heartbeat-url" content="{{ route('clients.invoices.heartbeat') }}">

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Mis Facturas</h1>
            <p>Pedidos pendientes de confirmación por parte de la tienda.</p>
        </div>
    </div>

    <div class="cf4-invoices-wrapper">

        <div class="cf4-invoices-card">
            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table">
                    <thead>
                        <tr>
                            <th>Pedido / Factura</th>
                            <th>Productos</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $sale)
                            <tr>
                                <td>
                                    <strong>#{{ $sale->sale_id }}</strong>
                                    @if($sale->invoice_number)
                                        <div class="cf4-invoice-number">{{ $sale->invoice_number }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->saleItems && $sale->saleItems->count() > 0)
                                        <div class="cf4-invoice-items">
                                            @foreach($sale->saleItems as $item)
                                                <div>{{ $item->quantity }} &times; {{ $item->product->name ?? 'Producto' }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="cf4-invoice-muted">Sin productos</span>
                                    @endif
                                </td>
                                <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="cf4-invoice-status-pill pending">Pendiente</span>
                                </td>
                                <td><strong>&#8353;{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="cf4-invoices-empty">
                                        <div class="cf4-invoices-empty-icon"><i class="fas fa-file-invoice"></i></div>
                                        <p>No tienes facturas pendientes.</p>
                                        <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-th"></i> Ir al catálogo
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
(function () {
    const metaCount = document.querySelector('meta[name="cf4-invoice-count"]');
    const metaUrl   = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]');
    if (!metaCount || !metaUrl) return;

    let lastCount = parseInt(metaCount.getAttribute('content'), 10);
    const url = metaUrl.getAttribute('content');

    setInterval(async function () {
        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.count !== lastCount) {
                location.reload();
            }
        } catch (_) {}
    }, 15000);
})();
</script>
@endpush
