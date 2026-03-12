@extends('clientes.layouts.app')

@section('title', 'Inicio - Ciclo Pérez')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Bienvenido a Ciclo Pérez</h1>
            <p class="hero-subtitle">Tu tienda especializada en bicicletas, componentes y accesorios para ciclismo</p>
            <div class="hero-actions">
                <a href="{{ route('clientes.catalogo') }}" class="btn btn-primary btn-lg">
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

<!-- Productos Destacados -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Productos Destacados</h2>
            <p class="section-subtitle">Descubre nuestros productos más populares</p>
        </div>
        
        @if($productosDestacados->count() > 0)
            <div class="products-grid">
                @foreach($productosDestacados as $producto)
                    <div class="product-card">
                        <div class="product-image">
                            <img src="{{ asset('assets/images/products/' . ($producto->image ?? 'default.png')) }}" 
                                 alt="{{ $producto->name }}"
                                 onerror="this.src='{{ asset('favicon.svg') }}'">
                            @if($producto->stock_current <= 10)
                                <span class="product-badge stock-low">Stock Bajo</span>
                            @endif
                        </div>
                        <div class="product-info">
                            <div class="product-category">{{ $producto->category->name ?? 'Uncategorized' }}</div>
                            <h3 class="product-name">{{ $producto->name }}</h3>
                            @if($producto->description)
                                <p class="product-description">{{ Str::limit($producto->description, 80) }}</p>
                            @endif
                            <div class="product-footer">
                                <div class="product-price">₡{{ number_format($producto->sale_price, 0, ',', '.') }}</div>
                                <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                        data-product-id="{{ $producto->product_id }}"
                                        data-product-name="{{ $producto->name }}"
                                        data-product-price="{{ $producto->sale_price }}"
                                        data-product-stock="{{ $producto->stock_current }}">
                                    <i class="fas fa-cart-plus"></i>
                                    Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="section-footer">
                <a href="{{ route('clientes.catalogo') }}" class="btn btn-secondary">
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

<!-- Categorías -->
@if($categorias->count() > 0)
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Explora por Categoría</h2>
            <p class="section-subtitle">Encuentra lo que buscas fácilmente</p>
        </div>
        
        <div class="categories-grid">
            @foreach($categorias as $categoria)
                <a href="{{ route('clientes.catalogo', ['categoria_id' => $categoria->category_id]) }}" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-bicycle"></i>
                    </div>
                    <h3 class="category-name">{{ $categoria->name }}</h3>
                    @if($categoria->description)
                        <p class="category-description">{{ Str::limit($categoria->description, 60) }}</p>
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

<!-- Modal para agregar al carrito -->
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
<script>
    // Manejo del modal de agregar al carrito
    let currentProductId = null;
    
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentProductId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = parseFloat(this.dataset.productPrice);
            const productStock = parseInt(this.dataset.productStock);
            
            document.getElementById('preview-name').textContent = productName;
            document.getElementById('preview-price').textContent = '₡' + productPrice.toLocaleString('es-CR');
            document.getElementById('preview-stock').textContent = 'Stock disponible: ' + productStock;
            document.getElementById('cart-quantity').max = productStock;
            document.getElementById('cart-quantity').value = 1;
            
            const productCard = this.closest('.product-card');
            const productImage = productCard.querySelector('.product-image img');
            if (productImage) {
                document.getElementById('preview-image').src = productImage.src;
            }
            
            document.getElementById('add-to-cart-modal').classList.add('active');
        });
    });
    
    document.getElementById('confirm-add-to-cart').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('cart-quantity').value);
        
        if (quantity < 1) {
            Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
            return;
        }
        
        addToCart(currentProductId, quantity);
    });
    
    document.getElementById('cancel-add-to-cart').addEventListener('click', function() {
        document.getElementById('add-to-cart-modal').classList.remove('active');
    });
    
    document.getElementById('close-add-to-cart-modal').addEventListener('click', function() {
        document.getElementById('add-to-cart-modal').classList.remove('active');
    });
</script>
@endpush

