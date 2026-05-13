@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Movimientos de inventario - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="inv-index-report">

        {{-- Reports breadcrumb --}}
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Movimientos de inventario</span>
        </nav>

        {{-- Page header --}}
        @component('admin.partials.page-header', ['title' => 'Historial de movimientos de inventario'])
            <p>
                Consulta los productos registrados y accede al detalle de sus entradas, salidas, ajustes y devoluciones de
                inventario.
                Puedes buscar por nombre o código SKU.
            </p>
        @endcomponent

        {{-- Products table card --}}
        <div class="orders-table-card">

            {{-- Search toolbar --}}
            <form method="GET" action="{{ route('admin.inventory.movements.index') }}" class="orders-toolbar" role="search">
                <div class="filter-group orders-search-wrap">
                    <label for="inv-search-input">Buscar producto</label>
                    <div class="orders-search-field">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" name="search" id="inv-search-input" value="{{ request('search') }}"
                            placeholder="Nombre o código (ej. BK-004)…" autocomplete="off">
                    </div>
                </div>
                <div class="orders-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Buscar
                    </button>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.inventory.movements.index') }}" class="btn btn-secondary btn-sm">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            {{-- Search results summary --}}
            @if (request()->filled('search'))
                <p class="inv-index-results-label" style="padding: 0 0 0.75rem;">
                    @if ($products->total() > 0)
                        {{ $products->total() }} {{ Str::plural('resultado', $products->total()) }}
                        para <strong>«{{ request('search') }}»</strong>
                    @else
                        Ningún producto coincide con <strong>«{{ request('search') }}»</strong>.
                    @endif
                </p>
            @endif

            {{-- Products table --}}
            <div class="sales-table-container">
                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Estado stock</th>
                            <th class="text-end">Stock actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php $badgeClass = $product->adminInventoryStockBadgeClass(); @endphp
                            <tr>
                                <td>
                                    <strong class="po-number">
                                        {{ \App\Models\Product::skuFromId($product->product_id) }}
                                    </strong>
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>
                                    {{ $product->category?->name ?? '—' }}
                                </td>
                                <td>
                                    @if ($product->supplier)
                                        <span>
                                            <i class="fas fa-truck-fast" aria-hidden="true"
                                                style="font-size:0.75rem; margin-right:0.3rem; opacity:.6"></i>
                                            {{ $product->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="order-status-pill {{ $badgeClass }}">
                                        @switch($badgeClass)
                                            @case('success')
                                                Normal
                                            @break

                                            @case('warning')
                                                Bajo
                                            @break

                                            @case('danger')
                                                Crítico
                                            @break

                                            @default
                                                Sin stock
                                        @endswitch
                                    </span>
                                </td>
                                <td class="text-end">
                                    <strong style="color: var(--stock-color-{{ $badgeClass }}, inherit)">
                                        {{ number_format($product->stock_current) }}
                                    </strong>
                                    <span style="font-size:0.78rem; color:var(--color-text-muted,#6b7280)"> unid.</span>
                                </td>
                                <td>
                                    <div class="actions-container">
                                        <a href="{{ route('admin.inventory.movements.show', $product->product_id) }}"
                                            class="action-btn secondary" title="Ver movimientos de {{ $product->name }}">
                                            <i class="fas fa-clock-rotate-left"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="orders-empty">
                                            <div class="orders-empty-icon">
                                                <i class="fas fa-box-open"></i>
                                            </div>
                                            <p style="margin:0; font-size:1rem;">
                                                @if (request()->filled('search'))
                                                    Ningún producto coincide con «{{ request('search') }}».
                                                @else
                                                    No hay productos registrados aún.
                                                @endif
                                            </p>
                                            @if (request()->filled('search'))
                                                <p style="margin:0.75rem 0 0; font-size:0.9rem;">
                                                    <a href="{{ route('admin.inventory.movements.index') }}">Limpiar
                                                        búsqueda</a>
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Shared pagination component --}}
                @if ($products->hasPages())
                    <div class="orders-pagination-wrap">
                        <small class="inv-index-pagination-info">
                            Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }}
                            de {{ $products->total() }} productos
                        </small>
                        <x-pagination :paginator="$products" label="productos" />
                    </div>
                @endif

            </div>{{-- /.orders-table-card --}}

        </div>
    @endsection
