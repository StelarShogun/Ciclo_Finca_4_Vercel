@extends('client.layouts.app')

@section('hideFooter')
@endsection

@section('title', 'Mis Facturas - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
    <style>
        .cf4-review-modal-list { text-align: left; margin-top: 0.75rem; }
        .cf4-review-modal-row { border: 1px solid #e7e7e7; border-radius: 8px; padding: 0.65rem 0.75rem; margin-bottom: 0.55rem; }
        .cf4-review-modal-product { font-weight: 600; margin-bottom: 0.35rem; }
        .cf4-review-stars { display: flex; gap: 0.3rem; }
        .cf4-review-star-btn {
            border: 0;
            background: transparent;
            font-size: 1.3rem;
            line-height: 1;
            color: #c8c8c8;
            cursor: pointer;
            padding: 0;
        }
        .cf4-review-star-btn.is-active { color: #f5b301; }
    </style>
@endpush

@section('content')

    <meta name="cf4-invoice-count" content="{{ $invoiceCount }}">
    <meta name="cf4-invoice-heartbeat-url" content="{{ route('clients.invoices.heartbeat') }}">

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Mis Facturas</h1>
            <p>{{ $tab === 'historial' ? 'Pedidos confirmados por la tienda.' : 'Pedidos pendientes de confirmación por parte de la tienda.' }}</p>
        </div>
        <div class="cf4-invoices-tab-selector">
            <div class="cf4-select-wrapper">
                <i class="{{ $tab === 'historial' ? 'fas fa-history' : 'fas fa-file-invoice' }} cf4-select-icon"></i>
                <select id="cf4-invoice-tab" class="cf4-select" onchange="window.location.href = '{{ route('clients.invoices') }}?tab=' + this.value">
                    <option value="facturas" {{ $tab === 'facturas' ? 'selected' : '' }}>Facturas pendientes</option>
                    <option value="historial" {{ $tab === 'historial' ? 'selected' : '' }}>Historial de compras</option>
                </select>
            </div>
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
                                    @if($tab === 'historial')
                                        <span class="cf4-invoice-status-pill confirmed">Confirmada</span>
                                    @else
                                        <span class="cf4-invoice-status-pill pending">Pendiente</span>
                                    @endif
                                </td>
                                <td><strong>&#8353;{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="cf4-invoices-empty">
                                        <div class="cf4-invoices-empty-icon"><i class="fas fa-file-invoice"></i></div>
                                        <p>{{ $tab === 'historial' ? 'No has realizado ninguna compra aún.' : 'No tienes facturas pendientes.' }}</p>
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

(function () {
    const tab = @json($tab);
    const pendingProducts = @json($pendingReviewProducts ?? []);
    if (tab !== 'historial' || !Array.isArray(pendingProducts) || pendingProducts.length === 0 || typeof Swal === 'undefined') {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const postUrl = @json(route('clients.products.review.batch'));
    const selectedRatings = {};

    function renderRows() {
        return pendingProducts.map((product) => {
            const pid = Number(product.product_id);
            const stars = [1, 2, 3, 4, 5].map((value) => {
                return '<button type="button" class="cf4-review-star-btn" data-product-id="' + pid + '" data-star="' + value + '" aria-label="' + value + ' estrellas">★</button>';
            }).join('');

            return '<div class="cf4-review-modal-row">' +
                '<div class="cf4-review-modal-product">' + product.name + '</div>' +
                '<div class="cf4-review-stars">' + stars + '</div>' +
                '</div>';
        }).join('');
    }

    function paintStars(modal, productId, value) {
        modal.querySelectorAll('.cf4-review-star-btn[data-product-id="' + productId + '"]').forEach((btn) => {
            btn.classList.toggle('is-active', Number(btn.dataset.star) <= value);
        });
    }

    Swal.fire({
        title: 'Tu pedido fue confirmado',
        html:
            '<p>Por favor denos una calificación de la satisfacción con el producto.</p>' +
            '<div class="cf4-review-modal-list">' + renderRows() + '</div>' +
            '<p style="margin-top:0.65rem;font-size:0.86rem;color:#666;">Puedes calificar uno o varios productos y luego presionar Guardar mi reseña.</p>',
        icon: 'info',
        confirmButtonText: 'Guardar mi reseña',
        showCancelButton: true,
        cancelButtonText: 'Más tarde',
        focusConfirm: false,
        didOpen: (modal) => {
            modal.querySelectorAll('.cf4-review-star-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const productId = Number(btn.dataset.productId);
                    const star = Number(btn.dataset.star);
                    selectedRatings[productId] = star;
                    paintStars(modal, productId, star);
                });
            });
        },
        preConfirm: async () => {
            const payload = Object.entries(selectedRatings).map(([productId, stars]) => ({
                product_id: Number(productId),
                stars: Number(stars),
            }));

            if (payload.length === 0) {
                Swal.showValidationMessage('Selecciona al menos una calificación antes de guardar.');
                return false;
            }

            try {
                const response = await fetch(postUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ reviews: payload }),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'No se pudo guardar la reseña.');
                }

                return data;
            } catch (error) {
                Swal.showValidationMessage(error.message);
                return false;
            }
        },
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Reseña guardada',
                text: 'Gracias por calificar tus productos.',
                timer: 1800,
                showConfirmButton: false,
            }).then(() => window.location.reload());
        }
    });
})();
</script>
@endpush
