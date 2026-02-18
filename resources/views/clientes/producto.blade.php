@extends('clientes.layouts.app')

@section('title', $producto->nombre . ' - Ciclo Pérez')

@section('content')
<div class="product-detail-container">
    <div class="container">
        <nav class="breadcrumb">
            <a href="{{ route('clientes.home') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('clientes.catalogo') }}">Catálogo</a>
            <span>/</span>
            <span>{{ $producto->nombre }}</span>
        </nav>

        <div class="product-detail-layout">
            <div class="product-detail-image">
                <img src="{{ asset('assets/images/products/' . ($producto->imagen ?? 'default.png')) }}" 
                     alt="{{ $producto->nombre }}"
                     onerror="this.src='{{ asset('favicon.svg') }}'">
            </div>

            <div class="product-detail-info">
                <div class="product-detail-category">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</div>
                <h1 class="product-detail-name">{{ $producto->nombre }}</h1>
                
                @if($producto->descripcion)
                    <div class="product-detail-description">
                        <p>{{ $producto->descripcion }}</p>
                    </div>
                @endif

                <div class="product-detail-price">
                    <span class="price-label">Precio:</span>
                    <span class="price-amount">₡{{ number_format($producto->precio_venta, 0, ',', '.') }}</span>
                </div>

                <div class="product-detail-stock">
                    @if($producto->stock_actual > 0)
                        <span class="stock-available">
                            <i class="fas fa-check-circle"></i>
                            En stock ({{ $producto->stock_actual }} disponibles)
                        </span>
                    @else
                        <span class="stock-unavailable">
                            <i class="fas fa-times-circle"></i>
                            Sin stock
                        </span>
                    @endif
                </div>

                @if($producto->stock_actual > 0)
                    <div class="product-detail-actions">
                        <div class="quantity-selector">
                            <label>Cantidad:</label>
                            <div class="quantity-controls">
                                <button class="quantity-btn" id="decrease-qty">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="product-quantity" value="1" min="1" max="{{ $producto->stock_actual }}">
                                <button class="quantity-btn" id="increase-qty">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg add-to-cart-btn" 
                                data-product-id="{{ $producto->producto_id }}"
                                data-product-name="{{ $producto->nombre }}"
                                data-product-price="{{ $producto->precio_venta }}"
                                data-product-stock="{{ $producto->stock_actual }}">
                            <i class="fas fa-cart-plus"></i>
                            Agregar al Carrito
                        </button>
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
                                <a href="{{ route('clientes.producto', $relacionado->producto_id) }}">
                                    <img src="{{ asset('assets/images/products/' . ($relacionado->imagen ?? 'default.png')) }}" 
                                         alt="{{ $relacionado->nombre }}"
                                         onerror="this.src='{{ asset('favicon.svg') }}'">
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category">{{ $relacionado->categoria->nombre ?? 'Sin categoría' }}</div>
                                <h3 class="product-name">
                                    <a href="{{ route('clientes.producto', $relacionado->producto_id) }}">
                                        {{ $relacionado->nombre }}
                                    </a>
                                </h3>
                                <div class="product-footer">
                                    <div class="product-price">₡{{ number_format($relacionado->precio_venta, 0, ',', '.') }}</div>
                                    <a href="{{ route('clientes.producto', $relacionado->producto_id) }}" class="btn btn-primary btn-sm">
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
<script>
    // Control de cantidad
    let quantity = 1;
    const maxQuantity = {{ $producto->stock_actual }};
    
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
</script>
@endpush

