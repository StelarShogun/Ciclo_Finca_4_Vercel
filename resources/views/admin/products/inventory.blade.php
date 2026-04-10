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

    {{-- Styles & Fonts --}}
    @vite(['resources/css/admin/products/inventory.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="inventory-container">

            {{-- ==================== HEADER ==================== --}}
            <header class="usuarios-header">
                <div>
                    <h1>Gestión de Inventario</h1>
                    <p>Administra los productos y el stock del sistema</p>
                </div>
                <div class="usuarios-actions">
                    <button class="btn btn-primary" id="open-new-product-modal">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </button>
                    <a class="btn btn-secondary" href="{{ route('categories.subcategories.create') }}">
                        <i class="fas fa-sitemap"></i>
                        Crear Subcategoría
                    </a>
                    <button class="btn btn-secondary" id="export-btn">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button class="btn btn-secondary" id="import-btn">
                        <i class="fas fa-upload"></i> Importar
                    </button>
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('status'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> {{ session('status') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- ==================== FILTERS ==================== --}}
            <div class="filters-section">
                <h2 class="filters-title">Filtros de Búsqueda</h2>
                <form class="filter-form">
                    <div class="filters-grid">

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
                                <option value="">Todas las subcategorías</option>
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

                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                        </div>

                        <div class="filter-group">
                            <button type="button" class="btn btn-primary" id="clear-filters">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- ==================== PRODUCT LIST ==================== --}}
            <section class="products-section">

                {{-- Loading overlay shown during async operations --}}
                <div class="loading-spinner-overlay">
                    <div class="spinner"></div>
                </div>

                {{-- View toggle: table / grid --}}
                <div class="products-header">
                    <div class="products-count">
                        <span>{{ $paginator->total() }} products</span>
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" data-view="table">
                            <i class="fas fa-table"></i>
                        </button>
                        <button class="view-btn" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>

                {{-- Table view --}}
                <div class="products-table table-view active">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Disponibilidad</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paginator as $product)
                                @php $adminAv = $product->adminAvailabilityLabel(); @endphp
                                <tr>
                                    <td class="product-cell">
                                        {{-- Falls back to default image if none is set --}}
                                        <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                             alt="{{ $product->name }}">
                                        <div class="product-info">
                                            <h4>{{ $product->name }}</h4>
                                            <span class="sku">SKU: {{ 'BK-' . str_pad($product->product_id, 3, '0', STR_PAD_LEFT) }}</span>
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
                                        {{-- Stock badge: success >10, warning >0, danger =0 --}}
                                        <span class="stock-badge {{ $product->stock_current > \App\Models\Product::CLIENT_LOW_STOCK_THRESHOLD ? 'success' : ($product->stock_current > 0 ? 'warning' : 'danger') }}">
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
                                    <td>
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
                                            <button class="action-btn delete"
                                                    data-action="delete"
                                                    data-product-id="{{ $product->product_id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    title="Delete product">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                                    <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                         alt="{{ $product->name }}" class="product-card-image">
                                    <div class="product-card-info">
                                        <h4>{{ $product->name }}</h4>
                                        <span class="sku">SKU: {{ 'BK-' . str_pad($product->product_id, 3, '0', STR_PAD_LEFT) }}</span>
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
                                            <span class="stock-badge {{ $product->stock_current > \App\Models\Product::CLIENT_LOW_STOCK_THRESHOLD ? 'success' : ($product->stock_current > 0 ? 'warning' : 'danger') }}">
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
                                        <button class="action-btn delete"
                                                data-action="delete"
                                                data-product-id="{{ $product->product_id }}"
                                                data-product-name="{{ $product->name }}"
                                                title="Delete product">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pagination component --}}
                <x-pagination :paginator="$paginator" label="inventory" />

            </section>
        </div>
    </main>

    {{-- ==================== MODAL: NEW PRODUCT ==================== --}}
    <div class="edit-modal" id="new-product-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nuevo Producto</h3>
                <button class="modal-close" id="close-new-product-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="new-product-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
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
                    <div class="form-group">
                        <label for="new-image">Imagen del Producto</label>
                        <input type="file" id="new-image" name="image" accept="image/*">
                    </div>
                    {{-- Multiple images rendered as a carousel on the product page --}}
                    <div class="form-group">
                        <label for="new-images">Imágenes adicionales (carrusel)</label>
                        <input type="file" id="new-images" name="images[]" accept="image/*" multiple>
                        <small class="form-text text-muted">
                            Opcional. Varias imágenes se mostrarán en un carrusel en la ficha del producto.
                        </small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new-parent-category">Categoría *</label>
                            <select id="new-parent-category" required>
                                <option value="">Seleccionar categoría</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new-subcategory">Subcategoría <span class="text-muted">(opcional)</span></label>
                            <select id="new-subcategory" aria-describedby="new-subcategory-hint">
                                <option value="">Sin subcategoría</option>
                            </select>
                            <small id="new-subcategory-hint" class="form-text text-muted">Si no eliges subcategoría, el producto queda en la categoría padre.</small>
                            <input type="hidden" id="new-category" name="category_id" value="">
                        </div>
                        <div class="form-group">
                            <label for="new-provider">Proveedor *</label>
                            <select id="new-provider" name="supplier_id" required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach(\App\Models\Supplier::where('status', 'active')->get() as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
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
    <div class="edit-modal" id="edit-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Producto</h3>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-product-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- Spoofs PUT method since HTML forms only support GET/POST --}}
                    <input type="hidden" name="_method" value="PUT">
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
                    <div class="form-group">
                        <label for="edit-image">Imagen del Producto</label>
                        <input type="file" id="edit-image" name="image" accept="image/*">
                        <div id="current-image-preview" style="margin-top: 10px;"></div>
                    </div>
                    {{-- Uploading new images replaces the existing carousel set --}}
                    <div class="form-group">
                        <label for="edit-images">Imágenes adicionales (carrusel)</label>
                        <input type="file" id="edit-images" name="images[]" accept="image/*" multiple>
                        <small class="form-text text-muted">Opcional. Al subir nuevas, reemplazan las actuales del carrusel.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-parent-category">Categoría padre *</label>
                            <select id="edit-parent-category" required>
                                <option value="">Seleccionar categoría padre</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-subcategory">Subcategoría <span class="text-muted">(opcional)</span></label>
                            <select id="edit-subcategory">
                                <option value="">Sin subcategoría</option>
                            </select>
                            <input type="hidden" id="edit-category" name="category_id" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-provider">Proveedor *</label>
                            <select id="edit-provider" name="supplier_id" required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach(\App\Models\Supplier::where('status', 'active')->get() as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-edit">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-edit">Guardar Cambios</button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: EXPORT ==================== --}}
    <div class="edit-modal" id="export-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Exportar Productos</h3>
                <button class="modal-close" id="close-export-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="export-options">

                    <div class="export-option">
                        <div class="export-icon"><i class="fas fa-file-code"></i></div>
                        <div class="export-info">
                            <h4>XML</h4>
                            <p>Formato estructurado para intercambio de datos</p>
                        </div>
                        <a href="{{ route('products.export', 'xml') }}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Exportar XML
                        </a>
                    </div>

                    <div class="export-option">
                        <div class="export-icon"><i class="fas fa-file-csv"></i></div>
                        <div class="export-info">
                            <h4>CSV</h4>
                            <p>Formato de hoja de cálculo compatible con Excel</p>
                        </div>
                        <a href="{{ route('products.export', 'csv') }}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                    </div>

                    <div class="export-option">
                        <div class="export-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="export-info">
                            <h4>JSON</h4>
                            <p>Formato ligero para aplicaciones web</p>
                        </div>
                        <a href="{{ route('products.export', 'json') }}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Exportar JSON
                        </a>
                    </div>

                    <div class="export-option">
                        <div class="export-icon"><i class="fas fa-file-pdf"></i></div>
                        <div class="export-info">
                            <h4>PDF</h4>
                            <p>Documento profesional para impresión</p>
                        </div>
                        <a href="{{ route('products.export', 'pdf') }}" class="btn btn-primary">
                            <i class="fas fa-download"></i> Exportar PDF
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: IMPORT ==================== --}}
    <div class="edit-modal" id="import-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-file-upload"></i> Importar Productos</h3>
                <button class="modal-close" id="close-import-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('products.import') }}" method="POST"
                      enctype="multipart/form-data" id="import-form">
                    @csrf

                    {{-- File drop zone; visibility toggled by inventory.js --}}
                    <div class="form-group">
                        <label for="import_file" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Arrastra tu archivo aquí o haz clic para seleccionar</span>
                        </label>
                        <input type="file" id="import_file" name="import_file"
                               accept=".xml,.csv,.json,.txt" required style="display:none;">
                        <div id="file-info" class="file-info hidden">
                            <div class="file-info-content">
                                <i class="fas fa-file" id="file-icon"></i>
                                <div class="file-details">
                                    <strong id="file-name"></strong>
                                    <span id="file-format" class="file-format-badge"></span>
                                    <small id="file-size"></small>
                                </div>
                                <button type="button" class="btn-remove-file" id="remove-file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Shown after a file is selected; populated by inventory.js --}}
                    <div id="format-detected" class="alert alert-success hidden">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Formato detectado:</strong> <span id="detected-format-text"></span>
                            <small class="format-help-text"></small>
                        </div>
                    </div>

                    {{-- Supported file formats guide --}}
                    <div class="form-group">
                        <div class="alert alert-info">
                            <div class="alert-header">
                                <i class="fas fa-info-circle"></i>
                                <strong>Formatos soportados</strong>
                            </div>
                            <div class="formats-guide">
                                <div class="format-item">
                                    <i class="fas fa-file-code format-icon xml"></i>
                                    <div>
                                        <strong>XML</strong>
                                        <small>Formato estructurado con etiquetas</small>
                                    </div>
                                </div>
                                <div class="format-item">
                                    <i class="fas fa-file-csv format-icon csv"></i>
                                    <div>
                                        <strong>CSV</strong>
                                        <small>Compatible con Excel y hojas de cálculo</small>
                                    </div>
                                </div>
                                <div class="format-item">
                                    <i class="fas fa-file-alt format-icon json"></i>
                                    <div>
                                        <strong>JSON</strong>
                                        <small>Formato ligero para aplicaciones web</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Required fields reference for the import file --}}
                    <div class="form-group">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Campos requeridos en el archivo:</strong>
                                <ul class="required-fields-list">
                                    <li><code>nombre</code> - Nombre del producto</li>
                                    <li><code>categoria</code> - Nombre de la categoría (debe existir)</li>
                                    <li><code>proveedor</code> - Nombre del proveedor (debe existir)</li>
                                    <li><code>precio_compra</code> - Precio de compra</li>
                                    <li><code>precio_venta</code> - Precio de venta</li>
                                    <li><code>stock_actual</code> - Cantidad en stock</li>
                                    <li><code>stock_minimo</code> - Stock mínimo</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-import">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                {{-- Enabled by inventory.js once a valid file is selected --}}
                <button type="button" class="btn btn-primary" id="confirm-import" disabled>
                    <i class="fas fa-upload"></i> <span>Importar Productos</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL: VIEW PRODUCT DETAILS ==================== --}}
    {{-- Body content injected dynamically by inventory.js --}}
    <div class="edit-modal" id="view-product-modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-auto-size">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles del Producto</h3>
                <button class="modal-close" id="close-view-product-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="view-product-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-view-product">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Árbol padre → hijos para filtros y modales (inventory.js) --}}
    <script>
        window.inventoryCategoryTree = @json($subcategoriesByParent ?? []);
        window.inventoryBrands = @json($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name]) ?? []);
    </script>

    {{-- Scripts: SweetAlert2 loaded before inventory.js --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/admin/inventory/inventory.js'])

</body>
</html>