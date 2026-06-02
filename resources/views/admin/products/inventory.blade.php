<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión de Inventario - Ciclo Finca 4 Admin</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @include('admin.partials.cf4-theme-head')

    {{-- Styles & Fonts --}}
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/products/inventory.css'])
</head>

<body class="admin-layout">

    @php
        $lowStockCardActive = request('stock_status') === 'low';
        $lowStockCardUrl = $lowStockCardActive
            ? route('inventory')
            : route('inventory', ['stock_status' => 'low']);
        $lowStockCardCta = $lowStockCardActive ? 'Ver todo' : 'Abrir inventario filtrado';
    @endphp

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="inventory-container">

            {{-- ==================== HEADER ==================== --}}
            @component('admin.partials.page-header', [
                'title' => 'Gestión de Inventario',
                'description' => 'Administra los productos y el stock del sistema',
            ])
                @slot('actions')
                    <button class="btn btn-primary" id="open-new-product-modal">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </button>
                    <a class="btn btn-secondary" href="{{ route('categories.parents.create') }}">
                        <i class="fas fa-layer-group"></i>
                        Crear categoría
                    </a>
                    <a class="btn btn-secondary" href="{{ route('categories.subcategories.create') }}">
                        <i class="fas fa-sitemap"></i>
                        Crear Subcategoría
                    </a>
                    @include('admin.products.partials.catalog-import-export')
                @endslot
            @endcomponent

            <section class="inventory-kpi-grid" aria-label="Resumen de inventario">
                <a class="inventory-kpi-card" href="{{ $lowStockCardUrl }}">
                    <div class="inventory-kpi-card-head">
                        <h3>Stock bajo</h3>
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                    </div>
                    <p class="inventory-kpi-card-value">{{ number_format((int) ($lowStockProductsCount ?? 0), 0, ',', '.') }}</p>
                    <span class="inventory-kpi-card-link {{ $lowStockCardActive ? 'inventory-kpi-card-link--reset' : '' }}">
                        {{ $lowStockCardCta }}
                    </span>
                </a>
            </section>

            {{-- ==================== FILTERS ==================== --}}
            @component('admin.partials.filters', [
                'action' => route('inventory'),
                'clearUrl' => route('inventory'),
                'formClass' => 'filter-form',
            ])
                @slot('fields')
                        <div class="filter-group">
                            <label for="parent-category-filter">Categoría</label>
                            <select id="parent-category-filter" name="parent_category_id">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}"
                                        @selected((string) request('parent_category_id') === (string) $category->category_id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="subcategory-filter">Subcategoría</label>
                            <select id="subcategory-filter" name="subcategory_id" data-selected="{{ request('subcategory_id') }}">
                                <option value="">Todos los tipos</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="stock">Estado de Stock</label>
                            <select id="stock" name="stock_status">
                                <option value="">Todos los estados</option>
                                <option value="in-stock"  @selected(request('stock_status') === 'in-stock')>En Stock</option>
                                <option value="low"       @selected(request('stock_status') === 'low')>Stock Bajo</option>
                                <option value="out"       @selected(request('stock_status') === 'out')>Sin Stock</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="status">Estado del Producto</label>
                            <select id="status" name="status">
                                <option value="">Todos los estados</option>
                                <option value="active"        @selected(request('status') === 'active')>Activo</option>
                                <option value="inactive"      @selected(request('status') === 'inactive')>Inactivo</option>
                                <option value="out_of_stock"  @selected(request('status') === 'out_of_stock')>Agotado</option>
                                <option value="discontinued"  @selected(request('status') === 'discontinued')>Descontinuado</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="search">Buscar</label>
                            <input type="text" id="search" name="search"
                                   placeholder="Nombre o código" value="{{ request('search') }}">
                        </div>
                @endslot

                @slot('footer')
                    <div class="filters-classification-toggle-row">
                        <button
                            type="button"
                            class="btn btn-primary btn-classification-toggle"
                            id="toggle-classification-filters"
                            aria-expanded="{{ ($hasClassificationSelections ?? false) ? 'true' : 'false' }}"
                            aria-controls="classification-filters-panel">
                            <i class="fas fa-sliders-h"></i>
                            Más filtros por clasificación
                        </button>
                    </div>

                    <div
                        id="classification-filters-panel"
                        class="classification-filters-panel{{ ($hasClassificationSelections ?? false) ? ' is-open' : '' }}"
                        @unless($hasClassificationSelections ?? false) hidden @endunless>
                        <div
                            id="classification-filters-root"
                            data-endpoint-dimensions="{{ route('inventory.classification-filters.dimensions') }}"
                            data-endpoint-suggest-template="{{ route('inventory.classification-filters.suggest', ['slug' => '__SLUG__']) }}"
                            data-initial='@json($activeClassificationFilters ?? [])'>
                            <div id="classification-filters-active" class="classification-filters-active" @if(empty($activeClassificationFilters)) hidden @endif>
                                @foreach(($activeClassificationFilters ?? []) as $activeFilter)
                                    <span class="classification-filter-chip cf-chip">
                                        {{ $activeFilter['dimension_label'] }}: {{ $activeFilter['value_label'] }}
                                        <button type="button" class="cf-chip__clear" aria-label="Quitar filtro {{ $activeFilter['dimension_label'] }}">&times;</button>

                                    </span>
                                @endforeach
                            </div>

                            <p class="classification-filters-hint">Elegí un atributo y buscá el valor. Podés combinar varios filtros.</p>

                            <div class="classification-filters-builder">
                                <div class="filter-group">
                                    <label for="classification-dimension-picker">Atributo</label>
                                    <select id="classification-dimension-picker" disabled>
                                        <option value="">Cargando…</option>
                                    </select>
                                </div>
                                <div class="filter-group classification-value-combobox-wrap">
                                    <label for="classification-value-search">Valor</label>
                                    <div class="cf-combobox classification-filter-combobox">
                                        <input
                                            type="text"
                                            id="classification-value-search"
                                            class="cf-combobox__input"
                                            autocomplete="off"
                                            placeholder="Escribí para buscar…"
                                            disabled>
                                        <div id="classification-value-list" class="cf-combobox__list" role="listbox" hidden></div>
                                    </div>
                                </div>
                            </div>

                            <div id="classification-filters-hidden-inputs">
                                @foreach(($activeClassificationFilters ?? []) as $activeFilter)
                                    <input type="hidden" name="classifications[{{ $activeFilter['slug'] }}]" value="{{ $activeFilter['value'] }}">
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endslot
            @endcomponent

            {{-- ==================== PRODUCT LIST ==================== --}}
            <section class="products-section" data-cf4-ajax-pagination data-cf4-ajax-scroll>

                {{-- Loading overlay shown during async operations --}}
                <div class="loading-spinner-overlay">
                    <div class="spinner"></div>
                </div>

                <div id="cf4-list-fragment">
                {{-- View toggle: table / grid --}}
                <div class="products-header">
                    <div class="products-count">
                        <i class="fas fa-box-open products-count__icon"></i>
                        <span>
                            <strong>{{ $paginator->total() }}</strong>
                            producto{{ $paginator->total() === 1 ? '' : 's' }}
                        </span>
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" data-view="table" title="Vista de tabla">
                            <i class="fas fa-table"></i>
                        </button>
                        <button class="view-btn" data-view="grid" title="Vista de tarjetas">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>

                @php
                    $classificationRequestFilters = collect(request('classifications', []))
                        ->filter(fn ($value) => is_string($value) && trim($value) !== '');
                @endphp
                @if($paginator->total() === 0)
                    <div class="alert alert-info" style="margin: 16px 0;">
                        <i class="fas fa-info-circle"></i>
                        @if($classificationRequestFilters->isNotEmpty())
                            No hay productos para la combinación de clasificaciones seleccionada.
                        @else
                            No hay productos que coincidan con los filtros aplicados.
                        @endif
                    </div>
                @endif

                {{-- Table view --}}
                <div class="products-table table-view active">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Disponibilidad</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th class="admin-table__col--actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paginator as $product)
                                @php $adminAv = $product->adminAvailabilityLabel(); @endphp
                                <tr>
                                    <td class="product-cell">
                                        <div class="product-cell-content">
                                        <div class="product-thumb-wrap product-thumb-wrap--table">
                                            {{-- MediaLibrary image with legacy fallback --}}
                                            @include('shared.media.product-media', [
                                                'product' => $product,
                                                'variant' => 'thumb-table',
                                                'alt' => $product->name,
                                            ])
                                            <button type="button"
                                                    class="featured-star-btn {{ $product->is_featured ? 'is-featured' : '' }}"
                                                    data-product-id="{{ $product->product_id }}"
                                                    data-featured="{{ $product->is_featured ? '1' : '0' }}"
                                                    aria-pressed="{{ $product->is_featured ? 'true' : 'false' }}"
                                                    aria-label="{{ $product->is_featured ? 'Quitar de destacados en tienda' : 'Marcar como destacado en tienda' }}"
                                                    title="Destacado en tienda (inicio y catálogo)">
                                                <i class="featured-star-icon {{ $product->is_featured ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="product-info">
                                            <h4>{{ $product->name }}</h4>
                                            <span class="sku">SKU: {{ $product->displaySku() }}</span>
                                        </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($product->category)
                                            @if($product->category->parent)
                                                {{ $product->category->parent->name }} &gt; {{ $product->category->name }}
                                            @else
                                                {{ $product->category->name }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="stock-badge {{ $product->adminInventoryStockBadgeClass() }}">
                                            {{ $product->stock_current }}
                                        </span>
                                    </td>
                                    <td>
                                        <span @class([
                                            'inventory-availability',
                                            'inventory-availability--ok' => $adminAv === 'Disponible',
                                            'inventory-availability--low' => $adminAv === 'Quedan pocas unidades',
                                            'inventory-availability--out' => $adminAv === 'Agotado',
                                            'inventory-availability--na' => $adminAv === 'No disponible',
                                        ])>{{ $adminAv }}</span>
                                    </td>
                                    <td>₡{{ number_format($product->sale_price, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="status-badge {{ $product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'warning' : 'secondary') }}">
                                            {{ $product->status === 'active' ? 'Activo' : ($product->status === 'inactive' ? 'Inactivo' : ($product->status === 'out_of_stock' ? 'Agotado' : 'Descontinuado')) }}
                                        </span>
                                    </td>
                                    <td class="admin-table__col--actions">
                                        <div class="actions-container">
                                            <button class="action-btn view view-details-btn"
                                                    data-product-id="{{ $product->product_id }}"
                                                    title="View details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit edit-btn"
                                                    data-product-id="{{ $product->product_id }}"
                                                    title="Edit product">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn stock-adjust"
                                                    data-stock-action="add"
                                                    data-product-id="{{ $product->product_id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-stock="{{ $product->stock_current }}"
                                                    title="Add stock">
                                                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                            </button>
                                            <button class="action-btn stock-adjust"
                                                    data-stock-action="remove"
                                                    data-product-id="{{ $product->product_id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-stock="{{ $product->stock_current }}"
                                                    title="Remove stock">
                                                <i class="fas fa-minus-circle" aria-hidden="true"></i>
                                            </button>
                                            @include('admin.products.partials.inventory-status-action', ['product' => $product])
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Grid view --}}
                <div class="products-table grid-view">
                    <div class="products-grid">
                        @foreach($paginator as $product)
                            @php $adminAvGrid = $product->adminAvailabilityLabel(); @endphp
                            <div class="product-card">
                                <div class="product-card-header">
                                    <div class="product-thumb-wrap product-thumb-wrap--card">
                                        @include('shared.media.product-media', [
                                            'product' => $product,
                                            'variant' => 'thumb-card',
                                            'alt' => $product->name,
                                        ])
                                        <button type="button"
                                                class="featured-star-btn {{ $product->is_featured ? 'is-featured' : '' }}"
                                                data-product-id="{{ $product->product_id }}"
                                                data-featured="{{ $product->is_featured ? '1' : '0' }}"
                                                aria-pressed="{{ $product->is_featured ? 'true' : 'false' }}"
                                                aria-label="{{ $product->is_featured ? 'Quitar de destacados en tienda' : 'Marcar como destacado en tienda' }}"
                                                title="Destacado en tienda (inicio y catálogo)">
                                            <i class="featured-star-icon {{ $product->is_featured ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="product-card-info">
                                        <h4>{{ $product->name }}</h4>
                                        <span class="sku">SKU: {{ $product->displaySku() }}</span>
                                    </div>
                                </div>
                                <div class="product-card-details">
                                    <div class="product-card-detail">
                                        <span class="product-card-detail-label">Categoría</span>
                                        <span class="product-card-detail-value">
                                            @if($product->category)
                                                @if($product->category->parent)
                                                    {{ $product->category->parent->name }} &gt; {{ $product->category->name }}
                                                @else
                                                    {{ $product->category->name }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                    <div class="product-card-detail">
                                        <span class="product-card-detail-label">Stock</span>
                                        <span class="product-card-detail-value">
                                            <span class="stock-badge {{ $product->adminInventoryStockBadgeClass() }}">
                                                {{ $product->stock_current }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="product-card-detail">
                                        <span class="product-card-detail-label">Disponibilidad</span>
                                        <span class="product-card-detail-value">
                                            <span @class([
                                                'inventory-availability',
                                                'inventory-availability--ok' => $adminAvGrid === 'Disponible',
                                                'inventory-availability--low' => $adminAvGrid === 'Quedan pocas unidades',
                                                'inventory-availability--out' => $adminAvGrid === 'Agotado',
                                                'inventory-availability--na' => $adminAvGrid === 'No disponible',
                                            ])>{{ $adminAvGrid }}</span>
                                        </span>
                                    </div>
                                    <div class="product-card-detail">
                                        <span class="product-card-detail-label">Precio</span>
                                        <span class="product-card-detail-value">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="product-card-detail">
                                        <span class="product-card-detail-label">Estado</span>
                                        <span class="product-card-detail-value">
                                            <span class="status-badge {{ $product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'warning' : 'secondary') }}">
                                                {{ $product->status === 'active' ? 'Activo' : ($product->status === 'inactive' ? 'Inactivo' : ($product->status === 'out_of_stock' ? 'Agotado' : 'Descontinuado')) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="product-card-actions">
                                    <div class="actions-container">
                                        <button class="action-btn view view-details-btn"
                                                data-product-id="{{ $product->product_id }}"
                                                title="View details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit edit-btn"
                                                data-product-id="{{ $product->product_id }}"
                                                title="Edit product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        {{-- Add stock (green plus) --}}
                                        <button class="action-btn stock-adjust"
                                                data-stock-action="add"
                                                data-product-id="{{ $product->product_id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-product-stock="{{ $product->stock_current }}"
                                                title="Add stock">
                                            <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        </button>
                                        {{-- Remove stock (red minus) --}}
                                        <button class="action-btn stock-adjust"
                                                data-stock-action="remove"
                                                data-product-id="{{ $product->product_id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-product-stock="{{ $product->stock_current }}"
                                                title="Remove stock">
                                            <i class="fas fa-minus-circle" aria-hidden="true"></i>
                                        </button>
                                        @include('admin.products.partials.inventory-status-action', ['product' => $product])
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pagination-wrapper">
                    <x-admin.pagination :paginator="$paginator" label="inventario" />
                </div>
                </div>

            </section>
            </div>
        </div>
    </main>

    {{-- ==================== MODAL: NEW PRODUCT ==================== --}}
    <div class="edit-modal" id="new-product-modal" role="dialog" aria-modal="true" aria-labelledby="new-product-modal-title" aria-hidden="true">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3 id="new-product-modal-title"><i class="fas fa-plus-circle"></i> Nuevo Producto</h3>
                <button type="button" class="modal-close" id="close-new-product-modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="new-product-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <section class="form-section" data-section="basic">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Datos básicos</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-name">Nombre del Producto *</label>
                            <input type="text" id="new-name" name="name" placeholder="e.g., Bike tire" required>
                        </div>
                        <div class="form-group">
                            <label for="new-description">Descripción</label>
                            <textarea id="new-description" name="description" rows="3"
                                      placeholder="e.g., High quality off-road tire"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-parent-category-search">Categoría *</label>
                            <div class="brand-combobox" id="new-parent-category-combobox">
                                <input type="text" id="new-parent-category-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar una categoría..." autocomplete="off"
                                       aria-label="Categoría del producto">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="new-parent-category-dropdown" role="listbox"></div>
                            </div>
                            <input type="hidden" id="new-parent-category" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="new-subcategory-search">Subcategoría <span class="text-muted">(recomendado)</span></label>
                            <input type="hidden" id="new-subcategory" value="">
                            <div class="brand-combobox admin-search-combobox" id="new-subcategory-combobox">
                                <input type="text" id="new-subcategory-search" class="brand-combobox-input"
                                       placeholder="Seleccioná primero una categoría" autocomplete="off"
                                       aria-label="Subcategoría del producto" aria-describedby="new-subcategory-hint">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="new-subcategory-dropdown" role="listbox"></div>
                            </div>
                            <small id="new-subcategory-hint" class="form-text text-muted"
                                data-default-hint="Si no elegís subcategoría (ej. solo «Bicicletas»), no vas a poder cargar color, talla, etc. Elegí una subcategoría (ej. MTB) cuando exista.">
                                Elegí categoría y, si aplica, subcategoría. Sin subcategoría no podrás usar atributos como color o talla hasta que existan en catálogo.
                            </small>
                            <input type="hidden" id="new-parent-category-id" name="parent_category_id" value="">
                            <input type="hidden" id="new-category" name="category_id" value="">
                        </div>
                        <div class="form-group">
                            <label for="new-provider-search">Proveedor *</label>
                            <div class="brand-combobox" id="new-provider-combobox">
                                <input type="text" id="new-provider-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar un proveedor..." autocomplete="off"
                                       aria-label="Proveedor del producto">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="new-provider-dropdown" role="listbox"></div>
                            </div>
                            <input type="hidden" id="new-provider" name="supplier_id" value="" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Marca *</label>
                            <div class="brand-combobox" id="new-brand-combobox">
                                <input type="text" id="new-brand-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar una marca..." autocomplete="off">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="new-brand-dropdown"></div>
                            </div>
                            <input type="hidden" id="new-brand" name="brand_id">
                        </div>
                    </div>
                        </div>
                    </section>

                    <section class="form-section" data-section="pricing">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Precios y stock</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-price-buy">Precio de Compra (₡) *</label>
                            <input type="number" id="new-price-buy" name="purchase_price"
                                   min="0" step="0.01" placeholder="e.g., 10000" required>
                        </div>
                        <div class="form-group">
                            <label for="new-price-sell">Precio de Venta (₡) *</label>
                            <input type="number" id="new-price-sell" name="sale_price"
                                   min="0" step="0.01" placeholder="e.g., 15000" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-stock">Stock Actual *</label>
                            <input type="number" id="new-stock" name="stock_current"
                                   min="0" placeholder="e.g., 50" required>
                        </div>
                        <div class="form-group">
                            <label for="new-stock-min">Stock Mínimo *</label>
                            <input type="number" id="new-stock-min" name="stock_minimum"
                                   min="0" placeholder="e.g., 10" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-status">Estado *</label>
                        <select id="new-status" name="status" required>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="out_of_stock">Agotado</option>
                            <option value="discontinued">Descontinuado</option>
                        </select>
                    </div>
                        </div>
                    </section>

                    <section class="form-section" data-section="images">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Imágenes</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                            <x-shared.file-upload
                                id="new-image"
                                name="image"
                                label="Imagen del Producto"
                                accept="image/*"
                                variant="compact"
                                icon="fa-image"
                                meta-id="new-image-meta">
                                Haz clic o arrastra la imagen principal
                            </x-shared.file-upload>
                            <x-shared.file-upload
                                id="new-images"
                                name="images[]"
                                label="Imágenes adicionales (carrusel)"
                                accept="image/*"
                                :multiple="true"
                                :webkitdirectory="true"
                                hint="Opcional. Carpeta o varios archivos para el carrusel en la ficha del producto."
                                meta-id="new-images-meta"
                                icon="fa-images">
                                Seleccioná una carpeta o varios archivos
                            </x-shared.file-upload>
                        </div>
                    </section>

                    <section class="form-section" data-section="classification">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Clasificación y destacado</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-group" id="new-classification-section">
                        <label id="new-classification-heading">Atributos (color, talla…)</label>
                        <div id="new-classification-fields" aria-labelledby="new-classification-heading"></div>
                        <small class="form-text text-muted classification-section-hint">Un valor por atributo cuando el producto tiene subcategoría.</small>
                    </div>
                    <div class="form-group form-group-featured">
                        <div class="featured-store-toggle">
                            <input type="checkbox" id="new-featured" class="featured-store-toggle__input" value="1"
                                   aria-describedby="new-featured-desc">
                            <label for="new-featured" class="featured-store-toggle__copy">
                                <span class="featured-store-toggle__title">Destacado en tienda</span>
                                <small id="new-featured-desc" class="featured-store-toggle__desc">Se muestra en el inicio y en «Destacados y novedades» del catálogo público.</small>
                            </label>
                        </div>
                    </div>
                        </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-new-product">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-new-product">
                    <i class="fas fa-plus"></i> Crear Producto
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: EDIT PRODUCT ==================== --}}
    {{-- Form fields populated dynamically by inventory.js --}}
    <div class="edit-modal" id="edit-modal" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title" aria-hidden="true">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3 id="edit-modal-title"><i class="fas fa-edit"></i> Editar Producto</h3>
                <button type="button" class="modal-close" id="modal-close" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-product-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- Spoofs PUT method since HTML forms only support GET/POST --}}
                    <input type="hidden" name="_method" value="PUT">

                    <section class="form-section" data-section="basic">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Datos básicos</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-name">Nombre del Producto *</label>
                            <input type="text" id="edit-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-description">Descripción</label>
                            <textarea id="edit-description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-parent-category-search">Categoría *</label>
                            <div class="brand-combobox" id="edit-parent-category-combobox">
                                <input type="text" id="edit-parent-category-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar una categoría..." autocomplete="off"
                                       aria-label="Categoría del producto">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="edit-parent-category-dropdown" role="listbox"></div>
                            </div>
                            <input type="hidden" id="edit-parent-category" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-subcategory-search">Subcategoría <span class="text-muted">(recomendado)</span></label>
                            <input type="hidden" id="edit-subcategory" value="">
                            <div class="brand-combobox admin-search-combobox" id="edit-subcategory-combobox">
                                <input type="text" id="edit-subcategory-search" class="brand-combobox-input"
                                       placeholder="Seleccioná primero una categoría" autocomplete="off"
                                       aria-label="Subcategoría del producto" aria-describedby="edit-subcategory-hint">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="edit-subcategory-dropdown" role="listbox"></div>
                            </div>
                            <small id="edit-subcategory-hint" class="form-text text-muted"
                                data-default-hint="Si no elegís subcategoría (ej. solo «Bicicletas»), no vas a poder cargar color, talla, etc. Elegí una subcategoría (ej. MTB) cuando exista.">
                                Elegí categoría y, si aplica, subcategoría. Sin subcategoría no podrás usar atributos como color o talla hasta que existan en catálogo.
                            </small>
                            <input type="hidden" id="edit-parent-category-id" name="parent_category_id" value="">
                            <input type="hidden" id="edit-category" name="category_id" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-provider-search">Proveedor *</label>
                            <div class="brand-combobox" id="edit-provider-combobox">
                                <input type="text" id="edit-provider-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar un proveedor..." autocomplete="off"
                                       aria-label="Proveedor del producto">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="edit-provider-dropdown" role="listbox"></div>
                            </div>
                            <input type="hidden" id="edit-provider" name="supplier_id" value="" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Marca *</label>
                            <div class="brand-combobox" id="edit-brand-combobox">
                                <input type="text" id="edit-brand-search" class="brand-combobox-input"
                                       placeholder="Escribe para buscar una marca..." autocomplete="off">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="edit-brand-dropdown"></div>
                            </div>
                            <input type="hidden" id="edit-brand" name="brand_id">
                        </div>
                    </div>
                        </div>
                    </section>

                    <section class="form-section" data-section="pricing">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Precios y stock</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-price-buy">Precio de Compra (₡) *</label>
                            <input type="number" id="edit-price-buy" name="purchase_price" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-price-sell">Precio de Venta (₡) *</label>
                            <input type="number" id="edit-price-sell" name="sale_price" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-stock">Stock Actual *</label>
                            <input type="number" id="edit-stock" name="stock_current" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-stock-min">Stock Mínimo *</label>
                            <input type="number" id="edit-stock-min" name="stock_minimum" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-status">Estado *</label>
                        <select id="edit-status" name="status" required>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="out_of_stock">Agotado</option>
                            <option value="discontinued">Descontinuado</option>
                        </select>
                    </div>
                        </div>
                    </section>

                    <section class="form-section" data-section="images">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Imágenes</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body form-section__body--images">
                            <input type="hidden" id="edit-remove-main-image" name="remove_main_image" value="0">
                            <div id="current-image-preview" class="cf-product-current-image" hidden></div>
                            <x-shared.file-upload
                                id="edit-image"
                                name="image"
                                label="Imagen del Producto"
                                accept="image/*"
                                variant="compact"
                                icon="fa-image"
                                meta-id="edit-image-meta">
                                Haz clic o arrastra para reemplazar la imagen principal
                            </x-shared.file-upload>
                            <x-shared.file-upload
                                id="edit-images"
                                name="images[]"
                                label="Imágenes adicionales (carrusel)"
                                accept="image/*"
                                :multiple="true"
                                :webkitdirectory="true"
                                hint="Opcional. Al subir nuevas, reemplazan las actuales del carrusel."
                                meta-id="edit-images-meta"
                                icon="fa-images">
                                Seleccioná una carpeta o varios archivos
                            </x-shared.file-upload>
                        </div>
                    </section>

                    <section class="form-section" data-section="classification">
                        <button type="button" class="form-section__toggle" aria-expanded="true">
                            <span>Clasificación, destacado y variantes</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="form-section__body">
                    <div class="form-group" id="edit-classification-section">
                        <label id="edit-classification-heading">Atributos (color, talla…)</label>
                        <div id="edit-classification-fields" aria-labelledby="edit-classification-heading"></div>
                        <small class="form-text text-muted classification-section-hint">Un valor por atributo cuando el producto tiene subcategoría.</small>
                    </div>
                    <div class="form-group form-group-featured">
                        <div class="featured-store-toggle">
                            <input type="checkbox" id="edit-featured" class="featured-store-toggle__input" value="1"
                                   aria-describedby="edit-featured-desc">
                            <label for="edit-featured" class="featured-store-toggle__copy">
                                <span class="featured-store-toggle__title">Destacado en tienda</span>
                                <small id="edit-featured-desc" class="featured-store-toggle__desc">Se muestra en el inicio y en «Destacados y novedades» del catálogo público.</small>
                            </label>
                        </div>
                    </div>

                    {{-- CF4-74 — Variantes / presentaciones del producto --}}
                    <div class="form-group" id="edit-variants-section">
                        <label>Variantes / presentaciones</label>
                        <div style="display:flex; gap:10px; align-items:flex-start; margin: 0.35rem 0 0.5rem;">
                            <div class="brand-combobox" id="edit-variant-combobox" style="flex:1;">
                                <input
                                    type="text"
                                    id="edit-variant-search"
                                    class="brand-combobox-input"
                                    placeholder="Buscar producto para agregar como variante (nombre o SKU)…"
                                    autocomplete="off"
                                    aria-label="Buscar producto para agregar como variante">
                                <span class="brand-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                <div class="brand-combobox-dropdown" id="edit-variant-dropdown"></div>
                            </div>
                            <input type="hidden" id="edit-variant-product-id" value="">
                            <button type="button" class="btn btn-primary" id="edit-variant-add-btn" disabled>
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                        <div id="edit-variants-list" class="form-text text-muted">—</div>
                        <small class="form-text text-muted">
                            Eliminá solo una variante sin afectar el producto base. No se permite si la variante tiene pedidos activos o pendientes.
                        </small>
                    </div>
                        </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-edit">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-edit">Guardar Cambios</button>
            </div>
        </div>
    </div>

    {{-- CF4-72 — Editar variante (precio, stock, SKU condicional) --}}
    <div class="edit-modal" id="variant-edit-modal" aria-hidden="true">
        <div class="modal-backdrop" id="variant-edit-modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group"></i> Editar variante</h3>
                <button type="button" class="modal-close" id="variant-edit-modal-close" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="margin-bottom: 1rem;" id="variant-edit-variant-title"></p>
                <div class="form-group">
                    <label for="variant-edit-sku-input">SKU</label>
                    <input type="text" id="variant-edit-sku-input" class="form-control" maxlength="64" autocomplete="off">
                    <small class="form-text text-muted" id="variant-edit-sku-hint-default"></small>
                    <small class="form-text text-warning" id="variant-edit-sku-locked-msg" style="display:none;">
                        SKU bloqueado por historial de ventas.
                    </small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="variant-edit-sale-price">Precio de venta (₡) *</label>
                        <input type="number" id="variant-edit-sale-price" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="variant-edit-stock">Stock actual *</label>
                        <input type="number" id="variant-edit-stock" class="form-control" min="0" step="1" required>
                    </div>
                </div>
                <input type="hidden" id="variant-edit-base-id" value="">
                <input type="hidden" id="variant-edit-variant-id" value="">
                <input type="hidden" id="variant-edit-sku-locked" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="variant-edit-cancel-btn">Cancelar</button>
                <button type="button" class="btn btn-primary" id="variant-edit-save-btn">Guardar variante</button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: VIEW PRODUCT DETAILS ==================== --}}
    {{-- Body content injected dynamically by inventory.js --}}
    <div class="edit-modal" id="view-product-modal" role="dialog" aria-modal="true" aria-labelledby="view-product-modal-title" aria-hidden="true">
        <div class="modal-backdrop" id="view-product-modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3 id="view-product-modal-title"><i class="fas fa-eye"></i> Detalles del Producto</h3>
                <button type="button" class="modal-close" id="close-view-product-modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-product-body">
                <div class="loading-spinner" role="status">
                    <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                    <p>Cargando detalles…</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-view-product">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Árbol padre → hijos para filtros y modales (inventory.js) --}}
    <script>
        window.inventoryCategoryTree = @json($subcategoriesByParent ?? []);
        window.inventoryBrands = @json($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name]) ?? []);
        window.inventoryParentCategories = @json($categories->map(fn($c) => ['id' => $c->category_id, 'name' => $c->name]));
        window.inventorySuppliers = @json($suppliers->map(fn($s) => ['id' => $s->supplier_id, 'name' => $s->name]));
    </script>

    {{-- ==================== MODAL: STOCK ADJUSTMENT ==================== --}}
    <div class="edit-modal" id="stock-adjust-modal" role="dialog" aria-modal="true"
         aria-labelledby="stock-modal-title">
        <div class="stock-modal-backdrop"></div>

        <div class="stock-modal-box" id="stock-modal-box">

            {{-- Header --}}
            <div class="stock-modal-header">
                <h3>
                    <i id="stock-modal-title-icon" class="fas fa-plus-circle modal-icon-add"></i>
                    <span id="stock-modal-title">Agregar Stock</span>
                </h3>
                <button class="stock-modal-close" id="stock-modal-close-btn"
                        aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Product info strip --}}
            <div class="stock-modal-product-info">
                <div class="stock-modal-product-info__icon" aria-hidden="true">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stock-modal-product-info__body">
                    <div class="product-name" id="stock-modal-product-name">—</div>
                    <div class="product-stock">
                        <span class="product-stock__label">Stock actual</span>
                        <span class="stock-pill" id="stock-modal-product-stock">—</span>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="stock-modal-body">

                {{-- Hidden product ID --}}
                <input type="hidden" id="stock-modal-product-id" value="">

                {{-- Alert banner --}}
                <div class="stock-modal-alert" id="stock-modal-alert" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="stock-modal-alert-msg"></span>
                </div>

                {{-- Quantity --}}
                <div class="stock-form-group">
                    <label for="stock-modal-qty">Cantidad *</label>
                    <input type="number"
                           id="stock-modal-qty"
                           class="stock-form-control"
                           min="1"
                           step="1"
                           inputmode="numeric"
                           placeholder="Ej: 10"
                           required>
                    <p class="stock-form-hint" id="stock-modal-preview" hidden></p>
                    <span class="stock-field-error" id="stock-modal-qty-error"></span>
                </div>

                {{-- Reason --}}
                <div class="stock-form-group">
                    <label for="stock-modal-reason">Motivo *</label>
                    <textarea id="stock-modal-reason"
                              class="stock-form-control stock-form-control--textarea"
                              rows="3"
                              placeholder="Describe el motivo del ajuste…"
                              maxlength="500"
                              autocomplete="off"
                              required></textarea>
                    <span class="stock-form-charcount" id="stock-modal-reason-count">0 / 500</span>
                    <span class="stock-field-error" id="stock-modal-reason-error"></span>
                </div>

            </div>

            {{-- Footer --}}
            <div class="stock-modal-footer">
                <button type="button" class="stock-btn stock-btn-cancel"
                        id="stock-modal-cancel-btn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="stock-btn stock-btn-confirm-add"
                        id="stock-modal-confirm-btn">
                    <span class="spinner-border-sm" id="stock-modal-confirm-spinner"
                          style="display:none;" aria-hidden="true"></span>
                    <span id="stock-modal-confirm-text">Confirmar</span>
                </button>
            </div>

        </div>
    </div>

    @include('admin.partials.cf4-flash-swal')

    @vite(['resources/js/admin/shell.js', 'resources/js/admin/inventory/inventory-entry.js'])
    @include('admin.partials.cf4-theme-scripts')
</body>
</html>
