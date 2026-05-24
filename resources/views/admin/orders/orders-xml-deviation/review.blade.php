@extends('admin.layouts.sales')

@section('Titulo pagina', 'Revisión de precios XML – Admin')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css'])
    <style>
        /* ── Page layout ── */
        .xml-review-container {
            max-width: 1100px; /* FIX: 1300px era demasiado ancho con sidebar */
        }

        /* FIX: igual que en upload — evita que el layout sales infle la altura */
        .sales-container {
            min-height: unset !important;
            height: auto !important;
            padding-bottom: 2rem;
        }

        .xml-review-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
            background: var(--color-surface, #fff);
            border: 1px solid var(--color-border, #e5e7eb);
            border-radius: var(--radius-md, 10px);
            padding: .85rem 1.25rem;
            margin-bottom: 1.25rem;
            font-size: .875rem;
            color: var(--color-text-secondary, #6b7280);
        }

        .xml-review-meta span strong {
            color: var(--color-text-primary, #111827);
        }

        /* ── Table ── */
        .xml-review-table-wrap {
            overflow-x: auto;
            background: var(--color-surface, #fff);
            border: 1px solid var(--color-border, #e5e7eb);
            border-radius: var(--radius-md, 10px);
        }

        .xml-review-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        .xml-review-table thead th {
            background: var(--color-surface-alt, #f9fafb);
            padding: .75rem 1rem;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
            border-bottom: 1px solid var(--color-border, #e5e7eb);
            color: var(--color-text-secondary, #6b7280);
        }

        .xml-review-table tbody td {
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--color-border-light, #f3f4f6);
            vertical-align: middle;
            white-space: nowrap;
        }

        .xml-review-table tbody tr:last-child td {
            border-bottom: none;
        }

        .xml-review-table tbody tr.row-deviation {
            background: #fffbeb;
        }

        .xml-review-table tbody tr.row-not-found {
            background: #fef2f2;
            opacity: .85;
        }

        /* ── Badges ── */
        .badge-deviation {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde68a;
            border-radius: 99px;
            padding: .2rem .65rem;
            font-size: .78rem;
            font-weight: 600;
        }

        .badge-ok {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 99px;
            padding: .2rem .65rem;
            font-size: .78rem;
            font-weight: 600;
        }

        .badge-not-found {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 99px;
            padding: .2rem .65rem;
            font-size: .78rem;
            font-weight: 600;
        }

        /* ── Diff colours ── */
        .diff-positive {
            color: #b91c1c;
            font-weight: 600;
        }

        .diff-negative {
            color: #166534;
            font-weight: 600;
        }

        .diff-zero {
            color: var(--color-text-secondary, #6b7280);
        }

        /* ── Sale price suggestion column ── */
        .sale-price-cell {
            min-width: 210px;
        }

        .sale-price-suggestion {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .sale-price-suggestion .suggestion-hint {
            font-size: .78rem;
            color: var(--color-text-secondary, #6b7280);
            display: flex;
            align-items: center;
            gap: .3rem;
            flex-wrap: wrap;
        }

        .suggestion-hint .hint-arrow {
            color: #d97706;
            font-weight: 700;
        }

        .suggestion-hint .hint-margin {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 99px;
            padding: .05rem .45rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .sale-price-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .sale-price-input-wrap .currency-symbol {
            position: absolute;
            left: .6rem;
            font-size: .85rem;
            color: var(--color-text-secondary, #6b7280);
            pointer-events: none;
            user-select: none;
        }

        .sale-price-input {
            width: 100%;
            padding: .45rem .6rem .45rem 1.5rem;
            border: 1px solid #d97706;
            border-radius: var(--radius-sm, 6px);
            font-size: .875rem;
            background: #fffbeb;
            color: var(--color-text-primary, #111827);
            box-sizing: border-box;
            transition: border-color .15s, box-shadow .15s;
        }

        .sale-price-input:focus {
            outline: none;
            border-color: #b45309;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, .15);
        }

        .sale-price-input.is-modified {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .sale-price-input.is-modified:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
        }

        .sale-price-clear {
            margin-left: .4rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: .85rem;
            padding: .2rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .sale-price-clear:hover {
            color: #b91c1c;
        }

        .sale-price-no-change {
            font-size: .8rem;
            color: #9ca3af;
            font-style: italic;
        }

        /* ── Reason textarea ── */
        .xml-reason-group {
            margin-top: 1.25rem;
        }

        .xml-reason-group label {
            display: block;
            font-size: .875rem;
            font-weight: 500;
            margin-bottom: .4rem;
            color: var(--color-text-primary, #111827);
        }

        .xml-reason-group textarea {
            width: 100%;
            min-height: 70px;
            padding: .55rem .75rem;
            border: 1px solid var(--color-border, #d1d5db);
            border-radius: var(--radius-sm, 6px);
            font-size: .875rem;
            resize: vertical;
            box-sizing: border-box;
            background: var(--color-input-bg, #f9fafb);
            color: var(--color-text-primary, #111827);
        }

        /* ── Bottom actions bar ── */
        .xml-actions-bar {
            display: flex;
            gap: .75rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1.25rem;
        }

        .xml-selected-count {
            font-size: .875rem;
            color: var(--color-text-secondary, #6b7280);
        }

        .xml-select-helpers {
            display: flex;
            gap: .5rem;
            margin-bottom: .75rem;
            flex-wrap: wrap;
        }

        .xml-select-helpers button {
            font-size: .8rem;
            padding: .3rem .7rem;
            cursor: pointer;
        }
    </style>
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $items = $analysis['items'];
        $fileName = $analysis['file_name'];
        $threshold = $analysis['threshold_percentage'];
        $totalItems = count($items);
        $deviationItems = collect($items)->where('has_deviation', true)->where('found', true)->count();
        $notFoundItems = collect($items)->where('found', false)->count();
        $priceUpItems = collect($items)
            ->where('found', true)
            ->filter(fn($i) => $i['suggested_sale_price'] !== null)
            ->count();
    @endphp

    <div class="sales-container xml-review-container">

        @component('admin.partials.page-header', ['title' => 'Revisión de precios XML'])
            <p>
                Revisa las diferencias entre los precios actuales y los precios importados desde el XML.
                Selecciona los productos que deseas actualizar y ajusta el precio de venta cuando corresponda.
            </p>

            @slot('actions')
                <div class="sales-header-actions">
                    <a href="{{ route('admin.supplier-orders.xml-deviation.upload') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Cargar otro XML
                    </a>
                    <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-ghost btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver a pedidos
                    </a>
                </div>
            @endslot
        @endcomponent

        <nav class="reports-breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('admin.supplier-orders.index') }}">Pedidos a proveedor</a>
            <span class="sep">/</span>
            <a href="{{ route('admin.supplier-orders.xml-deviation.upload') }}">Importar XML</a>
            <span class="sep">/</span>
            <span>Revisión de precios</span>
        </nav>

        {{-- Meta bar --}}
        <div class="xml-review-meta">
            <span><strong><i class="fas fa-file-alt"></i></strong> {{ $fileName }}</span>
            <span>Umbral: <strong>{{ number_format($threshold, 1) }}%</strong></span>
            <span>Total productos: <strong>{{ $totalItems }}</strong></span>
            <span>Con desvío: <strong style="color:#b45309;">{{ $deviationItems }}</strong></span>
            @if ($priceUpItems)
                <span>Con alza en compra: <strong style="color:#d97706;">{{ $priceUpItems }}</strong>
                    <span style="font-size:.8rem;">(precio de venta sugerido disponible)</span>
                    </strong></span>
            @endif
            @if ($notFoundItems)
                <span>No encontrados: <strong style="color:#b91c1c;">{{ $notFoundItems }}</strong></span>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.supplier-orders.xml-deviation.apply') }}" id="xml-apply-form">
            @csrf

            {{-- Quick-select helpers --}}
            <div class="xml-select-helpers">
                <button type="button" id="btn-select-deviations" class="btn btn-secondary btn-sm">
                    <i class="fas fa-exclamation-triangle"></i> Seleccionar con desvío
                </button>
                <button type="button" id="btn-select-all" class="btn btn-secondary btn-sm">
                    <i class="fas fa-check-double"></i> Seleccionar todos
                </button>
                <button type="button" id="btn-deselect-all" class="btn btn-ghost btn-sm">
                    <i class="fas fa-times"></i> Deseleccionar todos
                </button>
            </div>

            {{-- Review table --}}
            <div class="xml-review-table-wrap">
                <table class="xml-review-table admin-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Cant.</th>
                            <th>P. compra actual</th>
                            <th>P. compra XML</th>
                            <th>Diferencia</th>
                            <th>% Desvío</th>
                            {{-- New column --}}
                            <th
                                title="Sólo aparece cuando el precio de compra sube. El valor sugerido mantiene el mismo % de margen actual. Puede editarlo o borrarlo para no modificar el precio de venta.">
                                <i class="fas fa-tag" style="color:#d97706;margin-right:.3rem;"></i>
                                Precio de venta
                                <i class="fas fa-info-circle" style="font-size:.75rem;color:#9ca3af;"
                                    title="Editable. Vacío = sin cambios."></i>
                            </th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $rowClass = !$item['found']
                                    ? 'row-not-found'
                                    : ($item['has_deviation']
                                        ? 'row-deviation'
                                        : '');
                                $diff = $item['difference_amount'];
                                $pct = $item['difference_percentage'];
                                $diffClass = $diff > 0 ? 'diff-positive' : ($diff < 0 ? 'diff-negative' : 'diff-zero');
                                $diffSign = $diff > 0 ? '+' : '';
                                $hasSuggestion = $item['found'] && $item['suggested_sale_price'] !== null;
                            @endphp
                            <tr class="{{ $rowClass }}" data-product-id="{{ $item['product_id'] ?? '' }}">

                                {{-- Checkbox --}}
                                <td>
                                    @if ($item['found'])
                                        <input type="checkbox" name="updates[]" value="{{ $item['product_id'] }}"
                                            class="xml-update-checkbox" aria-label="Actualizar {{ $item['name'] }}"
                                            @if ($item['has_deviation']) checked @endif>
                                    @else
                                        <span title="Producto no encontrado en el sistema">—</span>
                                    @endif
                                </td>

                                {{-- Name --}}
                                <td>
                                    @if ($item['found'])
                                        <a href="{{ route('products.show', $item['product_id']) }}" target="_blank"
                                            style="color:var(--color-primary,#2563eb);text-decoration:none;"
                                            title="Ver producto">{{ $item['name'] }}</a>
                                    @else
                                        <span style="color:#9ca3af;">{{ $item['name'] ?: '(sin nombre)' }}</span>
                                    @endif
                                </td>

                                {{-- Code / SKU --}}
                                <td><code style="font-size:.85em;">{{ $item['sku'] ?: '—' }}</code></td>

                                {{-- Quantity --}}
                                <td>{{ number_format($item['quantity'], 0, ',', '.') }}</td>

                                {{-- Current purchase price --}}
                                <td>
                                    @if ($item['found'])
                                        ₡{{ number_format($item['current_price'], 2, ',', '.') }}
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>

                                {{-- XML price --}}
                                <td>₡{{ number_format($item['xml_price'], 2, ',', '.') }}</td>

                                {{-- Difference --}}
                                <td class="{{ $diffClass }}">
                                    @if ($item['found'])
                                        {{ $diffSign }}₡{{ number_format(abs($diff), 2, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- % --}}
                                <td class="{{ $diffClass }}">
                                    @if ($item['found'])
                                        {{ $diffSign }}{{ number_format(abs($pct), 2, ',', '.') }}%
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- ── Sale price suggestion column ─────────────────── --}}
                                <td class="sale-price-cell">
                                    @if ($hasSuggestion)
                                        <div class="sale-price-suggestion">

                                            {{-- Hint line: current → suggested --}}
                                            <div class="suggestion-hint">
                                                <span>₡{{ number_format($item['current_sale_price'], 2, ',', '.') }}</span>
                                                <span class="hint-arrow">→</span>
                                                <span style="color:#b45309;font-weight:600;">
                                                    ₡{{ number_format($item['suggested_sale_price'], 2, ',', '.') }}
                                                </span>
                                                <span class="hint-margin">
                                                    {{ number_format($item['current_margin_pct'], 1) }}% margen
                                                </span>
                                                @if ($item['sale_price_increase'] > 0)
                                                    <span style="color:#b91c1c;font-size:.75rem;">
                                                        (+₡{{ number_format($item['sale_price_increase'], 2, ',', '.') }})
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Editable input pre-filled with suggestion --}}
                                            <div style="display:flex;align-items:center;gap:.3rem;">
                                                <div class="sale-price-input-wrap" style="flex:1;">
                                                    <span class="currency-symbol">₡</span>
                                                    <input type="number" name="sale_prices[{{ $item['product_id'] }}]"
                                                        class="sale-price-input"
                                                        value="{{ $item['suggested_sale_price'] }}"
                                                        min="{{ $item['xml_price'] }}" step="1"
                                                        data-suggested="{{ $item['suggested_sale_price'] }}"
                                                        data-product-id="{{ $item['product_id'] }}"
                                                        aria-label="Nuevo precio de venta para {{ $item['name'] }}"
                                                        title="Edite el valor o borre el campo para no cambiar el precio de venta.">
                                                </div>
                                                {{-- Clear button to revert / leave empty --}}
                                                <button type="button" class="sale-price-clear"
                                                    data-target="{{ $item['product_id'] }}"
                                                    title="Limpiar — no modificará el precio de venta">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>

                                            <div style="font-size:.75rem;color:#9ca3af;">
                                                <i class="fas fa-info-circle"></i>
                                                Vacío = precio de venta sin cambios
                                            </div>
                                        </div>
                                    @elseif ($item['found'])
                                        {{-- Purchase price didn't go up → no suggestion --}}
                                        <span class="sale-price-no-change">
                                            Sin cambio sugerido
                                        </span>
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>
                                {{-- ─────────────────────────────────────────────────── --}}

                                {{-- Status badge --}}
                                <td>
                                    @if (!$item['found'])
                                        <span class="badge-not-found"><i class="fas fa-times-circle"></i> No
                                            encontrado</span>
                                    @elseif ($item['has_deviation'])
                                        <span class="badge-deviation"><i class="fas fa-exclamation-triangle"></i> Desvío
                                            detectado</span>
                                    @else
                                        <span class="badge-ok"><i class="fas fa-check-circle"></i> Sin desvío</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10"
                                    style="text-align:center;padding:2rem;color:var(--color-text-secondary,#6b7280);">
                                    No se encontraron productos en el XML.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Optional reason --}}
            <div class="xml-reason-group">
                <label for="reason">
                    Motivo / nota del ajuste
                    <span style="color:var(--color-text-secondary,#9ca3af);font-weight:400;">(opcional)</span>
                </label>
                <textarea id="reason" name="reason" maxlength="500"
                    placeholder="Ej: Ajuste por alza generalizada de precios del proveedor XYZ en mayo 2025."></textarea>
            </div>

            {{-- Actions bar --}}
            <div class="xml-actions-bar">
                <button type="submit" class="btn btn-primary" id="xml-apply-btn">
                    <i class="fas fa-check"></i> Aplicar cambios seleccionados
                    <span id="selected-count-badge"
                        style="
                    background: rgba(255,255,255,.25);
                    border-radius: 99px;
                    padding: .1rem .5rem;
                    font-size: .8rem;
                    margin-left: .3rem;
                "></span>
                </button>
                <a href="{{ route('admin.supplier-orders.xml-deviation.upload') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <span class="xml-selected-count" id="selected-count-label"></span>
            </div>

        </form>

    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/orders/xml-deviation-review.js'])
@endpush