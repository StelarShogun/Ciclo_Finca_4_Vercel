@extends('client.layouts.app')

@section('title', 'Catálogo - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<div class="catalog-container">
    <div class="catalog-header">
        <div class="container">
            <h1 class="catalog-title">Catálogo de Productos</h1>
            <p class="catalog-subtitle">Explora nuestra amplia selección de productos</p>
        </div>
    </div>

    <div class="container">
        <div class="catalog-layout">
            {{-- Mobile: filter toggle button --}}
            <button class="btn btn-outline-secondary catalog-filter-toggle" id="catalog-filter-toggle" type="button"
                    aria-expanded="false" aria-controls="catalog-sidebar"
                    style="display:none; align-items:center; gap:8px; width:100%; margin-bottom:12px;">
                <i class="fas fa-filter"></i>
                <span>Mostrar filtros</span>
                <i class="fas fa-chevron-down" style="margin-left:auto; font-size:0.8rem; transition:transform 0.25s ease;"></i>
            </button>

            <aside class="catalog-sidebar" id="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>

                    {{-- GET form keeps active filters in the URL; category_id comes from the horizontal bar --}}
                    <form method="GET" action="{{ route('clients.catalog') }}" id="filter-form">
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
                            <button type="submit" class="btn btn-primary btn-block" id="filter-submit-btn">
                                <i class="fas fa-search"></i>
                                Aplicar Filtros
                            </button>
                            {{-- Navigating to the base URL effectively clears all filters --}}
                            <a href="{{ route('clients.catalog') }}" class="btn btn-secondary btn-block">
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
                    <a href="{{ route('clients.catalog', $catalogParams) }}"
                       class="cat-pill {{ !request('category_id') ? 'active' : '' }}">
                        Todas las categorías
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $cat->category_id])) }}"
                           class="cat-pill {{ (request('category_id') == $cat->category_id || optional($selectedCategory)->parent_category_id == $cat->category_id) ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>

                {{-- Subcategory row appears when a parent or child category is active --}}
                @if($parentCategoryForSubcats)
                    <div class="catalog-subcats-row">
                        <span class="catalog-subcats-label">En {{ $parentCategoryForSubcats->name }}:</span>
                        <div class="catalog-subcats-pills">
                            <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $parentCategoryForSubcats->category_id])) }}"
                               class="cat-pill cat-pill-sub {{ request('category_id') == $parentCategoryForSubcats->category_id ? 'active' : '' }}">
                                Todas
                            </a>
                            @foreach($subcategories as $sub)
                                <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $sub->category_id])) }}"
                                   class="cat-pill cat-pill-sub {{ request('category_id') == $sub->category_id ? 'active' : '' }}">
                                    {{ $sub->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($catalogSpotlight->isNotEmpty())
                    <section class="catalog-spotlight-section" aria-labelledby="catalog-spotlight-heading">
                        <div class="catalog-spotlight-inner">
                            <header class="catalog-spotlight-header">
                                <h2 id="catalog-spotlight-heading" class="catalog-spotlight-title">Destacados y novedades</h2>
                                <p class="catalog-spotlight-subtitle">Productos recomendados y recién incorporados al catálogo.</p>
                            </header>
                            <div class="catalog-spotlight-grid" role="list">
                                @foreach($catalogSpotlight as $row)
                                    @php
                                        /** @var \App\Models\Product $product */
                                        $product = $row['product'];
                                        $spotlight = $row['spotlight'];
                                        $catLabel = $product->clientCatalogStockLabel();
                                        $canBuy = $product->isPurchasableByClient();
                                    @endphp
                                    <article class="product-card product-card--catalog-spotlight" role="listitem">
                                        <div class="product-image">
                                            <span @class([
                                                'spotlight-badge',
                                                'spotlight-badge--featured' => $spotlight === 'featured',
                                                'spotlight-badge--novelty' => $spotlight === 'novelty',
                                            ])>
                                                {{ $spotlight === 'featured' ? 'Destacado' : 'Novedad' }}
                                            </span>
                                            <a href="{{ $product->clientProductUrl() }}">
                                                <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                                     alt="{{ $product->name }}"
                                                     data-fallback-src="{{ asset('favicon.svg') }}"
                                                     onerror="this.src=this.dataset.fallbackSrc;">
                                            </a>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                            <h3 class="product-name">
                                                <a href="{{ $product->clientProductUrl() }}">{{ $product->name }}</a>
                                            </h3>
                                            <p @class([
                                                'product-availability-text',
                                                'is-available' => $catLabel === 'Disponible',
                                                'is-low' => $catLabel === 'Quedan pocas unidades',
                                                'is-out' => $catLabel === 'Agotado',
                                                'is-na' => $catLabel === 'No disponible',
                                            ])>{{ $catLabel }}</p>
                                            @if($canBuy)
                                                <p class="product-stock-qty">{{ number_format((int) ($product->stock_current ?? 0), 0, ',', '.') }} unidades disponibles</p>
                                            @endif
                                            <div class="product-footer product-footer--spotlight-compact">
                                                <div class="product-price-bar">
                                                    <span class="product-price-value">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="product-actions">
                                                    <a href="{{ $product->clientProductUrl() }}" class="btn-product btn-ver-detalles">
                                                        <i class="fas fa-arrow-right"></i>
                                                        Ver detalles
                                                    </a>
                                                    @if($canBuy)
                                                        @auth('clients')
                                                            <button type="button" class="btn-product btn-agregar add-to-cart-btn"
                                                                    data-purchasable="1"
                                                                    data-product-id="{{ $product->product_id }}"
                                                                    data-product-name="{{ $product->name }}"
                                                                    data-product-price="{{ $product->sale_price }}"
                                                                    data-product-stock="{{ $product->stock_current }}">
                                                                <i class="fas fa-cart-plus"></i>
                                                                Agregar
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn-product btn-agregar guest-add-btn"
                                                                    data-purchasable="1"
                                                                    data-product-stock="{{ $product->stock_current }}">
                                                                <i class="fas fa-cart-plus"></i>
                                                                Agregar
                                                            </button>
                                                        @endauth
                                                    @else
                                                        <button type="button" class="btn-product btn-agotado" disabled>
                                                            <i class="fas fa-ban"></i>
                                                            {{ $catLabel === 'Agotado' ? 'Agotado' : 'No disponible' }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </section>
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
                                @php $catLabel = $product->clientCatalogStockLabel(); $canBuy = $product->isPurchasableByClient(); @endphp
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="{{ $product->clientProductUrl() }}">
                                            {{-- Fallback to favicon if product image is missing --}}
                                            <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                                 alt="{{ $product->name }}"
                                                 data-fallback-src="{{ asset('favicon.svg') }}"
                                                 onerror="this.src=this.dataset.fallbackSrc;">
                                        </a>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                        <h3 class="product-name">
                                            <a href="{{ $product->clientProductUrl() }}">
                                                {{ $product->name }}
                                            </a>
                                        </h3>
                                        <p @class([
                                            'product-availability-text',
                                            'is-available' => $catLabel === 'Disponible',
                                            'is-low' => $catLabel === 'Quedan pocas unidades',
                                            'is-out' => $catLabel === 'Agotado',
                                            'is-na' => $catLabel === 'No disponible',
                                        ])>{{ $catLabel }}</p>
                                        @if($canBuy)
                                            <p class="product-stock-qty">{{ number_format((int) ($product->stock_current ?? 0), 0, ',', '.') }} unidades disponibles</p>
                                        @endif
                                        @if($product->description)
                                            <p class="product-description">{{ Str::limit($product->description, 100) }}</p>
                                        @endif
                                        <div class="product-footer">
                                            <div class="product-price-bar">
                                                <span class="product-price-value">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="product-actions">
                                                <a href="{{ $product->clientProductUrl() }}" class="btn-product btn-ver-detalles">
                                                    <i class="fas fa-arrow-right"></i>
                                                    Ver detalles
                                                </a>
                                                @if($canBuy)
                                                    @auth('clients')
                                                        <button type="button" class="btn-product btn-agregar add-to-cart-btn"
                                                                data-purchasable="1"
                                                                data-product-id="{{ $product->product_id }}"
                                                                data-product-name="{{ $product->name }}"
                                                                data-product-price="{{ $product->sale_price }}"
                                                                data-product-stock="{{ $product->stock_current }}">
                                                            <i class="fas fa-cart-plus"></i>
                                                            Agregar
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn-product btn-agregar guest-add-btn"
                                                                data-purchasable="1"
                                                                data-product-stock="{{ $product->stock_current }}">
                                                            <i class="fas fa-cart-plus"></i>
                                                            Agregar
                                                        </button>
                                                    @endauth
                                                @else
                                                    <button type="button" class="btn-product btn-agotado" disabled>
                                                        <i class="fas fa-ban"></i>
                                                        {{ $catLabel === 'Agotado' ? 'Agotado' : 'No disponible' }}
                                                    </button>
                                                @endif
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
                            <a href="{{ route('clients.catalog') }}" class="btn btn-primary">
                                Ver Todos los Productos
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>

{{-- Quantity selector modal, populated by JS when the user clicks "Agregar" --}}
<div class="modal" id="add-to-cart-modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Agregar al Carrito</h3>
            <button class="modal-close" id="close-add-to-cart-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="product-preview" id="product-preview">
                <img id="preview-image" src="" alt="">
                <div class="preview-info">
                    <h4 id="preview-name"></h4>
                    <p class="preview-price" id="preview-price"></p>
                    <p class="preview-stock" id="preview-stock"></p>
                </div>
            </div>
            <div class="form-group">
                <label for="cart-quantity">Cantidad:</label>
                <input type="number" id="cart-quantity" class="form-control" min="1" value="1">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-add-to-cart">Cancelar</button>
            <button class="btn btn-primary" id="confirm-add-to-cart">Agregar al Carrito</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-page.js'])
@endpush