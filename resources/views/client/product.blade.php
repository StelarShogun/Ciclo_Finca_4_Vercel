@extends('client.layouts.app')

@section('title', $product->name . ' - Ciclo Finca 4')

@push('meta')
@php
    $canonicalProductUrl = $product->clientProductUrl();
    $metaDesc = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($product->description ?? ''))), 155);
    if ($metaDesc === '') {
        $metaDesc = $product->name.' — Ciclo Finca 4';
    }
    $ogImage = asset('assets/images/products/'.($product->image ?? 'default.png'));
@endphp
<link rel="canonical" href="{{ $canonicalProductUrl }}" />
<meta name="description" content="{{ $metaDesc }}" />
@if($product->isPurchasableByClient())
<meta name="robots" content="index, follow" />
@else
<meta name="robots" content="noindex, follow" />
@endif
<meta property="og:title" content="{{ $product->name }} | Ciclo Finca 4" />
<meta property="og:description" content="{{ $metaDesc }}" />
<meta property="og:url" content="{{ $canonicalProductUrl }}" />
<meta property="og:type" content="product" />
<meta property="og:image" content="{{ $ogImage }}" />
<meta name="twitter:card" content="summary_large_image" />
@endpush

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
                    @if($product->isPurchasableByClient())
                        @if($product->clientShowsLowStockWarning())
                            <span class="stock-available stock-available--low">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Quedan pocas unidades</span>
                                <span class="stock-detail-count">· {{ $product->stock_current }} unidades disponibles</span>
                            </span>
                        @else
                            <span class="stock-available">
                                <i class="fas fa-check-circle"></i>
                                <span class="stock-status-word">Disponible</span>
                                <span class="stock-detail-count stock-detail-count--plain">· {{ $product->stock_current }} unidades disponibles</span>
                            </span>
                        @endif
                    @else
                        <span class="stock-unavailable">
                            <i class="fas fa-times-circle"></i>
                            {{ $product->clientCatalogStockLabel() }}
                        </span>
                    @endif
                </div>

                @if($product->isPurchasableByClient())
                    <div class="product-detail-actions">
                        <div class="quantity-selector">
                            <label>Cantidad:</label>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" id="decrease-qty">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="product-quantity" value="1" min="1" max="{{ $product->stock_current }}">
                                <button type="button" class="quantity-btn" id="increase-qty">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        @auth('clients')
                            <button type="button" class="btn btn-primary btn-lg add-to-cart-btn"
                                    data-purchasable="1"
                                    data-product-id="{{ $product->product_id }}"
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ $product->sale_price }}"
                                    data-product-stock="{{ $product->stock_current }}">
                                <i class="fas fa-cart-plus"></i>
                                Agregar al Carrito
                            </button>
                        @else
                            <button type="button" class="btn btn-primary btn-lg guest-add-btn" data-purchasable="1" data-product-stock="{{ $product->stock_current }}">
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
                        @php
                            $relLabel = $related->clientCatalogStockLabel();
                            $relCanBuy = $related->isPurchasableByClient();
                        @endphp
                        <div class="product-card">
                            <div class="product-image">
                                <a href="{{ $related->clientProductUrl() }}">
                                    <img src="{{ asset('assets/images/products/' . ($related->image ?? 'default.png')) }}"
                                         alt="{{ $related->name }}"
                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                         onerror="this.src=this.dataset.fallbackSrc;">
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category">{{ $related->category->name ?? 'Uncategorized' }}</div>
                                <h3 class="product-name">
                                    <a href="{{ $related->clientProductUrl() }}">
                                        {{ $related->name }}
                                    </a>
                                </h3>
                                <p @class([
                                    'product-availability-text',
                                    'is-available' => $relLabel === 'Disponible',
                                    'is-low' => $relLabel === 'Quedan pocas unidades',
                                    'is-out' => $relLabel === 'Agotado',
                                    'is-na' => $relLabel === 'No disponible',
                                ])>{{ $relLabel }}</p>
                                @if($relCanBuy)
                                    <p class="product-stock-qty">{{ number_format((int) ($related->stock_current ?? 0), 0, ',', '.') }} unidades disponibles</p>
                                @endif
                                <div class="product-footer">
                                    <div class="product-price">₡{{ number_format($related->sale_price, 0, ',', '.') }}</div>
                                    <a href="{{ $related->clientProductUrl() }}" class="btn btn-primary btn-sm">
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