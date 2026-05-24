@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Movimientos de inventario - Reportes')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/reports-hub.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

<div class="inv-index-report">

    {{-- ==================== BREADCRUMB ==================== --}}
    <nav class="reports-breadcrumb">
        <a href="{{ route('admin.reports.index') }}">Reportes</a>
        <span class="sep">/</span>
        <span>Movimientos de inventario</span>
    </nav>

    {{-- ==================== PAGE HEADER ==================== --}}
    @component('admin.partials.page-header', [
        'title' => 'Movimientos de inventario',
        'description' =>
            'Selecciona un producto para consultar su historial completo de entradas, salidas y devoluciones. Puedes buscar por nombre o código SKU.',
    ])

        @slot('actions')
            <a
                href="{{ route('admin.reports.index') }}"
                class="btn btn-secondary">

                <i class="fas fa-arrow-left"></i>
                Reportes
            </a>
        @endslot

    @endcomponent

    {{-- ==================== TABLE CARD ==================== --}}
    <div class="orders-table-card" data-cf4-ajax-pagination data-cf4-ajax-scroll>

        {{-- Search toolbar --}}
        <form
            method="GET"
            action="{{ route('admin.inventory.movements.index') }}"
            class="orders-toolbar"
            role="search">

            <input
                type="hidden"
                name="per_page"
                value="{{ \App\Support\AdminPerPage::resolve(request('per_page', 10)) }}">

            <div class="filter-group orders-search-wrap">
                <label for="inv-search-input">Buscar producto</label>

                <div class="orders-search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>

                    <input
                        type="search"
                        name="search"
                        id="inv-search-input"
                        value="{{ request('search') }}"
                        placeholder="Nombre o código (ej. BK-004)..."
                        autocomplete="off">
                </div>
            </div>

            <div class="orders-toolbar-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i>
                    Buscar
                </button>

                @if(request()->filled('search'))
                    <a
                        href="{{ route('admin.inventory.movements.index') }}"
                        class="btn btn-secondary btn-sm">
                        Limpiar
                    </a>
                @endif
            </div>

        </form>

        {{-- Results label --}}
        @if(request()->filled('search'))
            <p class="inv-index-results-label" style="padding:0 0 0.75rem;">
                @if($products->total() > 0)
                    {{ $products->total() }} {{ Str::plural('resultado', $products->total()) }}
                    para <strong>"{{ request('search') }}"</strong>
                @else
                    Ningún producto coincide con <strong>"{{ request('search') }}"</strong>.
                @endif
            </p>
        @endif

        {{-- ==================== TABLE ==================== --}}
        <div id="cf4-list-fragment">

            <div class="sales-table-container">

                <table class="sales-table admin-table">

                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Estado stock</th>
                            <th class="text-end admin-table__col--end">Stock actual</th>
                            <th class="admin-table__col--actions">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($products as $product)

                            @php
                                $badgeClass = $product->adminInventoryStockBadgeClass();
                            @endphp

                            <tr>

                                <td>
                                    <strong class="po-number">
                                        {{ \App\Models\Product::skuFromId($product->product_id) }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $product->name }}
                                </td>

                                <td>
                                    {{ $product->category?->name ?? '—' }}
                                </td>

                                <td>
                                    @if ($product->supplier)
                                        <span>
                                            <i class="fas fa-truck-fast"
                                               style="font-size:0.75rem;margin-right:0.3rem;opacity:.6"></i>
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
                                                Sin stock
                                            @break

                                            @default
                                                Revisar
                                        @endswitch
                                    </span>
                                </td>

                                <td class="text-end admin-table__col--end">
                                    <strong style="color: var(--stock-color-{{ $badgeClass }}, inherit)">
                                        {{ number_format($product->stock_current) }}
                                    </strong>
                                    <span style="font-size:0.78rem;color:#6b7280">unid.</span>
                                </td>

                                <td class="admin-table__col--actions">
                                    <div class="actions-container">
                                        <a
                                            href="{{ route('admin.inventory.movements.show', $product->product_id) }}"
                                            class="action-btn secondary"
                                            title="Ver movimientos">

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

                                        <p style="margin:0;">
                                            @if(request()->filled('search'))
                                                Ningún producto coincide con «{{ request('search') }}».
                                            @else
                                                No hay productos registrados aún.
                                            @endif
                                        </p>

                                        @if(request()->filled('search'))
                                            <p style="margin-top:0.75rem;">
                                                <a href="{{ route('admin.inventory.movements.index') }}">
                                                    Limpiar búsqueda
                                                </a>
                                            </p>
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

                {{-- Pagination --}}
                <div class="pagination-wrapper">
                    <x-admin.pagination :paginator="$products" label="productos" />
                </div>

            </div>

        </div>

    </div>

</div>

@endsection