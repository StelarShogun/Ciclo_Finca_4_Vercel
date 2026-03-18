@extends('layouts.app')

@section('title', 'Compras - Ciclo Finca 4 Admin')

@push('styles')
    <style>
        .purchases-container { padding: 28px 18px; max-width: 1200px; margin: 0 auto; }
        .purchases-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .purchases-header h1 { font-size: 1.6rem; margin: 0; }
        .purchases-subtitle { color: var(--color-muted); margin-top: 6px; }
        .purchases-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
        .purchases-table th, .purchases-table td { padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,0.06); text-align: left; }
        .purchases-table th { background: rgba(46,125,50,0.06); font-weight: 600; }
        .purchases-total { font-weight: 700; }
        .purchases-empty { padding: 40px; color: var(--color-muted); text-align: center; }
        .text-muted { color: var(--color-muted); }
        .action-link { cursor: pointer; background: transparent; border: 1px solid rgba(0,0,0,0.08); padding: 8px 10px; border-radius: 8px; }
        .action-link:hover { border-color: rgba(0,0,0,0.18); }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; width: min(800px, 92vw); max-height: 86vh; overflow: auto; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid rgba(0,0,0,0.08); }
        .modal-header h3 { margin: 0; font-size: 1.1rem; }
        .modal-close { cursor: pointer; border: none; background: transparent; font-size: 1.4rem; line-height: 1; }
        .modal-body { padding: 16px 18px; }
        .loading-spinner { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 24px 10px; }
        .detail-products-table { width: 100%; border-collapse: collapse; }
        .detail-products-table th, .detail-products-table td { padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .detail-products-table th { background: rgba(46,125,50,0.06); }
    </style>
@endpush

@section('content')
    <div class="purchases-container">
        <div class="purchases-header">
            <div>
                <h1>Compras (CF4-4)</h1>
                <div class="purchases-subtitle">Pendientes y completadas (actualización automática)</div>
            </div>
        </div>

        <table class="purchases-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Productos</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>
                            @if($sale->client_id && $sale->client)
                                {{ $sale->client->name }} {{ $sale->client->first_surname }} {{ $sale->client->second_surname ? $sale->client->second_surname : '' }}
                                <span class="text-muted">({{ $sale->client->gmail }})</span>
                            @elseif($sale->customer_id && $sale->customer)
                                {{ $sale->customer->nombre ?? 'N/A' }} {{ $sale->customer->apellido ?? '' }}
                            @elseif($sale->buyer_name)
                                {{ $sale->buyer_name }}
                                @if($sale->buyer_email)
                                    <span class="text-muted">({{ $sale->buyer_email }})</span>
                                @endif
                            @else
                                Walk-in / Sin datos
                            @endif
                        </td>
                        <td>{{ $sale->sale_items_count }} líneas</td>
                        <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                        <td class="purchases-total">₡{{ number_format((float)$sale->total, 0, ',', '.') }}</td>
                        <td>
                            <button class="action-link" type="button" onclick="viewPurchaseDetails('{{ $sale->sale_id }}')">
                                Ver detalles
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="purchases-empty">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <p>No hay compras registradas</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 16px;">
            <x-pagination :paginator="$sales" label="de compras" />
        </div>

        <div id="purchase-detail-modal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Detalle de la compra</h3>
                    <button class="modal-close" onclick="closePurchaseDetailsModal()">&times;</button>
                </div>
                <div class="modal-body" id="purchase-detail-body">
                    <!-- Se llena con JS -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let latestSaleId = @json($latestSaleId ?? 0);

        async function heartbeatCheck() {
            // Evita consultas si el usuario oculta la pestaña
            if (document.visibilityState === 'hidden') return;

            try {
                const res = await fetch("{{ route('purchases.heartbeat') }}" + "?since=" + latestSaleId, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data && data.latestSaleId !== undefined) {
                    latestSaleId = data.latestSaleId;
                }

                if (data && data.hasNew) {
                    window.location.reload();
                }
            } catch (e) {
                // Fail silencioso: no bloquea la UI
            }
        }

        setInterval(heartbeatCheck, 20000);

        function closePurchaseDetailsModal() {
            document.getElementById('purchase-detail-modal').classList.remove('active');
        }

        function viewPurchaseDetails(id) {
            const modal = document.getElementById('purchase-detail-modal');
            const body = document.getElementById('purchase-detail-body');
            modal.classList.add('active');
            body.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--color-primary);"></i>
                    <div>Cargando detalles...</div>
                </div>
            `;

            fetch(`/sales/${id}`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success || !data.sale) {
                        body.innerHTML = '<div class="alert alert-danger">No se pudieron cargar los detalles.</div>';
                        return;
                    }

                    const sale = data.sale;
                    const items = sale.sale_items || sale.saleItems || [];
                    const productsHtml = items.map(item => {
                        const prod = item.product || {};
                        return `<tr>
                            <td>${prod.name || 'N/A'}</td>
                            <td style="text-align:center;">${item.quantity ?? 0}</td>
                            <td style="text-align:right;">₡${Number(item.unit_price || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</td>
                            <td style="text-align:right;"><strong>₡${Number(item.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</strong></td>
                        </tr>`;
                    }).join('');

                    const buyerName = sale.buyer && (sale.buyer.name || sale.buyer.email)
                        ? `${sale.buyer.name || 'Walk-in / Sin datos'}${sale.buyer.email ? ' (' + sale.buyer.email + ')' : ''}`
                        : (sale.customer ? `${sale.customer.nombre || ''} ${sale.customer.apellido || ''}` : 'Walk-in / Sin datos');

                    body.innerHTML = `
                        <div>
                            <div style="display:flex; flex-wrap:wrap; gap: 10px; margin-bottom: 14px;">
                                <div><strong>Factura:</strong> ${sale.invoice_number || '#' + sale.sale_id}</div>
                                <div><strong>Fecha:</strong> ${sale.sale_date ? new Date(sale.sale_date).toLocaleString('es-CR') : ''}</div>
                                <div><strong>Cliente:</strong> ${buyerName}</div>
                                <div><strong>Total:</strong> ₡${Number(sale.total || 0).toLocaleString('es-CR', { minimumFractionDigits: 2 })}</div>
                            </div>

                            <h4 style="margin: 0 0 10px 0;">Productos</h4>
                            <table class="detail-products-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style="text-align:center;">Cantidad</th>
                                        <th style="text-align:right;">Precio</th>
                                        <th style="text-align:right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${productsHtml || '<tr><td colspan="4" class="text-muted">Sin productos</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    `;
                })
                .catch(() => {
                    body.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>';
                });
        }
    </script>
@endpush

