<div class="results-header">
    <p class="results-count">
        Mostrando {{ $productos->firstItem() ?? 0 }}-{{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
    </p>
</div>

@php $fallbackImage = asset('favicon.svg'); @endphp

@if($productos->count() > 0)
    <div class="products-grid">
        @foreach($productos as $producto)
            <div class="product-card">
                <div class="product-image">
                    <a href="{{ route('customers.product', $producto->product_id) }}">
                        <img src="{{ asset('assets/images/products/' . ($producto->image ?? 'default.png')) }}" 
                             alt="{{ $producto->name }}"
                             onerror="this.src='{{ $fallbackImage }}'">
                    </a>
                    @if($producto->stock_current <= 10)
                        <span class="product-badge stock-low">Stock Bajo</span>
                    @endif
                </div>
                <div class="product-info">
                    <div class="product-category">{{ $producto->category->name ?? 'Uncategorized' }}</div>
                    <h3 class="product-name">
                        <a href="{{ route('customers.product', $producto->product_id) }}">
                            {{ $producto->name }}
                        </a>
                    </h3>
                    @if($producto->description)
                        <p class="product-description">{{ Str::limit($producto->description, 100) }}</p>
                    @endif
                    <div class="product-footer">
                        <div class="product-price">₡{{ number_format($producto->sale_price, 0, ',', '.') }}</div>
                        <button type="button" class="btn btn-primary btn-sm add-to-cart-btn" 
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

    <div class="pagination-wrapper">
        {{ $productos->links() }}
    </div>
@else
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>No se encontraron productos que coincidan con tu búsqueda</h3>
        <p>Intenta ajustar tus filtros de búsqueda</p>
        <a href="{{ route('customers.catalog') }}" class="btn btn-primary">
            Ver Todos los Productos
        </a>
    </div>
@endif