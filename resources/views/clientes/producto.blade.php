@extends('clientes.layouts.app')

@section('title', $producto->name . ' - Ciclo Finca 4')

@section('content')
<div class="product-detail-container">
    <div class="container">
        <a href="{{ route('clientes.catalogo') }}" class="product-detail-back btn btn-primary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span class="product-detail-back-label">Volver al catálogo</span>
        </a>

        <div class="product-detail-layout">
            @php
                $productImages = $producto->getDisplayImages();
                $hasCarousel = count($productImages) > 1;
            @endphp
            <div class="product-detail-hero {{ $hasCarousel ? 'product-detail-hero--carousel' : '' }}">
                @if($hasCarousel)
                    <div class="product-carousel" id="product-carousel">
                        <button type="button" class="product-carousel-btn product-carousel-btn--prev" aria-label="Imagen anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="product-carousel-track">
                            @foreach($productImages as $index => $imgPath)
                                <div class="product-carousel-slide {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                                    <img src="{{ asset('assets/images/products/' . $imgPath) }}" 
                                         alt="{{ $producto->name }} - {{ $index + 1 }}"
                                         onerror="this.onerror=null; this.src='{{ asset('assets/images/products/default.png') }}';">
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="product-carousel-btn product-carousel-btn--next" aria-label="Siguiente imagen">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="product-carousel-dots">
                            @foreach($productImages as $index => $imgPath)
                                <button type="button" class="product-carousel-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}" aria-label="Ir a imagen {{ $index + 1 }}"></button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <img src="{{ asset('assets/images/products/' . ($productImages[0] ?? 'default.png')) }}" 
                         alt="{{ $producto->name }}"
                         onerror="this.onerror=null; this.src='{{ asset('assets/images/products/default.png') }}';">
                @endif
            </div>

            <div class="product-detail-content">
                <div class="product-detail-header">
                    <div class="product-detail-header-left">
                        <h1 class="product-detail-name">{{ $producto->name }}</h1>
                        <div class="product-detail-badges">
                            <span class="product-detail-badge product-detail-badge--category">
                                <i class="fas fa-tag"></i>
                                {{ $producto->category->name ?? 'Sin categoría' }}
                            </span>
                            <span class="product-detail-badge product-detail-badge--sku">
                                <i class="fas fa-barcode"></i>
                                SKU: {{ 'BK-' . str_pad($producto->product_id, 3, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    </div>
                    <div class="product-detail-header-right">
                        <div class="product-detail-price-block">
                            <span class="price-amount">₡{{ number_format($producto->sale_price, 0, ',', '.') }}</span>
                        </div>
                        @if($producto->stock_current > 0)
                            <span class="product-detail-badge product-detail-badge--stock">✓ Disponible</span>
                        @else
                            <span class="product-detail-badge product-detail-badge--out">Producto temporalmente agotado</span>
                        @endif
                    </div>
                </div>

                @if($producto->description)
                    <div class="product-detail-description">
                        <h3 class="product-detail-description-title">Descripción</h3>
                        <p>{{ $producto->description }}</p>
                    </div>
                @endif

                @if($producto->stock_current > 0)
                    <div class="product-detail-actions">
                        <div class="quantity-selector quantity-selector--detail">
                            <label class="quantity-selector-label" for="product-quantity">Cantidad</label>
                            <div class="quantity-controls quantity-controls--grouped">
                                <button class="quantity-btn quantity-btn--minus" id="decrease-qty" type="button" aria-label="Disminuir cantidad">
                                    <i class="fas fa-minus" aria-hidden="true"></i>
                                </button>
                                <input type="number" id="product-quantity" class="quantity-input" value="1" min="1" max="{{ $producto->stock_current }}" aria-label="Cantidad">
                                <button class="quantity-btn quantity-btn--plus" id="increase-qty" type="button" aria-label="Aumentar cantidad">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        @auth
                        <button class="btn btn-primary btn-lg add-to-cart-btn" 
                                data-product-id="{{ $producto->product_id }}"
                                data-product-name="{{ $producto->name }}"
                                data-product-price="{{ $producto->sale_price }}"
                                data-product-stock="{{ $producto->stock_current }}">
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

        @if($productosRelacionados->count() > 0)
            <section class="related-products">
                <h2 class="section-title">Productos Relacionados</h2>
                <div class="products-grid">
                    @foreach($productosRelacionados as $relacionado)
                        <div class="product-card">
                            <div class="product-image">
                                <a href="{{ route('clientes.producto', $relacionado->product_id) }}">
                                    <img src="{{ asset('assets/images/products/' . ($relacionado->image ?? 'default.png')) }}" 
                                         alt="{{ $relacionado->name }}"
                                         onerror="this.src='{{ asset('favicon.svg') }}'">
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category">{{ $relacionado->category->name ?? 'Uncategorized' }}</div>
                                <h3 class="product-name">
                                    <a href="{{ route('clientes.producto', $relacionado->product_id) }}">
                                        {{ $relacionado->name }}
                                    </a>
                                </h3>
                                <div class="product-price-row">
                                    <span class="product-price">₡{{ number_format($relacionado->sale_price, 0, ',', '.') }}</span>
                                </div>
                                <div class="product-actions">
                                    <a href="{{ route('clientes.producto', $relacionado->product_id) }}" class="btn btn-view-details">
                                        <i class="fas fa-arrow-right"></i>
                                        Ver detalles
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
<script>
    // Control de cantidad
    let quantity = 1;
    const maxQuantity = {{ $producto->stock_current }};
    
    document.getElementById('decrease-qty')?.addEventListener('click', function() {
        if (quantity > 1) {
            quantity--;
            document.getElementById('product-quantity').value = quantity;
        }
    });
    
    document.getElementById('increase-qty')?.addEventListener('click', function() {
        if (quantity < maxQuantity) {
            quantity++;
            document.getElementById('product-quantity').value = quantity;
        }
    });
    
    document.getElementById('product-quantity')?.addEventListener('change', function() {
        const value = parseInt(this.value);
        if (value < 1) {
            this.value = 1;
            quantity = 1;
        } else if (value > maxQuantity) {
            this.value = maxQuantity;
            quantity = maxQuantity;
        } else {
            quantity = value;
        }
    });
    
    // Agregar al carrito
    document.querySelector('.add-to-cart-btn')?.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const qty = parseInt(document.getElementById('product-quantity').value);
        addToCart(productId, qty);
    });

    // Carrusel de imágenes del producto
    (function() {
        var carousel = document.getElementById('product-carousel');
        if (!carousel) return;
        var slides = carousel.querySelectorAll('.product-carousel-slide');
        var dots = carousel.querySelectorAll('.product-carousel-dot');
        var btnPrev = carousel.querySelector('.product-carousel-btn--prev');
        var btnNext = carousel.querySelector('.product-carousel-btn--next');
        var total = slides.length;
        var current = 0;

        function goTo(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            current = index;
            slides.forEach(function(s, i) { s.classList.toggle('active', i === current); });
            dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
        }

        if (btnPrev) btnPrev.addEventListener('click', function() { goTo(current - 1); });
        if (btnNext) btnNext.addEventListener('click', function() { goTo(current + 1); });
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() { goTo(i); });
        });
    })();
</script>
@endpush

