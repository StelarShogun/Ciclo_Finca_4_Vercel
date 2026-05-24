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
    <meta name="cf4-unseen-history-count" content="{{ $unseenHistoryCount }}">
    <meta name="cf4-invoice-revision" content="{{ $invoicesRevision }}">
    <meta name="cf4-invoice-heartbeat-url" content="{{ route('clients.invoices.heartbeat') }}">

    @php
        use App\Support\ClientPickupPolicy;
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

        $activeTabLabel = match ($tab) {
            'historial' => 'Historial de compras',
            'canceladas' => 'Canceladas',
            default => 'Pendientes / Por recoger',
        };
    @endphp

    <div class="cf4-invoices-header">
        <div class="cf4-invoices-header-inner">
            <h1><i class="fas fa-file-invoice"></i> Mis Facturas</h1>
            <p>{{ $headerDescription }}</p>
            <nav class="cf4-invoices-escape-nav" aria-label="Seguir en la tienda">
                <a href="{{ route('clients.catalog') }}" class="cf4-invoices-escape-link cf4-invoices-escape-link--primary">
                    <i class="fas fa-store" aria-hidden="true"></i> Seguir comprando
                </a>
                <a href="{{ route('clients.home') }}" class="cf4-invoices-escape-link">
                    <i class="fas fa-home" aria-hidden="true"></i> Ir al inicio
                </a>
            </nav>
        </div>
        <div class="cf4-invoices-tab-selector">
            <div class="cf4-invoices-tab-dropdown" data-cf4-invoices-tab-dropdown>
                <button type="button"
                        class="cf4-invoices-tab-trigger"
                        id="cf4-invoice-tab-trigger"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                        aria-controls="cf4-invoice-tab-menu">
                    <i class="{{ $selectIcon }} cf4-invoices-tab-trigger__icon" aria-hidden="true"></i>
                    <span class="cf4-invoices-tab-trigger__label">{{ $activeTabLabel }}</span>
                    <i class="fas fa-chevron-down cf4-invoices-tab-trigger__chevron" aria-hidden="true"></i>
                    @if($unseenHistoryCount > 0 && $tab !== 'historial')
                        <span class="cf4-invoices-tab-trigger__badge" id="history-tab-badge" title="Compras nuevas en Historial"></span>
                    @endif
                </button>
                <ul class="cf4-invoices-tab-menu" id="cf4-invoice-tab-menu" role="listbox" hidden>
                    <li role="presentation">
                        <a href="{{ route('clients.invoices', ['tab' => 'facturas']) }}"
                           role="option"
                           @class(['cf4-invoices-tab-option', 'is-active' => $tab === 'facturas'])>
                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                            Pendientes / Por recoger
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="{{ route('clients.invoices', ['tab' => 'canceladas']) }}"
                           role="option"
                           @class(['cf4-invoices-tab-option', 'is-active' => $tab === 'canceladas'])>
                            <i class="fas fa-ban" aria-hidden="true"></i>
                            Canceladas
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="{{ route('clients.invoices', ['tab' => 'historial']) }}"
                           role="option"
                           @class(['cf4-invoices-tab-option', 'is-active' => $tab === 'historial'])>
                            <i class="fas fa-history" aria-hidden="true"></i>
                            Historial de compras
                            @if($unseenHistoryCount > 0 && $tab !== 'historial')
                                <span class="cf4-invoices-tab-option__badge" title="Compras nuevas"></span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="cf4-invoices-wrapper">

        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('clients.home') }}">Inicio</a>
            <span>/</span>
            <span>Mis Facturas</span>
        </nav>

        @if(($readyToPickupCount ?? 0) > 0)
            <div class="cf4-ready-pickup-banner" role="status" aria-live="polite">
                <div class="cf4-ready-pickup-banner__icon" aria-hidden="true">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="cf4-ready-pickup-banner__body">
                    <strong>Tu pedido ya está listo para retirar</strong>
                    <p>
                        {{ $readyToPickupCount === 1 ? 'Tienes 1 pedido' : 'Tienes '.$readyToPickupCount.' pedidos' }}
                        listo{{ $readyToPickupCount === 1 ? '' : 's' }} en tienda.
                        {{ ClientPickupPolicy::summaryLine() }}
                    </p>
                </div>
            </div>
        @endif

        <div class="cf4-invoices-card">
            @if($orders->isEmpty())
                @php
                    $emptyMessage = match ($tab) {
                        'historial' => 'No has realizado ninguna compra aún.',
                        'canceladas' => 'No tienes facturas canceladas.',
                        default => 'No tienes facturas pendientes o por recoger.',
                    };
                @endphp
                <div class="cf4-invoices-empty cf4-invoices-empty--panel">
                    <div class="cf4-invoices-empty-icon"><i class="fas fa-file-invoice"></i></div>
                    <p>{{ $emptyMessage }}</p>
                    <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-bicycle"></i> Ir al catálogo
                    </a>
                </div>
            @else
                <div class="sales-table-container cf4-invoices-table-scroll">
                    <table class="sales-table cf4-invoices-list-table admin-table">
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
                        @foreach($orders as $sale)
                            @php
                                $statusLabel = match ($sale->status) {
                                    'pending' => 'Pendiente',
                                    'ready_to_pickup' => 'Por recoger',
                                    'cancelled', 'canceled' => 'Cancelada',
                                    'completed' => 'Confirmado',
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
                                <td data-label="Factura">
                                    @if($sale->invoice_number)
                                        <strong>{{ $sale->invoice_number }}</strong>
                                    @else
                                        <span class="cf4-invoice-muted">Sin número asignado</span>
                                    @endif
                                </td>
                                <td data-label="Fecha">{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                                <td data-label="Estado">
                                    <span class="cf4-invoice-status-badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td data-label="{{ $tab === 'historial' ? 'Total pagado' : 'Total' }}"><strong>&#8353;{{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                                <td class="cf4-invoices-td-actions" data-label="Acciones">
                                    <a href="{{ route('clients.invoices.show', $sale) }}" class="btn btn-primary btn-sm cf4-invoice-detail-btn" data-cf4-confirm-invoice aria-label="Ver pedido{{ $sale->invoice_number ? ' '.$sale->invoice_number : '' }}">
                                        Ver pedido
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="cf4-invoices-pagination-wrap">
                    <x-pagination :paginator="$orders" label="facturas" />
                </div>
            @endif
            @endif
        </div>

    </div>

@endsection

@push('scripts')
    @vite(['resources/js/client/invoices-page.js'])
    @if ($tab === 'historial' && $pendingReviewProducts->isNotEmpty())
        <script>
            window.__cf4InvoiceReview = {
                tab: @json($tab),
                pendingProducts: @json($pendingReviewProducts),
                postUrl: @json(route('clients.products.review.batch')),
            };
        </script>
        @vite(['resources/js/client/invoices-review-modal.js'])
    @endif
@endpush