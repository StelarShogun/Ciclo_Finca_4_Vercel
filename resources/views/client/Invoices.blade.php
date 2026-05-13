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

        .cf4-invoices-card .sales-table.cf4-invoices-list-table thead th,
        .cf4-invoices-card .sales-table.cf4-invoices-list-table tbody td {
            text-align: left;
            vertical-align: top;
        }

        .cf4-invoices-card .sales-table.cf4-invoices-list-table thead th.cf4-invoices-th-actions,
        .cf4-invoices-card .sales-table.cf4-invoices-list-table tbody td.cf4-invoices-td-actions {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .cf4-invoice-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .cf4-invoice-status-pending {
            background: #fff4d6;
            color: #8a5a00;
        }

        .cf4-invoice-status-ready {
            background: #dff7ea;
            color: #176b3a;
        }

        .cf4-invoice-status-cancelled {
            background: #fde2e2;
            color: #9f1d1d;
        }

        .cf4-invoice-status-completed {
            background: #e7f0ff;
            color: #1f4f9a;
        }

        .cf4-invoice-status-default {
            background: #eeeeee;
            color: #555555;
        }
    </style>
@endpush

@section('content')

    <meta name="cf4-invoice-count" content="{{ $invoiceCount }}">
    <meta name="cf4-invoice-heartbeat-url" content="{{ route('clients.invoices.heartbeat') }}">

    @php
        $headerDescription = match ($tab) {
            'historial' => 'Compras completadas por la tienda.',
            'canceladas' => 'Pedidos cancelados.',
            default => 'Pedidos pendientes o listos para recoger.',
        };

        $selectIcon = match ($tab) {
            'historial' => 'fas fa-history',
            'canceladas' => 'fas fa-ban',
            default => 'fas fa-file-invoice',
        };
    @endphp

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Mis Facturas</h1>
            <p>{{ $headerDescription }}</p>
        </div>
        <div class="cf4-invoices-tab-selector">
            <div class="cf4-select-wrapper">
                <i class="{{ $selectIcon }} cf4-select-icon"></i>
                <select id="cf4-invoice-tab" class="cf4-select" onchange="window.location.href = '{{ route('clients.invoices') }}?tab=' + this.value">
                    <option value="facturas" {{ $tab === 'facturas' ? 'selected' : '' }}>Pendientes / Por recoger</option>
                    <option value="canceladas" {{ $tab === 'canceladas' ? 'selected' : '' }}>Canceladas</option>
                    <option value="historial" {{ $tab === 'historial' ? 'selected' : '' }}>Historial de compras</option>
                </select>
            </div>
        </div>
    </div>

    <div class="cf4-invoices-wrapper">

        <div class="cf4-invoices-card">
            <div class="sales-table-container">
                <table class="sales-table cf4-purchases-table cf4-invoices-list-table">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>{{ $tab === 'historial' ? 'Total pagado' : 'Total' }}</th>
                            <th class="cf4-invoices-th-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $sale)
                            @php
                                $statusLabel = match ($sale->status) {
                                    'pending' => 'Pendiente',
                                    'ready_to_pickup' => 'Por recoger',
                                    'cancelled', 'canceled' => 'Cancelada',
                                    'completed' => 'Completada',
                                    default => ucfirst(str_replace('_', ' ', (string) $sale->status)),
                                };

                                $statusClass = match ($sale->status) {
                                    'pending' => 'cf4-invoice-status-pending',
                                    'ready_to_pickup' => 'cf4-invoice-status-ready',
                                    'cancelled', 'canceled' => 'cf4-invoice-status-cancelled',
                                    'completed' => 'cf4-invoice-status-completed',
                                    default => 'cf4-invoice-status-default',
                                };
                            @endphp

                            <tr>
                                <td>
                                    @if($sale->invoice_number)
                                        <strong>{{ $sale->invoice_number }}</strong>
                                    @else
                                        <span class="cf4-invoice-muted">Sin número asignado</span>
                                    @endif
                                </td>
                                <td>{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                                <td>
                                    <span class="cf4-invoice-status-badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td><strong>&#8353;{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                                <td class="cf4-invoices-td-actions">
                                    <a href="{{ route('clients.invoices.show', $sale) }}" class="btn btn-primary btn-sm" aria-label="Ver detalle{{ $sale->invoice_number ? ' de '.$sale->invoice_number : '' }}">
                                        <i class="fas fa-eye"></i> Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="cf4-invoices-empty">
                                        <div class="cf4-invoices-empty-icon"><i class="fas fa-file-invoice"></i></div>
                                        <p>
                                            @if($tab === 'historial')
                                                No has realizado ninguna compra aún.
                                            @elseif($tab === 'canceladas')
                                                No tienes facturas canceladas.
                                            @else
                                                No tienes facturas pendientes o por recoger.
                                            @endif
                                        </p>
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
    const metaUrl = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]');
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
            '<p style="margin-top:0.65rem;font-size:0.86rem;color:#666;">Este mensaje seguirá apareciendo mientras tengas productos sin reseñar.</p>',
        icon: 'info',
        confirmButtonText: 'Guardar mi reseña',
        showCancelButton: false,
        showCloseButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
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