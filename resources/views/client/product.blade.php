@extends('client.layouts.app')

@section('title', $product->name . ' - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<div class="product-detail-container">
    <div class="container">
        <!-- Breadcrumb navigation -->
        <nav class="breadcrumb">
            <a href="{{ route('clients.home') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('clients.catalog') }}">Catálogo</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="product-detail-layout">
            <div class="product-detail-image">
                <!-- Fallback to favicon if product image is missing -->
                <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}" 
                     alt="{{ $product->name }}"
                     data-fallback-src="{{ asset('favicon.svg') }}"
                     onerror="this.src=this.dataset.fallbackSrc;">
            </div>

            <div class="product-detail-info">
                <div class="product-detail-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                <h1 class="product-detail-name">{{ $product->name }}</h1>
                
                @if($product->description)
                    <div class="product-detail-description">
                        <p>{{ $product->description }}</p>
                    </div>
                @endif

                <div class="product-detail-price">
                    <span class="price-label">Precio:</span>
                    <span class="price-amount">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
                </div>

                <div class="product-detail-stock">
                    @if($product->stock_current > 0)
                        <span class="stock-available">
                            <i class="fas fa-check-circle"></i>
                            En stock ({{ $product->stock_current }} disponibles)
                        </span>
                    @else
                        <span class="stock-unavailable">
                            <i class="fas fa-times-circle"></i>
                            Sin stock
                        </span>
                    @endif
                </div>

                <!-- Actions only shown when product is in stock -->
                @if($product->stock_current > 0)
                    <div class="product-detail-actions">
                        <div class="quantity-selector">
                            <label>Cantidad:</label>
                            <!-- max enforces stock limit on the client side -->
                            <div class="quantity-controls">
                                <button class="quantity-btn" id="decrease-qty">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="product-quantity" value="1" min="1" max="{{ $product->stock_current }}">
                                <button class="quantity-btn" id="increase-qty">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Authenticated users add to cart; guests are prompted to log in via JS -->
                        @auth('clients')
                        <button class="btn btn-primary btn-lg add-to-cart-btn" 
                                data-product-id="{{ $product->product_id }}"
                                data-product-name="{{ $product->name }}"
                                data-product-price="{{ $product->sale_price }}"
                                data-product-stock="{{ $product->stock_current }}">
                            <i class="fas fa-cart-plus"></i>
                            Agregar al Carrito
                        </button>
                        @else
                        <button class="btn btn-primary btn-lg guest-add-btn" type="button">
                            <i class="fas fa-cart-plus"></i>
                            Agregar al Carrito
                        </button>
                        @endauth
                    </div>
                @endif
            </div>
        </div>

        <!-- Related products from the same category -->
        @if($relatedProducts->count() > 0)
            <section class="related-products">
                <h2 class="section-title">Productos Relacionados</h2>
                <div class="products-grid">
                    @foreach($relatedProducts as $related)
                        <div class="product-card">
                            <div class="product-image">
                                <a href="{{ route('clients.product', $related->product_id) }}">
                                    <img src="{{ asset('assets/images/products/' . ($related->image ?? 'default.png')) }}" 
                                         alt="{{ $related->name }}"
                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                         onerror="this.src=this.dataset.fallbackSrc;">
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category">{{ $related->category->name ?? 'Uncategorized' }}</div>
                                <h3 class="product-name">
                                    <a href="{{ route('clients.product', $related->product_id) }}">
                                        {{ $related->name }}
                                    </a>
                                </h3>
                                <div class="product-footer">
                                    <div class="product-price">₡{{ number_format($related->sale_price, 0, ',', '.') }}</div>
                                    <a href="{{ route('clients.product', $related->product_id) }}" class="btn btn-primary btn-sm">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-page.js'])
@endpush