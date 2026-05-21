@extends('client.layouts.app')

@section('title', 'Catálogo - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<section class="catalog-shell">
    <header class="catalog-hero">
        <div class="catalog-hero-content">
            <span class="catalog-kicker">Ciclo Finca 4</span>
            <h1>Catálogo de Productos</h1>
            <p class="catalog-subtitle">Explora nuestra amplia selección de productos</p>
            <div class="catalog-hero-stats" aria-label="Resumen del catálogo">
                <span><strong>{{ number_format((int) $products->total(), 0, ',', '.') }}</strong> productos</span>
                <span><strong>{{ $categories->count() }}</strong> categorías</span>
            </div>
        </div>
    </header>

    <div class="catalog-container">
        @php
            $activeCategoryId = (int) (request('category_id') ?: 0);
        @endphp

        <div class="catalog-sidebar-stack">
        <div class="catalog-rail-wrap">
            <nav class="category-rail catalog-category-sidebar"
                 id="catalog-category-sidebar"
                 aria-label="Categorías del catálogo"
                 data-catalog-sidebar-hover-root
                 data-close-delay-ms="150">
                <div class="category-rail-header">
                    <button id="catalog-category-sidebar-toggle"
                            class="category-rail-toggle catalog-category-sidebar-brand"
                            type="button"
                            aria-expanded="false"
                            aria-label="Expandir menú de categorías">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                    <span class="category-rail-title">Categorías</span>
                </div>
                <div class="category-rail-scroll">
            <a href="{{ route('clients.catalog', $catalogParams) }}"
               class="catalog-category-sidebar-all catalog-category-item {{ $activeCategoryId === 0 ? 'is-active' : '' }}"
               data-category-id="0"
               title="Todos los productos"
               aria-label="Todos los productos">
                <span class="catalog-category-item-icon" aria-hidden="true"><i class="fas fa-list"></i></span>
                <span class="catalog-category-item-label"></span>
            </a>
            @foreach($categories as $cat)
                @php
                    $isParentActive = $activeCategoryId === (int) $cat->category_id;
                    $isChildActive = $cat->childCategories->contains('category_id', $activeCategoryId);
                    $navRow = collect($catalogCategoryNav)->firstWhere('id', (int) $cat->category_id);
                    $iconClass = $navRow['icon'] ?? 'fas fa-bicycle';
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
                </div>
            </nav>
        </div>

        <aside class="catalog-filters catalog-sidebar" id="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        <span class="filters-title-text">Filtros</span>
                    </h3>

                    {{-- GET form: category_id se conserva por URL al filtrar desde el menú de categorías --}}
                    <form method="GET" action="{{ route('clients.catalog') }}" id="filter-form" autocomplete="off">
                        {{-- Búsqueda en el header: sin JS el GET conserva `search`; con JS se sincroniza desde #catalog-nav-search. --}}
                        <input type="hidden" name="search" id="catalog-filter-search-fallback" value="{{ old('search', request('search', '')) }}">
                        <input type="hidden" name="page" id="catalog-list-page" value="{{ max(1, (int) request('page', 1)) }}">
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
                            <label>Rango de Precio</label>
                            <div class="price-range">
                                <input type="number"
                                       id="min_price"
                                       name="min_price"
                                       class="form-control"
                                       placeholder="Mínimo"
                                       min="0"
                                       step="1"
                                       value="{{ old('min_price', request('min_price')) }}">
                                <span class="price-separator">-</span>
                                <input type="number"
                                       id="max_price"
                                       name="max_price"
                                       class="form-control"
                                       placeholder="Máximo"
                                       min="0"
                                       step="1"
                                       value="{{ old('max_price', request('max_price')) }}">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="brand_id">
                                <i class="fas fa-tag" aria-hidden="true"></i>
                                Marca
                            </label>
                            <select id="brand_id" name="brand_id" class="form-control">
                                <option value="">Todas las marcas</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}"
                                        {{ (string) request('brand_id') === (string) $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-block" id="filter-submit-btn">
                                <i class="fas fa-sliders" aria-hidden="true"></i>
                                <span class="btn-text">Aplicar Filtros</span>
                            </button>
                            {{-- Navigating to the base URL effectively clears all filters --}}
                            <a href="{{ route('clients.catalog') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo" aria-hidden="true"></i>
                                <span class="btn-text">Limpiar</span>
                            </a>
                        </div>
                    </form>
                </div>
            </aside>
        </div>

            <main class="catalog-main catalog-content">
                {{-- Mobile / tablet: filtros colapsables (JS + .open en #catalog-sidebar) --}}
                <button class="btn btn-outline-secondary catalog-filter-toggle" id="catalog-filter-toggle" type="button"
                        aria-expanded="false" aria-controls="catalog-sidebar">
                    <i class="fas fa-filter"></i>
                    <span>Mostrar filtros</span>
                    <i class="fas fa-chevron-down catalog-filter-toggle-caret" aria-hidden="true"></i>
                </button>

                <div class="catalog-category-toolbar">
                    <button type="button"
                            id="catalog-category-trigger"
                            class="btn btn-primary catalog-category-trigger"
                            aria-expanded="false"
                            aria-haspopup="dialog"
                            aria-controls="catalog-category-panel">
                        <i class="fas fa-bicycle" aria-hidden="true"></i>
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
                                        $iconClass = $navRow['icon'] ?? 'fas fa-bicycle';
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

                @php
                    // CF4-XXX: spotlight carousel rules — hide when any catalog filter is active
                    // (search text, category, brand, price range) and only show on page 1.
                    $hasActiveCatalogFilters = request()->filled('search')
                        || request()->filled('category_id')
                        || request()->filled('brand_id')
                        || request()->filled('min_price')
                        || request()->filled('max_price');
                    $catalogIsFirstPage = (int) ($products->currentPage() ?: 1) === 1;
                    $showCatalogSpotlight = $catalogSpotlight->isNotEmpty()
                        && ! $hasActiveCatalogFilters
                        && $catalogIsFirstPage;
                @endphp

                @if($showCatalogSpotlight)
                    <section class="catalog-spotlight catalog-spotlight-section" aria-labelledby="catalog-spotlight-heading">
                        <div class="catalog-spotlight-inner">
                            <header class="catalog-spotlight-header">
                                <h2 id="catalog-spotlight-heading" class="catalog-spotlight-title">Destacados y novedades</h2>
                                <p class="catalog-spotlight-subtitle">Productos recomendados y recién incorporados al catálogo.</p>
                            </header>
                            <div class="catalog-spotlight-carousel"
                                 data-catalog-spotlight-carousel
                                 data-autoplay-delay="4000"
                                 role="region"
                                 aria-roledescription="carrusel"
                                 aria-label="Productos destacados y novedades del catálogo">
                                <div class="swiper catalog-spotlight-swiper">
                                    <div class="swiper-wrapper">
                                        @foreach($catalogSpotlight as $row)
                                            @php
                                                /** @var \App\Models\Product $product */
                                                $product = $row['product'];
                                                $spotlight = $row['spotlight'];
                                                $isFavorite = $favoriteProductIds->contains((int) $product->product_id);
                                                $spotLabel = $product->clientCatalogStockLabel();
                                                $spotSku = $product->clientCatalogAssignedSku();
                                                $spotlightPriceFormatted = number_format((float) $product->sale_price, 0, ',', '.');
                                            @endphp
                                            <article class="swiper-slide product-card product-card--catalog-spotlight product-card--catalog-cf128 catalog-spotlight-slide @if($spotLabel === 'Agotado') catalog-spotlight-slide--out-of-stock @endif"
                                                     role="group"
                                                     aria-roledescription="diapositiva"
                                                     aria-label="{{ $loop->iteration }} de {{ $loop->count }}: {{ $product->name }}">
                                                <a class="catalog-spotlight-card-link"
                                                   href="{{ $product->clientProductUrl() }}"
                                                   aria-label="Ver producto: {{ $product->name }}">
                                                    <div class="product-image product-image--catalog-cf128">
                                                        <div class="product-image__frame">
                                                            @include('client.parts.responsive-picture', [
                                                                'desktopWebp' => \App\Support\ProductImageUrls::mainImageWebpDesktop($product),
                                                                'mobileWebp' => \App\Support\ProductImageUrls::mainImageWebpMobile($product),
                                                                'fallback' => \App\Support\ProductImageUrls::fallbackUrl($product),
                                                                'alt' => '',
                                                                'loading' => 'lazy',
                                                            ])
                                                            <div class="product-image__hover-overlay" aria-hidden="true">
                                                                <span class="product-image__hover-price">₡{{ $spotlightPriceFormatted }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="product-info">
                                                        <h3 class="product-name catalog-spotlight-card-title">{{ $product->name }}</h3>
                                                        @php $spotRs = $productReviewStats[(int) $product->product_id] ?? null; @endphp
                                                        @include('client.parts.product-stars-inline', [
                                                            'avgStars' => (float) data_get($spotRs, 'avg', 0),
                                                            'reviewCount' => (int) data_get($spotRs, 'count', 0),
                                                            'variant' => 'card',
                                                        ])
                                                        @if($spotSku)
                                                            <p class="catalog-spotlight-card-sku">SKU: {{ $spotSku }}</p>
                                                        @endif
                                                        <p @class([
                                                            'product-availability-text',
                                                            'product-stock-badge',
                                                            'catalog-spotlight-stock-label',
                                                            'is-available' => $spotLabel === 'En stock',
                                                            'is-low' => $spotLabel === 'Últimas unidades',
                                                            'is-out' => $spotLabel === 'Agotado',
                                                            'is-na' => $spotLabel === 'No disponible',
                                                        ])>{{ $spotLabel }}</p>
                                                        <div class="product-price-bar catalog-spotlight-card-price">
                                                            <span class="product-price-value">₡{{ $spotlightPriceFormatted }}</span>
                                                        </div>
                                                    </div>
                                                </a>
                                                <button type="button"
                                                        class="product-favorite-btn catalog-spotlight-favorite {{ $isFavorite ? 'is-active' : '' }}"
                                                        data-product-favorite-btn
                                                        data-product-id="{{ $product->product_id }}"
                                                        aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                                                        aria-label="{{ $isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                                                    <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                                                </button>
                                                <span @class([
                                                    'spotlight-badge',
                                                    'catalog-spotlight-badge',
                                                    'spotlight-badge--featured' => $spotlight === 'featured',
                                                    'spotlight-badge--novelty' => $spotlight === 'novelty',
                                                ])>
                                                    {{ $spotlight === 'featured' ? 'Destacado' : 'Novedad' }}
                                                </span>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>

                                <button type="button"
                                        class="catalog-spotlight-nav catalog-spotlight-nav--prev"
                                        data-spotlight-prev
                                        aria-label="Producto destacado anterior">
                                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                </button>
                                <button type="button"
                                        class="catalog-spotlight-nav catalog-spotlight-nav--next"
                                        data-spotlight-next
                                        aria-label="Siguiente producto destacado">
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>
                @endif

                <div class="catalog-results" data-cf4-ajax-pagination data-cf4-ajax-scroll>
                    <div id="cf4-list-fragment">
                    <div class="catalog-toolbar results-header">
                        <div class="catalog-toolbar-primary">
                            @if($selectedCategory)
                                <p class="catalog-breadcrumb">Catálogo / {{ $selectedCategory->name }}</p>
                            @endif

                            @if($selectedBrand)
                                <a href="{{ route('clients.catalog', array_diff_key(request()->query(), ['brand_id' => ''])) }}"
                                   class="catalog-brand-chip"
                                   title="Quitar filtro de marca">
                                    <i class="fas fa-tag" aria-hidden="true"></i>
                                    {{ $selectedBrand->name }}
                                    <i class="fas fa-times catalog-brand-chip-remove" aria-hidden="true"></i>
                                </a>
                            @endif

                            <h2 class="catalog-toolbar-heading">
                                @if($selectedCategory)
                                    Productos en {{ $selectedCategory->name }}
                                @else
                                    Productos disponibles
                                @endif
                            </h2>
                        </div>

                        <div class="catalog-toolbar-meta">
                            <div class="catalog-toolbar-sort" aria-label="Ordenar resultados">
                                <div class="catalog-toolbar-sort-field">
                                    <label for="sort">Ordenar por</label>
                                    <select id="sort" name="sort" class="form-control catalog-toolbar-select" form="filter-form"
                                            onchange="document.getElementById('filter-form') && document.getElementById('filter-form').requestSubmit()">
                                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Más recientes</option>
                                        <option value="price"      {{ request('sort') == 'price'      ? 'selected' : '' }}>Precio</option>
                                        <option value="name"       {{ request('sort') == 'name'       ? 'selected' : '' }}>Nombre</option>
                                    </select>
                                </div>
                                <div class="catalog-toolbar-sort-field">
                                    <label for="direction">Dirección</label>
                                    <select id="direction" name="direction" class="form-control catalog-toolbar-select" form="filter-form"
                                            onchange="document.getElementById('filter-form') && document.getElementById('filter-form').requestSubmit()">
                                        <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Descendente</option>
                                        <option value="asc"  {{ request('direction') == 'asc'  ? 'selected' : '' }}>Ascendente</option>
                                    </select>
                                </div>
                                <div class="catalog-toolbar-sort-field">
                                    <label for="catalog-per-page">Por página</label>
                                    <select id="catalog-per-page" name="per_page" class="form-control catalog-toolbar-select" form="filter-form"
                                            onchange="(function(){var p=document.getElementById('catalog-list-page');if(p){p.value='1';}var f=document.getElementById('filter-form');if(f){if(f.requestSubmit){f.requestSubmit();}else{f.submit();}}})();">
                                        @foreach (\App\Support\AdminPerPage::ALLOWED as $size)
                                            <option value="{{ $size }}" @selected(\App\Support\AdminPerPage::resolve(request('per_page', 10)) === $size)>{{ $size }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="catalog-count results-count">
                                {{ number_format((int) $products->total(), 0, ',', '.') }} productos
                            </p>
                        </div>
                    </div>

                    @if($products->total() > 0)
                        <div class="products-grid">
                            @foreach($products as $product)
                                @php
                                    $catLabel = $product->clientCatalogStockLabel();
                                    $canBuy = $product->isPurchasableByClient();
                                    $isFavorite = $favoriteProductIds->contains((int) $product->product_id);
                                    $cardSku = $product->clientCatalogAssignedSku();
                                    $cardPriceFormatted = number_format((float) $product->sale_price, 0, ',', '.');
                                @endphp
                                <div class="product-card product-card--catalog-cf128 @if($catLabel === 'Agotado') product-card--out-of-stock @endif">
                                    <div class="product-image product-image--catalog-cf128">
                                        <button type="button"
                                                class="product-favorite-btn {{ $isFavorite ? 'is-active' : '' }}"
                                                data-product-favorite-btn
                                                data-product-id="{{ $product->product_id }}"
                                                aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                                                aria-label="{{ $isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                                            <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                                        </button>
                                        <div class="product-image__frame">
                                            <a class="product-image__link" href="{{ $product->clientProductUrl() }}">
                                                @include('client.parts.responsive-picture', [
                                                    'desktopWebp' => \App\Support\ProductImageUrls::mainImageWebpDesktop($product),
                                                    'mobileWebp' => \App\Support\ProductImageUrls::mainImageWebpMobile($product),
                                                    'fallback' => \App\Support\ProductImageUrls::fallbackUrl($product),
                                                    'alt' => $product->name,
                                                    'loading' => 'lazy',
                                                ])
                                            </a>
                                            <div class="product-image__hover-overlay" aria-hidden="true">
                                                <span class="product-image__hover-price">₡{{ $cardPriceFormatted }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-info product-info--catalog-cf128">
                                        <div class="product-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                        <h3 class="product-name">
                                            <a href="{{ $product->clientProductUrl() }}">
                                                {{ $product->name }}
                                            </a>
                                        </h3>
                                        @php $cardRs = $productReviewStats[(int) $product->product_id] ?? null; @endphp
                                        @include('client.parts.product-stars-inline', [
                                            'avgStars' => (float) data_get($cardRs, 'avg', 0),
                                            'reviewCount' => (int) data_get($cardRs, 'count', 0),
                                            'variant' => 'card',
                                        ])
                                        @if($cardSku)
                                            <p class="product-card-sku">SKU: {{ $cardSku }}</p>
                                        @endif
                                        <p @class([
                                            'product-availability-text',
                                            'product-stock-badge',
                                            'is-available' => $catLabel === 'En stock',
                                            'is-low' => $catLabel === 'Últimas unidades',
                                            'is-out' => $catLabel === 'Agotado',
                                            'is-na' => $catLabel === 'No disponible',
                                        ])>{{ $catLabel }}</p>
                                        @if($product->description)
                                            <p class="product-description">{{ Str::limit($product->description, 100) }}</p>
                                        @endif
                                        <div class="product-footer">
                                            <div class="product-price-bar">
                                                <span class="product-price-value">₡{{ $cardPriceFormatted }}</span>
                                            </div>
                                            <div class="product-actions">
                                                <a href="{{ $product->clientProductUrl() }}"
                                                   class="btn-product btn-ver-detalles"
                                                   title="Ver ficha del producto">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                    Ver producto
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
                        @pushOnce('styles', 'cf4-state-card-css')
                            @vite(['resources/css/errors/state-card.css'])
                        @endPushOnce
                        @if($emptyCategoryNoProducts)
                            <x-cf4.state-card
                                variant="embed"
                                :bare="true"
                                title-tag="h3"
                                eyebrow="Sin resultados"
                                title="No hay productos en esta categoría"
                                message="Probá otra categoría o limpiá los filtros para ver más piezas disponibles."
                            >
                                <x-slot name="actions">
                                    <a href="{{ route('clients.catalog', $catalogParams) }}" class="cf4-state-btn-primary">
                                        Ver todas las categorías
                                    </a>
                                    <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-secondary">
                                        Ir al catálogo
                                    </a>
                                </x-slot>
                            </x-cf4.state-card>
                        @else
                            @pushOnce('scripts', 'cf4-scenes-js')
                                @vite(['resources/js/errors/scenes.js'])
                            @endPushOnce
                            <x-cf4.state-card
                                variant="embed"
                                :bare="true"
                                title-tag="h3"
                                eyebrow="Búsqueda"
                                title="No encontramos esa pieza"
                                message="Intentá ajustar filtros o palabras de búsqueda, o volvé al catálogo completo."
                                scene="wrong_route"
                            >
                                <x-slot name="visual">
                                    @include('errors.partials.404-bike-svg')
                                </x-slot>
                                <x-slot name="fallback">
                                    <img
                                        class="cf4-error-fallback"
                                        src="{{ asset('images/errors/404-bike-illustration-orig.png') }}"
                                        alt=""
                                        role="presentation"
                                        loading="lazy"
                                    >
                                </x-slot>
                                <x-slot name="actions">
                                    <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-primary">
                                        Ver todos los productos
                                    </a>
                                    <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-secondary">
                                        Catálogo completo
                                    </a>
                                </x-slot>
                            </x-cf4.state-card>
                        @endif
                    @endif
                    </div>
                </div>
            </main>
    </div>
</section>

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