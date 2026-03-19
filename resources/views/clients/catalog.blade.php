@extends('clients.layouts.app')

@section('title', 'Catálogo - Ciclo Pérez')

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
            <!-- Sidebar with filters -->
            <aside class="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>
                    
                    <!-- Filters submitted as GET to keep results shareable via URL -->
                    <form method="GET" action="{{ route('clients.catalog') }}" id="filter-form">
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
                            <label for="category_id">Categoría</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">Todas las categorías</option>
                                <!-- Preserve selected category across page reloads -->
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}" 
                                            {{ request('category_id') == $category->category_id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Rango de Precio</label>
                            <div class="price-range">
                                <input type="number" 
                                       id="min_price" 
                                       name="min_price" 
                                       class="form-control" 
                                       placeholder="Mínimo"
                                       value="{{ request('min_price') }}">
                                <span class="price-separator">-</span>
                                <input type="number" 
                                       id="max_price" 
                                       name="max_price" 
                                       class="form-control" 
                                       placeholder="Máximo"
                                       value="{{ request('max_price') }}">
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
                            <!-- Clear resets all filters by going to base catalog URL -->
                            <a href="{{ route('clients.catalog') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <main class="catalog-content">
                <div class="catalog-results">
                    <div class="results-header">
                        <!-- firstItem/lastItem return null when no results, fallback to 0 -->
                        <p class="results-count">
                            Mostrando {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} de {{ $products->total() }} productos
                        </p>
                    </div>
                    
                    @if($products->count() > 0)
                        <div class="products-grid">
                            @foreach($products as $product)
                                <div class="product-card">
                                    <div class="product-image">
                                        <a href="{{ route('clients.product', $product->product_id) }}">
                                            <!-- Fallback to favicon if product image is missing -->
                                            <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}" 
                                                 alt="{{ $product->name }}"
                                                 data-fallback-src="{{ asset('favicon.svg') }}"
                                                 onerror="this.src=this.dataset.fallbackSrc;">
                                        </a>
                                        <!-- Badge shown when stock is critically low -->
                                        @if($product->stock_current <= 10)
                                            <span class="product-badge stock-low">Stock Bajo</span>
                                        @endif
                                    </div>
                                    <div class="product-info">
                                        <div class="product-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                        <h3 class="product-name">
                                            <a href="{{ route('clients.product', $product->product_id) }}">
                                                {{ $product->name }}
                                            </a>
                                        </h3>
                                        @if($product->description)
                                            <p class="product-description">{{ Str::limit($product->description, 100) }}</p>
                                        @endif
                                        <div class="product-footer">
                                            <div class="product-price">₡{{ number_format($product->sale_price, 0, ',', '.') }}</div>
                                            <!-- Authenticated users add to cart; guests are prompted to log in via JS -->
                                            @auth('clients')
                                            <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                                    data-product-id="{{ $product->product_id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-price="{{ $product->sale_price }}"
                                                    data-product-stock="{{ $product->stock_current }}">
                                                <i class="fas fa-cart-plus"></i>
                                                Agregar
                                            </button>
                                            @else
                                            <button class="btn btn-primary btn-sm guest-add-btn" type="button">
                                                <i class="fas fa-cart-plus"></i>
                                                Agregar
                                            </button>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="pagination-wrapper">
                            {{ $products->links() }}
                        </div>
                    @else
                        <!-- No results state -->
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

<!-- Modal to select quantity before adding a product to cart -->
<div class="modal" id="add-to-cart-modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Agregar al Carrito</h3>
            <button class="modal-close" id="close-add-to-cart-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Populated dynamically by JS when a product is selected -->
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