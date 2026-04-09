<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catálogo de Productos - Ciclo Finca 4 Admin</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Admin layout scaffold + client catalog styles --}}
    @vite([
        'resources/css/admin/products/inventory.css',
        'resources/css/client/clients-page.css',
    ])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Remove cart-specific actions since admin only views the catalog */
        .btn-agregar { display: none !important; }
    </style>
</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main">

        <div class="catalog-container">
            <div class="catalog-header">
                <div class="container">
                    <h1 class="catalog-title">Catálogo de Productos</h1>
                    <p class="catalog-subtitle">Vista del catálogo tal como lo ven los clientes</p>
                </div>
            </div>

            <div class="container">
                <div class="catalog-layout">
                    <aside class="catalog-sidebar">
                        <div class="filters-card">
                            <h3 class="filters-title">
                                <i class="fas fa-filter"></i>
                                Filtros
                            </h3>

                            <form method="GET" action="{{ route('admin.catalog') }}" id="filter-form">
                                @if(request('category_id'))
                                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                                @endif
                                @if($errors->has('price_range'))
                                    <div class="alert alert-danger filter-error" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $errors->first('price_range') }}
                                    </div>
                                @endif
                                <div class="filter-group">
                                    <label for="search">Buscar</label>
                                    <input type="text"
                                           id="search"
                                           name="search"
                                           class="form-control"
                                           placeholder="Nombre o descripción..."
                                           value="{{ request('search') }}">
                                </div>

                                <div class="filter-group">
                                    <label>Rango de Precio</label>
                                    <div class="price-range">
                                        <input type="number"
                                               id="min_price"
                                               name="min_price"
                                               class="form-control"
                                               placeholder="Mínimo"
                                               value="{{ old('min_price', request('min_price')) }}">
                                        <span class="price-separator">-</span>
                                        <input type="number"
                                               id="max_price"
                                               name="max_price"
                                               class="form-control"
                                               placeholder="Máximo"
                                               value="{{ old('max_price', request('max_price')) }}">
                                    </div>
                                </div>

                                <div class="filter-group">
                                    <label for="sort">Ordenar por</label>
                                    <select id="sort" name="sort" class="form-control">
                                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Más recientes</option>
                                        <option value="price"      {{ request('sort') == 'price'      ? 'selected' : '' }}>Precio</option>
                                        <option value="name"       {{ request('sort') == 'name'       ? 'selected' : '' }}>Nombre</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="direction">Dirección</label>
                                    <select id="direction" name="direction" class="form-control">
                                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Descendente</option>
                                        <option value="asc"  {{ request('direction') == 'asc'  ? 'selected' : '' }}>Ascendente</option>
                                    </select>
                                </div>

                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                        Aplicar Filtros
                                    </button>
                                    <a href="{{ route('admin.catalog') }}" class="btn btn-secondary btn-block">
                                        <i class="fas fa-redo"></i>
                                        Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </aside>

                    <main class="catalog-content">
                        @php $catalogParams = request()->except('category_id', 'page'); @endphp
                        <div class="catalog-cats-horizontal">
                            <a href="{{ route('admin.catalog', $catalogParams) }}"
                               class="cat-pill {{ !request('category_id') ? 'active' : '' }}">
                                Todas las categorías
                            </a>
                            @foreach($categories as $cat)
                                <a href="{{ route('admin.catalog', array_merge($catalogParams, ['category_id' => $cat->category_id])) }}"
                                   class="cat-pill {{ (request('category_id') == $cat->category_id || optional($selectedCategory)->parent_category_id == $cat->category_id) ? 'active' : '' }}">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                        </div>

                        @if($parentCategoryForSubcats)
                            <div class="catalog-subcats-row">
                                <span class="catalog-subcats-label">En {{ $parentCategoryForSubcats->name }}:</span>
                                <div class="catalog-subcats-pills">
                                    <a href="{{ route('admin.catalog', array_merge($catalogParams, ['category_id' => $parentCategoryForSubcats->category_id])) }}"
                                       class="cat-pill cat-pill-sub {{ request('category_id') == $parentCategoryForSubcats->category_id ? 'active' : '' }}">
                                        Todas
                                    </a>
                                    @foreach($subcategories as $sub)
                                        <a href="{{ route('admin.catalog', array_merge($catalogParams, ['category_id' => $sub->category_id])) }}"
                                           class="cat-pill cat-pill-sub {{ request('category_id') == $sub->category_id ? 'active' : '' }}">
                                            {{ $sub->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="catalog-results">
                            <div class="results-header">
                                @if($selectedCategory)
                                    <p class="catalog-breadcrumb">Catálogo / {{ $selectedCategory->name }}</p>
                                @else
                                    <p class="catalog-breadcrumb">Catálogo</p>
                                @endif
                                <p class="results-count">
                                    Mostrando {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} de {{ $products->total() }} productos
                                </p>
                            </div>

                            @if($products->count() > 0)
                                <div class="products-grid">
                                    @foreach($products as $product)
                                        <div class="product-card">
                                            <div class="product-image">
                                                <a href="{{ route('clients.product', $product->product_id) }}" target="_blank">
                                                    <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                                         alt="{{ $product->name }}"
                                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                                         onerror="this.src=this.dataset.fallbackSrc;">
                                                </a>
                                                @if($product->stock_current <= 10)
                                                    <span class="product-badge stock-low">Stock Bajo</span>
                                                @endif
                                            </div>
                                            <div class="product-info">
                                                <div class="product-category">{{ $product->category->name ?? 'Sin categoría' }}</div>
                                                <h3 class="product-name">
                                                    <a href="{{ route('clients.product', $product->product_id) }}" target="_blank">
                                                        {{ $product->name }}
                                                    </a>
                                                </h3>
                                                @if($product->description)
                                                    <p class="product-description">{{ Str::limit($product->description, 100) }}</p>
                                                @endif
                                                <div class="product-footer">
                                                    <div class="product-price-bar">
                                                        <span class="product-price-value">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="product-actions">
                                                        <a href="{{ route('clients.product', $product->product_id) }}" target="_blank" class="btn-product btn-ver-detalles">
                                                            <i class="fas fa-arrow-right"></i>
                                                            Ver detalles
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="pagination-wrapper">
                                    <x-pagination :paginator="$products" label="productos" />
                                </div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h3>No se encontraron productos</h3>
                                    <p>Intenta ajustar tus filtros de búsqueda</p>
                                    <a href="{{ route('admin.catalog') }}" class="btn btn-primary">
                                        Ver Todos los Productos
                                    </a>
                                </div>
                            @endif
                        </div>
                    </main>
                </div>
            </div>
        </div>

    </main>

</body>
</html>
