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
        @php
            $activeCategoryId = (int) (request('category_id') ?: 0);
        @endphp

        <nav class="catalog-category-sidebar"
             id="catalog-category-sidebar"
             aria-label="Navegación por categorías"
             data-catalog-sidebar-hover-root
             data-close-delay-ms="150">
            <div class="catalog-category-sidebar-head">
                <button id="catalog-category-sidebar-toggle"
                        class="catalog-category-sidebar-brand"
                        type="button"
                        aria-expanded="false"
                        aria-label="Expandir menú de categorías">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <span class="catalog-category-sidebar-head-label">Categorías</span>
            </div>
            <a href="{{ route('clients.catalog', $catalogParams) }}"
               class="catalog-category-sidebar-all catalog-category-item {{ $activeCategoryId === 0 ? 'is-active' : '' }}"
               data-category-id="0"
               title="Todos los productos"
               aria-label="Todos los productos">
                <span class="catalog-category-item-icon" aria-hidden="true"><i class="fas fa-th-large"></i></span>
                <span class="catalog-category-item-label"></span>
            </a>
            @foreach($categories as $cat)
                @php
                    $isParentActive = $activeCategoryId === (int) $cat->category_id;
                    $isChildActive = $cat->childCategories->contains('category_id', $activeCategoryId);
                    $navRow = collect($catalogCategoryNav)->firstWhere('id', (int) $cat->category_id);
                    $iconClass = $navRow['icon'] ?? 'fas fa-layer-group';
                @endphp
                <div class="catalog-category-sidebar-item {{ ($isParentActive || $isChildActive) ? 'has-active' : '' }}"
                     data-parent-id="{{ $cat->category_id }}"
                     data-has-children="{{ $cat->childCategories->isNotEmpty() ? '1' : '0' }}">
                    <div class="catalog-category-sidebar-item-row">
                        <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $cat->category_id])) }}"
                           class="catalog-category-item catalog-category-sidebar-link {{ $isParentActive && ! $isChildActive ? 'is-active' : '' }}"
                           data-category-id="{{ $cat->category_id }}"
                           title="{{ $cat->name }}"
                           aria-label="{{ $cat->name }}">
                            <span class="catalog-category-item-icon" aria-hidden="true"><i class="{{ $iconClass }}"></i></span>
                            <span class="catalog-category-item-label">{{ $cat->name }}</span>
                        </a>
                        @if($cat->childCategories->isNotEmpty())
                            <button type="button"
                                    class="catalog-category-mobile-expand"
                                    aria-expanded="false"
                                    aria-controls="catalog-sidebar-subs-{{ $cat->category_id }}"
                                    aria-label="Mostrar subcategorías de {{ $cat->name }}">
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                    @if($cat->childCategories->isNotEmpty())
                        <div class="catalog-category-flyout"
                             id="catalog-sidebar-subs-{{ $cat->category_id }}"
                             role="region"
                             aria-label="Subcategorías de {{ $cat->name }}"
                             aria-hidden="true">
                            <div class="catalog-category-flyout-title">{{ $cat->name }}</div>
                            <ul class="catalog-category-flyout-list">
                                @foreach($cat->childCategories as $ch)
                                    <li>
                                        <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $ch->category_id])) }}"
                                           class="catalog-category-subitem {{ $activeCategoryId === (int) $ch->category_id ? 'is-active' : '' }}"
                                           data-category-id="{{ $ch->category_id }}">{{ $ch->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
        <div class="catalog-layout">
            {{-- Mobile: filter toggle (solo filtros; categorías en panel principal) --}}
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

                    {{-- GET form: category_id se conserva por URL al filtrar desde el menú de categorías --}}
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
                <div class="catalog-category-toolbar">
                    <button type="button"
                            id="catalog-category-trigger"
                            class="btn btn-primary catalog-category-trigger"
                            aria-expanded="false"
                            aria-haspopup="dialog"
                            aria-controls="catalog-category-panel">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                        Categorías
                    </button>
                </div>

                <div id="catalog-category-backdrop" class="catalog-category-backdrop" aria-hidden="true" data-catalog-category-backdrop></div>

                <div id="catalog-category-panel"
                     class="catalog-category-panel"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="catalog-category-panel-title"
                     aria-hidden="true"
                     data-active-category-id="{{ $activeCategoryId ?: '' }}"
                     data-close-delay-ms="150">
                    <div class="catalog-category-panel-inner">
                        <div class="catalog-category-panel-header">
                            <h2 id="catalog-category-panel-title" class="catalog-category-panel-title">Categorías</h2>
                            <button type="button" class="catalog-category-close" id="catalog-category-close" aria-label="Cerrar menú de categorías">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="catalog-category-panel-body" data-catalog-panel-hover-root>
                            <div class="catalog-category-col catalog-category-col--parents">
                                <a href="{{ route('clients.catalog', $catalogParams) }}"
                                   class="catalog-category-all-link {{ $activeCategoryId === 0 ? 'is-active-route' : '' }}">
                                    Todas las categorías
                                </a>
                                @foreach($categories as $cat)
                                    @php
                                        $navRow = collect($catalogCategoryNav)->firstWhere('id', (int) $cat->category_id);
                                        $iconClass = $navRow['icon'] ?? 'fas fa-layer-group';
                                        $isParentActive = $activeCategoryId === (int) $cat->category_id;
                                        $isChildActive = $cat->childCategories->contains('category_id', $activeCategoryId);
                                    @endphp
                                    <div class="catalog-category-parent-row {{ ($isParentActive || $isChildActive) ? 'has-active' : '' }}"
                                         data-parent-id="{{ $cat->category_id }}"
                                         data-has-children="{{ $cat->childCategories->isNotEmpty() ? '1' : '0' }}">
                                        <div class="catalog-category-parent-row-main">
                                            <span class="catalog-category-parent-icon" aria-hidden="true"><i class="{{ $iconClass }}"></i></span>
                                            <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $cat->category_id])) }}"
                                               class="catalog-category-parent-link {{ $isParentActive && ! $isChildActive ? 'is-active-route' : '' }}"
                                               data-category-id="{{ $cat->category_id }}">{{ $cat->name }}</a>
                                            @if($cat->childCategories->isNotEmpty())
                                                <button type="button"
                                                        class="catalog-category-panel-mobile-expand"
                                                        aria-expanded="false"
                                                        aria-controls="catalog-panel-subs-{{ $cat->category_id }}"
                                                        aria-label="Expandir subcategorías de {{ $cat->name }}">
                                                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </div>
                                        @if($cat->childCategories->isNotEmpty())
                                            <div class="catalog-category-parent-mobile-subs" id="catalog-panel-subs-{{ $cat->category_id }}" hidden>
                                                <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $cat->category_id])) }}"
                                                   class="catalog-category-ver-todo catalog-category-ver-todo--inline">Ver todo en {{ $cat->name }}</a>
                                                <ul class="catalog-category-mobile-sub-list">
                                                    @foreach($cat->childCategories as $ch)
                                                        <li>
                                                            <a href="{{ route('clients.catalog', array_merge($catalogParams, ['category_id' => $ch->category_id])) }}"
                                                               class="catalog-category-sub-link {{ $activeCategoryId === (int) $ch->category_id ? 'is-active' : '' }}">{{ $ch->name }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="catalog-category-col catalog-category-col--subs" id="catalog-category-subcolumn" aria-live="polite">
                                <p class="catalog-category-placeholder">Pasá el cursor sobre una categoría para ver subcategorías.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <script type="application/json" id="catalog-category-tree-data">@json($catalogCategoryNav)</script>

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
                                        $isFavorite = $favoriteProductIds->contains((int) $product->product_id);
                                    @endphp
                                    <article class="product-card product-card--catalog-spotlight" role="listitem">
                                        <div class="product-image">
                                            <button type="button"
                                                    class="product-favorite-btn {{ $isFavorite ? 'is-active' : '' }}"
                                                    data-product-favorite-btn
                                                    data-product-id="{{ $product->product_id }}"
                                                    aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                                                    aria-label="{{ $isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                                                <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                                            </button>
                                            <span @class([
                                                'spotlight-badge',
                                                'spotlight-badge--featured' => $spotlight === 'featured',
                                                'spotlight-badge--novelty' => $spotlight === 'novelty',
                                            ])>
                                                {{ $spotlight === 'featured' ? 'Destacado' : 'Novedad' }}
                                            </span>
                                            <a href="{{ $product->clientProductUrl() }}">
                                                @php $spotlightImgUrl = $product->getFirstMediaUrl('main_image') ?: asset('assets/images/products/' . ($product->image ?? 'default.png')); @endphp
                                                <img src="{{ $spotlightImgUrl }}"
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

                    @if($products->total() > 0)
                        <div class="products-grid">
                            @foreach($products as $product)
                                @php
                                    $catLabel = $product->clientCatalogStockLabel();
                                    $canBuy = $product->isPurchasableByClient();
                                    $isFavorite = $favoriteProductIds->contains((int) $product->product_id);
                                @endphp
                                <div class="product-card">
                                    <div class="product-image">
                                        <button type="button"
                                                class="product-favorite-btn {{ $isFavorite ? 'is-active' : '' }}"
                                                data-product-favorite-btn
                                                data-product-id="{{ $product->product_id }}"
                                                aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                                                aria-label="{{ $isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                                            <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                                        </button>
                                        @php $cardImgUrl = $product->getFirstMediaUrl('main_image') ?: asset('assets/images/products/' . ($product->image ?? 'default.png')); @endphp
                                        <a href="{{ $product->clientProductUrl() }}">
                                            {{-- Fallback to favicon if product image is missing --}}
                                            <img src="{{ $cardImgUrl }}"
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
                        @if($emptyCategoryNoProducts)
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <h3>No hay productos disponibles en esta categoría.</h3>
                                <p>Probá otra categoría o limpiá los filtros.</p>
                                <a href="{{ route('clients.catalog', $catalogParams) }}" class="btn btn-primary">
                                    Ver todas las categorías
                                </a>
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
    @auth('clients')
        @php
            $favoriteToggleUrl = \Illuminate\Support\Facades\Route::has('clients.favorites.toggle')
                ? route('clients.favorites.toggle')
                : url('/favorites/toggle');
        @endphp
        <script>
            window.catalogFavoriteConfig = {
                toggleUrl: @json($favoriteToggleUrl),
            };
        </script>
    @else
        <script>
            window.catalogFavoriteConfig = {
                loginUrl: @json(route('login.show')),
            };
        </script>
    @endauth
    @vite(['resources/js/client/clients-page.js'])
@endpush
