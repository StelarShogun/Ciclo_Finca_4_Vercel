@extends('admin.layouts.sales')

@section('Titulo pagina', 'Detalle Pedido a Proveedor - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css', 'resources/css/admin/orders/supplier-order-detail.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $stateLabels = [
            'draft'     => 'Borrador',
            'pending'   => 'Pendiente',
            'confirmed' => 'Confirmado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ];
        $label        = $stateLabels[$order->state] ?? ucfirst((string) $order->state);
        $po           = $order->po_number ?: ('#'.$order->num_order);
        $supplierName = $order->supplier?->name ?? '—';
    @endphp

    <div class="sales-container cf4-orders-module cf4-supplier-orders-module">
        <header class="sales-header">
            <div>
                <h1>Pedido {{ $po }}</h1>
                <p>Detalle del pedido de compra al proveedor.</p>
            </div>
            <div class="sales-actions">
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>

                @if($order->state === 'draft')
                    <button type="button" class="btn btn-primary"
                            onclick="sendOrder('{{ $order->num_order }}')"
                            title="Enviar pedido (pasar a Pendiente)">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i> Cancelar
                    </button>

                @elseif($order->state === 'pending')
                    <button type="button" class="btn btn-primary"
                            onclick="confirmOrder('{{ $order->num_order }}')"
                            title="Confirmar pedido">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i> Cancelar
                    </button>

                @elseif($order->state === 'confirmed')
                    <button type="button" class="btn btn-primary"
                            onclick="openReceiveModal()"
                            title="Registrar recepción de mercancía">
                        <i class="fas fa-clipboard-check"></i> Registrar recepción
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                @endif
            </div>
        </header>

        <div class="detail-grid">
            {{-- Información general --}}
            <section class="detail-card">
                <h2><i class="fas fa-info-circle"></i> Información</h2>
                <div class="kv">
                    <div class="kv-row"><span>Nº interno</span><strong>#{{ $order->num_order }}</strong></div>
                    <div class="kv-row"><span>Nº pedido (PO)</span><strong>{{ $po }}</strong></div>
                    <div class="kv-row"><span>Proveedor</span><strong>{{ $supplierName }}</strong></div>
                    <div class="kv-row"><span>Creación</span><strong>{{ $order->date?->format('d/m/Y H:i') }}</strong></div>
                    <div class="kv-row"><span>Entrega estimada</span><strong>{{ $order->estimated_delivery_date?->format('d/m/Y') }}</strong></div>
                    @if($order->received_at)
                        <div class="kv-row"><span>Fecha de recepción</span><strong>{{ $order->received_at->format('d/m/Y H:i') }}</strong></div>
                    @endif
                    <div class="kv-row">
                        <span>Estado</span>
                        <strong><span class="order-status-pill {{ $order->state }}">{{ $label }}</span></strong>
                    </div>
                </div>
            </section>

            {{-- Tabla de productos --}}
            <section class="detail-card detail-card-wide">
                <h2><i class="fas fa-box"></i> Productos</h2>
                <div class="items-table-wrap">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Pedido</th>
                                @if($order->state === 'delivered')
                                    <th class="num">Recibido</th>
                                @endif
                                <th class="num">Precio unit.</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->orderItems ?? [] as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="num">{{ (int) $item->quantity }}</td>
                                    @if($order->state === 'delivered')
                                        <td class="num">{{ (int)($item->received_quantity ?? 0) }}</td>
                                    @endif
                                    <td class="num">₡{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                    <td class="num"><strong>₡{{ number_format((float) $item->total, 2, ',', '.') }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty-cell">Sin productos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="detail-summary">
                    <span>Total</span>
                    <strong>₡{{ number_format((float) $order->total, 2, ',', '.') }}</strong>
                </div>
            </section>
        </div>
    </div>

    {{-- ================================================================
         Modal: Registrar recepción de mercancía
         Solo se renderiza cuando el pedido está Confirmado
         ================================================================ --}}
    @if($order->state === 'confirmed')
    <div id="receive-order-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Registrar recepción de mercancía</h3>
                <button type="button" class="modal-close" onclick="closeReceiveModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin:0 0 16px; color:var(--color-text-secondary, #5f6368); font-size:.9rem;">
                    Ingresa la cantidad recibida por cada producto. Al confirmar, el pedido pasará a
                    <strong>Entregado</strong> y el stock se actualizará automáticamente.
                </p>

                <div id="receive-form-error" class="field-error" style="display:none; margin-bottom:12px;" role="alert"></div>

                <div class="items-table-wrap">
                    <table class="items-table" id="receive-items-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num" style="width:110px;">Pedido</th>
                                <th class="num" style="width:140px;">Cantidad recibida</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="num">{{ (int) $item->quantity }}</td>
                                    <td class="num">
                                        <input
                                            type="number"
                                            class="qty-input receive-qty-input"
                                            name="received_quantity"
                                            min="0"
                                            max="{{ (int) $item->quantity }}"
                                            value="{{ (int) $item->quantity }}"
                                            data-item-id="{{ $item->id }}"
                                            data-max="{{ (int) $item->quantity }}"
                                            data-name="{{ $item->name }}"
                                            style="width:100%; text-align:right;"
                                            required
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeReceiveModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="confirm-receive-btn"
                        onclick="submitReception('{{ $order->num_order }}')">
                    <i class="fas fa-check"></i> Confirmar recepción
                </button>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
    @vite(['resources/js/admin/orders/supplier-orders.js'])

    <script>
        function openReceiveModal() {
            const modal = document.getElementById('receive-order-modal');
            if (!modal) return;
            document.getElementById('receive-form-error').style.display = 'none';
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeReceiveModal() {
            const modal = document.getElementById('receive-order-modal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        async function submitReception(orderId) {
            const inputs = document.querySelectorAll('.receive-qty-input');
            const errEl  = document.getElementById('receive-form-error');
            const btn    = document.getElementById('confirm-receive-btn');

            errEl.style.display = 'none';
            errEl.textContent   = '';

            for (const input of inputs) {
                const val  = parseInt(input.value, 10);
                const max  = parseInt(input.dataset.max, 10);
                const name = input.dataset.name;

                if (isNaN(val) || val < 0) {
                    errEl.textContent   = `La cantidad recibida de "${name}" no puede ser negativa.`;
                    errEl.style.display = 'block';
                    input.focus();
                    return;
                }
                if (val > max) {
                    errEl.textContent   = `La cantidad recibida de "${name}" (${val}) supera la pedida (${max}).`;
                    errEl.style.display = 'block';
                    input.focus();
                    return;
                }
            }

            const items = Array.from(inputs).map(input => ({
                order_item_id:     parseInt(input.dataset.itemId, 10),
                received_quantity: parseInt(input.value, 10),
            }));

            btn.disabled = true;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const res  = await fetch(`/supplier-orders/${orderId}/receive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ items }),
                });

                const data = await res.json().catch(() => ({}));

                if (res.status === 422 && data.errors) {
                    const first = Object.values(data.errors).flat()[0] ?? 'Error de validación.';
                    errEl.textContent   = first;
                    errEl.style.display = 'block';
                    return;
                }

                if (!res.ok || !data.success) {
                    errEl.textContent   = data.message ?? 'No se pudo registrar la recepción.';
                    errEl.style.display = 'block';
                    return;
                }

                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon:               'success',
                        title:              'Recepción registrada',
                        text:               data.message,
                        confirmButtonColor: '#2e7d32',
                        confirmButtonText:  'Entendido',
                    });
                }

                window.location.reload();

            } finally {
                btn.disabled = false;
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeReceiveModal();
        });

        document.getElementById('receive-order-modal')?.addEventListener('click', function (e) {
            if (e.target === this) closeReceiveModal();
        });
    </script>
@endpush