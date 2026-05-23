@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Movimientos de inventario - Reportes')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/reports-hub.css'])
@endpush

@push('vite-body')
    @vite(['resources/js/shared/ajax-pagination.js'])
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
    <header class="inv-index-header">
        <h1>Movimientos de inventario</h1>
        <p class="inv-index-lead">
            Selecciona un producto para consultar su historial completo de entradas,
            salidas y devoluciones. Puedes buscar por nombre o codigo SKU.
        </p>
    </header>

    {{-- Products table card --}}
    <div class="orders-table-card" data-cf4-ajax-pagination data-cf4-ajax-scroll>

        {{-- Search toolbar --}}
        <form method="GET" action="{{ route('admin.inventory.movements.index') }}"
              class="orders-toolbar" role="search">
            <input type="hidden" name="per_page" value="{{ \App\Support\AdminPerPage::resolve(request('per_page', 10)) }}">
            <div class="filter-group orders-search-wrap">
                <label for="inv-search-input">Buscar producto</label>
                <div class="orders-search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="search"
                        id="inv-search-input"
                        value="{{ request('search') }}"
                        placeholder="Nombre o codigo (ej. BK-004)..."
                        autocomplete="off"
                    >
                </div>
            </div>
            <div class="orders-toolbar-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> Buscar
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.inventory.movements.index') }}"
                       class="btn btn-secondary btn-sm">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Search results summary --}}
        @if(request()->filled('search'))
            <p class="inv-index-results-label" style="padding: 0 0 0.75rem;">
                @if($products->total() > 0)
                    {{ $products->total() }} {{ Str::plural('resultado', $products->total()) }}
                    para <strong>"{{ request('search') }}"</strong>
                @else
                    Ningun producto coincide con <strong>"{{ request('search') }}"</strong>.
                @endif
            </p>
        @endif

        <div id="cf4-list-fragment">
        {{-- Products table --}}
        <div class="sales-table-container">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Categoria</th>
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
                                            Sin stock
                                        @break

                                        @default
                                            Revisar
                                    @endswitch
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($product->stock_current) }}</td>
                            <td>
                                <div class="actions-container">
                                    <a href="{{ route('admin.inventory.movements.show', $product->product_id) }}"
                                       class="btn btn-secondary btn-sm action-btn">
                                        <i class="fas fa-chart-line"></i>
                                        Ver movimientos
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="orders-empty">
                                    <i class="fas fa-inbox orders-empty-icon" aria-hidden="true"></i>
                                    <p>No hay productos para mostrar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Shared pagination --}}
        <div class="orders-pagination-wrap">
            <x-admin.pagination :paginator="$products" label="productos" />
        </div>

        </div>{{-- /#cf4-list-fragment --}}

    </div>{{-- /.orders-table-card --}}

</div>
@endsection
