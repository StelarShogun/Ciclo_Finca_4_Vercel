@extends('client.layouts.app')

@section('title', 'Inicio - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Bienvenido a Ciclo Finca 4</h1>
            <p class="hero-subtitle">Tu tienda especializada en bicicletas, componentes y accesorios para ciclismo</p>
            <div class="hero-actions">
                <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-th"></i>
                    Ver Catálogo
                </a>
            </div>
        </div>
        <div class="hero-image">
            <i class="fas fa-bicycle"></i>
        </div>
    </div>
</section>

<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Productos Destacados</h2>
            <p class="section-subtitle">Descubre nuestros productos más populares</p>
        </div>

        @if($featuredProducts->count() > 0)
            <div class="products-grid">
                @foreach($featuredProducts as $product)
                    <div class="product-card">
                        <div class="product-image">
                            {{-- Fallback to favicon if product image is missing --}}
                            <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}"
                                 alt="{{ $product->name }}"
                                 data-fallback-src="{{ asset('favicon.svg') }}"
                                 onerror="this.src=this.dataset.fallbackSrc;">
                            @if($product->stock_current <= 10)
                                <span class="product-badge stock-low">Stock Bajo</span>
                            @endif
                        </div>
                        <div class="product-info">
                            <div class="product-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                            <h3 class="product-name">{{ $product->name }}</h3>
                            @if($product->description)
                                <p class="product-description">{{ Str::limit($product->description, 80) }}</p>
                            @endif
                            <div class="product-footer">
                                <div class="product-price">₡{{ number_format($product->sale_price, 0, ',', '.') }}</div>
                                {{-- Guest button triggers a login prompt via JS --}}
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

            <div class="section-footer">
                <a href="{{ route('clients.catalog') }}" class="btn btn-secondary">
                    Ver Todos los Productos
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No hay productos destacados disponibles en este momento</p>
            </div>
        @endif
    </div>
</section>

@if($categories->count() > 0)
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Explora por Categoría</h2>
            <p class="section-subtitle">Encuentra lo que buscas fácilmente</p>
        </div>

        <div class="categories-grid">
            @foreach($categories as $category)
                <a href="{{ route('clients.catalog', ['category_id' => $category->category_id]) }}" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-bicycle"></i>
                    </div>
                    <h3 class="category-name">{{ $category->name }}</h3>
                    @if($category->description)
                        <p class="category-description">{{ Str::limit($category->description, 60) }}</p>
                    @endif
                    <span class="category-link">
                        Ver productos
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

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