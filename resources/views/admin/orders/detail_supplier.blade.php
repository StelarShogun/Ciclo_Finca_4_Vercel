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
            'draft'            => 'Borrador',
            'pending'          => 'Pendiente',
            'confirmed'        => 'Confirmado',
            'partial_received' => 'Recepción parcial',
            'delivered'        => 'Entregado',
            'cancelled'        => 'Cancelado',
        ];
        $label        = $stateLabels[$order->state] ?? ucfirst((string) $order->state);
        $po           = $order->po_number ?: ('#' . $order->num_order);
        $supplierName = $order->supplier?->name ?? '—';

        $confirmTimelineEntry = $order->stateTimeline->firstWhere('state', 'confirmed');
        $confirmAuditAt = $confirmTimelineEntry?->changed_at;
        $confirmAuditUserLabel = null;
        if ($confirmTimelineEntry?->admin) {
            $a = $confirmTimelineEntry->admin;
            $confirmAuditUserLabel = trim(implode(' ', array_filter([
                $a->name,
                $a->first_surname,
                $a->second_surname,
            ])));
            if ($confirmAuditUserLabel === '') {
                $confirmAuditUserLabel = $a->gmail ?: null;
            }
        }
        $showSupplierConfirmAudit = $confirmTimelineEntry !== null;

        $hasReceivedData = $order->orderItems->contains(fn ($it) => $it->received_quantity !== null);

        $initialTotal = (float) ($order->orderItems->reduce(
            fn ($carry, $it) => $carry + (float) ($it->total ?? 0),
            0.0
        ) ?: (float) ($order->total ?? 0));

        $receivedTotal = $hasReceivedData
            ? (float) $order->orderItems->reduce(function ($carry, $it) {
                $unit = (float) ($it->unit_price ?? 0);
                $qty  = (int) ($it->received_quantity ?? 0);
                return $carry + round($unit * $qty, 2);
            }, 0.0)
            : null;

        $hasShorts = $hasReceivedData && $order->orderItems->contains(
            fn ($it) => (int) ($it->received_quantity ?? 0) < (int) ($it->quantity ?? 0)
        );

        $shortsTotal = ($hasReceivedData && $receivedTotal !== null)
            ? max($initialTotal - $receivedTotal, 0.0)
            : 0.0;

        // Columnas dinámicas: mostrar "Recibido" solo cuando existan datos de recepción por línea.
        $showReceivedCol = $hasReceivedData;
        // Número total de columnas de la tabla de productos.
        $productColCount = $showReceivedCol ? 5 : 4;
    @endphp

    <div class="sales-container cf4-orders-module cf4-supplier-orders-module"
         data-supplier-order-num="{{ $order->num_order }}"
         data-supplier-order-state="{{ $order->state }}">
        <header class="sales-header">
            <div>
                <h1>Pedido {{ $po }}</h1>
                <p>Detalle del pedido de compra al proveedor.</p>
            </div>
            <div class="sales-actions" data-supplier-order-actions="{{ $order->num_order }}">
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>

                @if($order->state === 'draft')
                    {{-- draft va directo a confirmed; no existe paso intermedio "pending" para pedidos nuevos. --}}
                    <button type="button" class="btn btn-primary"
                            onclick="confirmOrder('{{ $order->num_order }}')"
                            title="Confirmar pedido">
                        <i class="fas fa-check"></i>
                        Confirmar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="cancelOrder('{{ $order->num_order }}')"
                            title="Cancelar pedido">
                        <i class="fas fa-times"></i> Cancelar
                    </button>

                @elseif($order->state === 'pending')
                    {{-- Compatibilidad con pedidos históricos que aún estén en estado pending. --}}
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

                @elseif($order->state === 'partial_received')
                    <button type="button" class="btn btn-primary"
                            onclick="openReceiveModal()"
                            title="Completar recepción de mercancía">
                        <i class="fas fa-clipboard-check"></i> Completar recepción
                    </button>
                    {{-- Cierre manual con faltantes: permite cerrar el pedido aunque queden productos pendientes. --}}
                    <button type="button" class="btn btn-warning"
                            onclick="openClosePartialModal()"
                            title="Cerrar el pedido aceptando que hay productos faltantes del proveedor">
                        <i class="fas fa-exclamation-triangle"></i> Cerrar con faltantes
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
                    <div class="kv-row"><span>Nº pedido (PO)</span><strong>{{ $po }}</strong></div>
                    <div class="kv-row"><span>Proveedor</span><strong>{{ $supplierName }}</strong></div>
                    <div class="kv-row">
                        <span>Fecha en que se realizó el pedido</span>
                        <strong>{{ $order->date?->format('d/m/Y H:i') ?? '—' }}</strong>
                    </div>
                    <div class="kv-row">
                        <span>Entrega estimada</span>
                        <strong>{{ $order->estimated_delivery_date?->format('d/m/Y') ?? '—' }}</strong>
                    </div>
                    <div class="kv-row">
                        <span>Entregado</span>
                        <strong>
                            @if($order->state === 'cancelled')
                                <span style="color:#9ca3af;">Nunca</span>
                            @elseif($order->delivered_at)
                                {{ $order->delivered_at->format('d/m/Y H:i') }}
                            @elseif($order->received_at)
                                {{ $order->received_at->format('d/m/Y H:i') }}
                            @else
                                <span style="color:#f59e0b;">En proceso</span>
                            @endif
                        </strong>
                    </div>
                    <div class="kv-row">
                        <span>Estado</span>
                        <strong><span class="order-status-pill {{ $order->state }}">{{ $label }}</span></strong>
                    </div>
                    @if($order->closed_with_shorts)
                        <div class="kv-row">
                            <span>Observación</span>
                            <strong style="color:#f59e0b;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Cerrado con faltantes del proveedor
                            </strong>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Auditoría de confirmación desde el historial de estados (transición a confirmado). --}}
            @if($showSupplierConfirmAudit)
                <section class="detail-card cf4-supplier-order-audit">
                    <h2><i class="fas fa-user-check"></i> Confirmación con proveedor</h2>
                    <div class="kv">
                        <div class="kv-row">
                            <span>Fecha y hora</span>
                            <strong>{{ $confirmAuditAt?->format('d/m/Y H:i') ?? '—' }}</strong>
                        </div>
                        <div class="kv-row">
                            <span>Registró</span>
                            <strong>{{ $confirmAuditUserLabel ?? '—' }}</strong>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Tabla de productos --}}
            <section class="detail-card detail-card-wide">
                <h2><i class="fas fa-box"></i> Productos</h2>
                <div class="items-table-wrap">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Pedido</th>
                                @if($showReceivedCol)
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
                                    @if($showReceivedCol)
                                        <td class="num">
                                            {{ (int) ($item->received_quantity ?? 0) }}
                                            @if((int)($item->received_quantity ?? 0) < (int)$item->quantity)
                                                <span title="Recepción incompleta" style="color:#f59e0b; margin-left:4px;">⚠</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="num">₡{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                    <td class="num"><strong>₡{{ number_format((float) $item->total, 2, ',', '.') }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $productColCount }}" class="empty-cell">Sin productos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="detail-summary">
                    @if($hasReceivedData && $hasShorts && $receivedTotal !== null)
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.25rem;">
                            <div style="display:flex; gap:0.75rem; align-items:baseline;">
                                <span>Total pedido</span>
                                <strong>₡{{ number_format($initialTotal, 2, ',', '.') }}</strong>
                            </div>
                            <div style="display:flex; gap:0.75rem; align-items:baseline;">
                                <span>Total recibido</span>
                                <strong>₡{{ number_format($receivedTotal, 2, ',', '.') }}</strong>
                            </div>
                            <div style="display:flex; gap:0.75rem; align-items:baseline;">
                                <span>Faltante</span>
                                <strong style="color:#f59e0b;">₡{{ number_format($shortsTotal, 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    @else
                        <span>Total</span>
                        <strong>₡{{ number_format((float) $order->total, 2, ',', '.') }}</strong>
                    @endif
                </div>
            </section>

            {{-- Historial de estados --}}
            <section class="detail-card detail-card-wide">
                <h2><i class="fas fa-history"></i> Historial de estados</h2>
                @if($order->stateTimeline->isEmpty())
                    <p class="empty-cell" style="text-align:left; padding:0.5rem 0;">Sin registros de historial.</p>
                @else
                    @php
                        $tlLabels = [
                            'draft'            => ['label' => 'Borrador',          'icon' => 'fa-pencil-alt',     'color' => '#64748b'],
                            'pending'          => ['label' => 'Pendiente',         'icon' => 'fa-clock',          'color' => '#f59e0b'],
                            'confirmed'        => ['label' => 'Confirmado',        'icon' => 'fa-check',          'color' => '#3b82f6'],
                            'partial_received' => ['label' => 'Recepción parcial', 'icon' => 'fa-clipboard-check','color' => '#f97316'],
                            'delivered'        => ['label' => 'Entregado',         'icon' => 'fa-truck',          'color' => '#235347'],
                            'cancelled'        => ['label' => 'Cancelado',         'icon' => 'fa-times',          'color' => '#ef4444'],
                        ];
                    @endphp
                    <ol class="order-timeline">
                        @foreach($order->stateTimeline as $entry)
                            @php
                                $tl = $tlLabels[$entry->state] ?? ['label' => ucfirst($entry->state), 'icon' => 'fa-circle', 'color' => '#94a3b8'];
                                $adminName = $entry->admin
                                    ? trim($entry->admin->name . ' ' . ($entry->admin->first_surname ?? ''))
                                    : 'Sistema';
                                // Detectar si este registro de timeline corresponde a un cierre con faltantes.
                                $isClosePartialEntry = $entry->state === 'delivered'
                                    && str_starts_with((string) ($entry->reason ?? ''), '[Cierre con faltantes]');
                            @endphp
                            <li class="tl-item">
                                <div class="tl-dot" style="background:{{ $isClosePartialEntry ? '#f59e0b' : $tl['color'] }};">
                                    <i class="fas {{ $isClosePartialEntry ? 'fa-exclamation-triangle' : $tl['icon'] }}"></i>
                                </div>
                                <div class="tl-body">
                                    <span class="tl-state" style="color:{{ $isClosePartialEntry ? '#f59e0b' : $tl['color'] }};">
                                        {{ $isClosePartialEntry ? 'Cerrado con faltantes' : $tl['label'] }}
                                    </span>
                                    <span class="tl-meta">
                                        <i class="fas fa-user-circle"></i> {{ $adminName }}
                                        &nbsp;·&nbsp;
                                        <i class="fas fa-calendar-alt"></i> {{ $entry->changed_at->format('d/m/Y H:i') }}
                                    </span>
                                    @if($entry->reason)
                                        @php
                                            // Mostrar el motivo sin el prefijo interno.
                                            $displayReason = preg_replace('/^\[Cierre con faltantes\]\s*/', '', $entry->reason);
                                        @endphp
                                        <span class="tl-reason"><i class="fas fa-comment-alt"></i> {{ $displayReason }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>

    {{-- ================================================================
         Modal: Registrar / completar recepción de mercancía
         Disponible cuando el pedido está en Confirmado o Recepción parcial.
         ================================================================ --}}
    @if(in_array($order->state, ['confirmed', 'partial_received']))
    <div id="receive-order-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i>
                    {{ $order->state === 'partial_received' ? 'Completar recepción de mercancía' : 'Registrar recepción de mercancía' }}
                </h3>
                <button type="button" class="modal-close" onclick="closeReceiveModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                @if($order->state === 'partial_received')
                    <p style="margin:0 0 16px; color:var(--color-text-secondary, #5f6368); font-size:.9rem;">
                        Este pedido tiene una <strong>recepción parcial</strong> registrada. Actualiza las cantidades recibidas.
                        Al confirmar, si todos los productos llegan completos el pedido pasará a <strong>Entregado</strong>;
                        de lo contrario se mantendrá en <strong>Recepción parcial</strong>.
                    </p>
                @else
                    <p style="margin:0 0 16px; color:var(--color-text-secondary, #5f6368); font-size:.9rem;">
                        Ingresa la cantidad recibida por cada producto. Si todos los productos se reciben completos
                        el pedido pasará a <strong>Entregado</strong>; si alguno es menor quedará en
                        <strong>Recepción parcial</strong>.
                    </p>
                @endif

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
                                            value="{{ (int) ($item->received_quantity ?? $item->quantity) }}"
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

    {{-- ================================================================
         Modal: Cerrar pedido con faltantes
         Solo disponible cuando el pedido está en Recepción parcial.
         ================================================================ --}}
    @if($order->state === 'partial_received')
    <div id="close-partial-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3 style="color:#b45309;">
                    <i class="fas fa-exclamation-triangle"></i> Cerrar pedido con faltantes
                </h3>
                <button type="button" class="modal-close" onclick="closeClosePartialModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin:0 0 12px; font-size:.9rem; color:var(--color-text-secondary, #5f6368);">
                    Estás a punto de cerrar este pedido aunque no se hayan recibido todos los productos.
                    El estado pasará a <strong>Entregado</strong> y se registrará que hubo
                    <strong>faltantes por parte del proveedor</strong>.
                </p>
                <p style="margin:0 0 16px; font-size:.9rem; color:#b45309;">
                    <i class="fas fa-info-circle"></i>
                    El stock ya ingresado (recepción parcial) <strong>no se revertirá</strong>.
                    Solo se cerrará el pedido sin esperar los productos restantes.
                </p>

                <div id="close-partial-form-error" class="field-error" style="display:none; margin-bottom:12px;" role="alert"></div>

                <div class="form-group">
                    <label for="close-partial-reason" style="font-weight:600; display:block; margin-bottom:6px;">
                        Motivo del cierre con faltantes <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea
                        id="close-partial-reason"
                        rows="3"
                        placeholder="Ej: El proveedor confirmó que no tiene stock del producto faltante y no enviará más unidades."
                        style="width:100%; resize:vertical; padding:8px 10px; border:1px solid #d1d5db;
                               border-radius:8px; font-size:.9rem; font-family:inherit;
                               outline:none; box-sizing:border-box;"
                        maxlength="500"
                    ></textarea>
                    <p style="font-size:.78rem; color:#9ca3af; margin:4px 0 0;">
                        Mínimo 4 caracteres. Este motivo quedará registrado en el historial del pedido.
                    </p>
                </div>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeClosePartialModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="confirm-close-partial-btn"
                        onclick="submitClosePartial('{{ $order->num_order }}')">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar cierre con faltantes
                </button>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
    @vite(['resources/js/admin/orders/supplier-orders.js'])

    <script>
        // ── Modal: Recepción de mercancía ────────────────────────────────────────
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
                        confirmButtonColor: '#235347',
                        confirmButtonText:  'Entendido',
                    });
                }

                window.location.reload();

            } finally {
                btn.disabled = false;
            }
        }

        // ── Modal: Cerrar con faltantes ──────────────────────────────────────────
        function openClosePartialModal() {
            const modal = document.getElementById('close-partial-modal');
            if (!modal) return;
            document.getElementById('close-partial-form-error').style.display = 'none';
            document.getElementById('close-partial-reason').value = '';
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeClosePartialModal() {
            const modal = document.getElementById('close-partial-modal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        async function submitClosePartial(orderId) {
            const reasonEl = document.getElementById('close-partial-reason');
            const errEl    = document.getElementById('close-partial-form-error');
            const btn      = document.getElementById('confirm-close-partial-btn');

            errEl.style.display = 'none';
            errEl.textContent   = '';

            const reason = reasonEl.value.trim();
            if (reason.length < 4) {
                errEl.textContent   = 'El motivo debe tener al menos 4 caracteres.';
                errEl.style.display = 'block';
                reasonEl.focus();
                return;
            }

            btn.disabled = true;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const res  = await fetch(`/supplier-orders/${orderId}/close-partial`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ reason }),
                });

                const data = await res.json().catch(() => ({}));

                if (res.status === 422 && data.errors) {
                    const first = Object.values(data.errors).flat()[0] ?? 'Error de validación.';
                    errEl.textContent   = first;
                    errEl.style.display = 'block';
                    return;
                }

                if (!res.ok || !data.success) {
                    errEl.textContent   = data.message ?? 'No se pudo cerrar el pedido.';
                    errEl.style.display = 'block';
                    return;
                }

                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon:               'warning',
                        title:              'Pedido cerrado con faltantes',
                        text:               data.message,
                        confirmButtonColor: '#b45309',
                        confirmButtonText:  'Entendido',
                    });
                }

                window.location.reload();

            } finally {
                btn.disabled = false;
            }
        }

        // ── Cierre por teclado y clic en overlay ─────────────────────────────────
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeReceiveModal();
                closeClosePartialModal();
            }
        });

        document.getElementById('receive-order-modal')?.addEventListener('click', function (e) {
            if (e.target === this) closeReceiveModal();
        });

        document.getElementById('close-partial-modal')?.addEventListener('click', function (e) {
            if (e.target === this) closeClosePartialModal();
        });
    </script>
@endpush