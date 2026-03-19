@extends('clientes.layouts.app')

@section('title', 'Catálogo - Ciclo Finca 4')

@section('content')
<div class="catalog-container">
    <!-- Header del Catálogo -->
    <div class="catalog-header">
        <div class="container">
            <h1 class="catalog-title">Catálogo de Productos</h1>
            <p class="catalog-subtitle">Explora nuestra amplia selección de productos</p>
        </div>
    </div>

    {{-- Zona de categorías (barra horizontal de dos filas) --}}
    @php
        $catalogQuery = request()->except(['cat', 'sub', 'page']);
    @endphp
    <nav class="catalog-categories-bar" aria-label="Navegación por categorías">
        <div class="container">
        <div class="categories-row categories-row--parent">
            <a href="{{ route('clientes.catalogo', $catalogQuery) }}"
               class="category-pill {{ !$categoriaPadreActual ? 'category-pill--active' : 'category-pill--muted' }}"
               {{ !$categoriaPadreActual ? 'aria-current="location"' : '' }}>
                Todas las categorías
            </a>
            @foreach($categorias as $cat)
                @php $catSlug = \Illuminate\Support\Str::slug($cat->name); @endphp
                <a href="{{ route('clientes.catalogo', array_merge($catalogQuery, ['cat' => $catSlug])) }}"
                   class="category-pill {{ $categoriaPadreActual && $categoriaPadreActual->category_id === $cat->category_id ? 'category-pill--active' : '' }} {{ $categoriaPadreActual && $categoriaPadreActual->category_id !== $cat->category_id ? 'category-pill--muted' : '' }}"
                   {{ $categoriaPadreActual && $categoriaPadreActual->category_id === $cat->category_id ? 'aria-current="location"' : '' }}>
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
        @if($categoriaPadreActual)
        <div class="categories-row categories-row--sub">
            <span class="categories-row-context">En {{ $categoriaPadreActual->name }}:</span>
            @php $parentSlug = \Illuminate\Support\Str::slug($categoriaPadreActual->name); @endphp
            <a href="{{ route('clientes.catalogo', array_merge($catalogQuery, ['cat' => $parentSlug])) }}"
               class="subcategory-pill {{ !$subcategoriaActual ? 'subcategory-pill--active' : 'subcategory-pill--muted' }}"
               {{ !$subcategoriaActual ? 'aria-current="location"' : '' }}>
                Todas
            </a>
            @foreach($categoriaPadreActual->childCategories as $sub)
                @php $subSlug = \Illuminate\Support\Str::slug($sub->name); @endphp
                <a href="{{ route('clientes.catalogo', array_merge($catalogQuery, ['cat' => $parentSlug, 'sub' => $subSlug])) }}"
                   class="subcategory-pill {{ $subcategoriaActual && $subcategoriaActual->category_id === $sub->category_id ? 'subcategory-pill--active' : 'subcategory-pill--muted' }}"
                   {{ $subcategoriaActual && $subcategoriaActual->category_id === $sub->category_id ? 'aria-current="location"' : '' }}>
                    {{ $sub->name }}
                </a>
            @endforeach
        </div>
        @endif
        </div>
    </nav>

    <div class="container">
        <div class="catalog-layout">
            <!-- Sidebar de Filtros -->
            <aside class="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>
                    
                    <form method="GET" action="{{ route('clientes.catalogo') }}" id="filter-form">
                        @if(request('cat'))
                            <input type="hidden" name="cat" value="{{ request('cat') }}">
                        @endif
                        @if(request('sub'))
                            <input type="hidden" name="sub" value="{{ request('sub') }}">
                        @endif
                        <!-- Búsqueda -->
                        <div class="filter-group">
                            <label for="buscar">Buscar</label>
                            <input type="text" 
                                   id="buscar" 
                                   name="buscar" 
                                   class="form-control" 
                                   placeholder="Nombre o descripción..."
                                   value="{{ request('buscar') }}">
                        </div>
                        
                        <!-- Rango de Precio -->
                        <div class="filter-group">
                            <label>Rango de Precio</label>
                            <div class="price-range">
                                <input type="number" 
                                       id="precio_min" 
                                       name="precio_min" 
                                       class="form-control {{ $errorRangoPrecio ?? false ? 'is-invalid' : '' }}" 
                                       placeholder="Mínimo"
                                       value="{{ request('precio_min') }}"
                                       aria-describedby="{{ ($errorRangoPrecio ?? false) ? 'precio-rango-error' : '' }}">
                                <span class="price-separator">-</span>
                                <input type="number" 
                                       id="precio_max" 
                                       name="precio_max" 
                                       class="form-control {{ $errorRangoPrecio ?? false ? 'is-invalid' : '' }}" 
                                       placeholder="Máximo"
                                       value="{{ request('precio_max') }}"
                                       aria-describedby="{{ ($errorRangoPrecio ?? false) ? 'precio-rango-error' : '' }}">
                            </div>
                            @if(!empty($errorRangoPrecio))
                                <p id="precio-rango-error" class="filter-price-error filter-price-error-visible mt-2 mb-0" role="alert">
                                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                    <span>El precio mínimo no puede ser mayor que el máximo. Ajusta el rango o se ignorará el filtro de precio.</span>
                                </p>
                            @endif
                        </div>
                        
                        <!-- Ordenar -->
                        <div class="filter-group">
                            <label for="ordenar">Ordenar por</label>
                            <select id="ordenar" name="ordenar" class="form-control">
                                <option value="fecha_creacion" {{ request('ordenar') == 'fecha_creacion' ? 'selected' : '' }}>Más recientes</option>
                                <option value="precio" {{ request('ordenar') == 'precio' ? 'selected' : '' }}>Precio</option>
                                <option value="nombre" {{ request('ordenar') == 'nombre' ? 'selected' : '' }}>Nombre</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="direccion">Dirección</label>
                            <select id="direccion" name="direccion" class="form-control">
                                <option value="desc" {{ request('direccion') == 'desc' ? 'selected' : '' }}>Descendente</option>
                                <option value="asc" {{ request('direccion') == 'asc' ? 'selected' : '' }}>Ascendente</option>
                            </select>
                        </div>
                        
                        <!-- Botones -->
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-block" id="apply-filters-btn" {{ !empty($errorRangoPrecio) ? 'disabled' : '' }}>
                                <i class="fas fa-search"></i>
                                Aplicar Filtros
                            </button>
                            <a href="{{ route('clientes.catalogo') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Contenido Principal -->
            <main class="catalog-content">
                <!-- Resultados -->
                <div class="catalog-results">
                    <div class="results-header">
                        <nav class="catalog-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('clientes.catalogo', request()->except(['cat', 'sub', 'page'])) }}">Catálogo</a>
                            @if($categoriaPadreActual)
                                <span class="catalog-breadcrumb-sep">/</span>
                                @if($subcategoriaActual)
                                    <a href="{{ route('clientes.catalogo', array_merge(request()->except(['sub', 'page']), ['cat' => \Illuminate\Support\Str::slug($categoriaPadreActual->name)])) }}">{{ $categoriaPadreActual->name }}</a>
                                    <span class="catalog-breadcrumb-sep">/</span>
                                    <span class="catalog-breadcrumb-current">{{ $subcategoriaActual->name }}</span>
                                @else
                                    <span class="catalog-breadcrumb-current">{{ $categoriaPadreActual->name }}</span>
                                @endif
                            @endif
                        </nav>
                        <p class="results-count">
                            @if($productos->total() > 0)
                                Mostrando {{ $productos->firstItem() }}-{{ $productos->lastItem() }} de {{ $productos->total() }} productos
                            @else
                                0 productos encontrados
                                @if(request()->hasAny(['buscar', 'cat', 'sub', 'precio_min', 'precio_max']))
                                    <span class="results-count-note">(tus filtros se mantienen aplicados)</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    
                    @if($productos->count() > 0)
                        <div class="products-grid">
                            @foreach($productos as $producto)
                                <div class="product-card product-card--catalog">
                                    <div class="product-image">
                                        <a href="{{ route('clientes.producto', $producto->product_id) }}" class="product-image-link" aria-label="Ver detalle de {{ $producto->name }}">
                                            <img src="{{ asset('assets/images/products/' . ($producto->image ?? 'default.png')) }}" 
                                                 alt="{{ $producto->name }}"
                                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/products/default.png') }}';">
                                        </a>
                                        @if($producto->stock_current <= 10)
                                            <span class="product-badge stock-low">Stock Bajo</span>
                                        @endif
                                    </div>
                                    <div class="product-info">
                                        <div class="product-category">{{ $producto->category->name ?? 'Uncategorized' }}</div>
                                        <h3 class="product-name">
                                            <a href="{{ route('clientes.producto', $producto->product_id) }}">
                                                {{ $producto->name }}
                                            </a>
                                        </h3>
                                        @if($producto->description)
                                            <p class="product-description">{{ Str::limit($producto->description, 100) }}</p>
                                        @endif
                                        <div class="product-price-row">
                                            <span class="product-price">₡{{ number_format($producto->sale_price, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="product-actions">
                                            <a href="{{ route('clientes.producto', $producto->product_id) }}" class="btn btn-view-details">
                                                <i class="fas fa-arrow-right"></i>
                                                Ver detalles
                                            </a>
                                            @if(Auth::guard('clients')->check())
                                            <button type="button" class="btn btn-primary btn-add-cart add-to-cart-btn" 
                                                    data-product-id="{{ $producto->product_id }}"
                                                    data-product-name="{{ $producto->name }}"
                                                    data-product-price="{{ $producto->sale_price }}"
                                                    data-product-stock="{{ $producto->stock_current }}">
                                                <i class="fas fa-cart-plus"></i>
                                                Agregar
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-primary btn-add-cart guest-add-btn">
                                                <i class="fas fa-cart-plus"></i>
                                                Agregar
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Paginación (mismo estilo que dashboard admin) -->
                        <div class="pagination-wrapper">
                            <x-pagination :paginator="$productos" label="del catálogo" />
                        </div>
                    @else
                        <div class="empty-state">
                            @if($categoriaPadreActual || $subcategoriaActual)
                                <i class="fas fa-folder-open"></i>
                                <h3>No hay productos disponibles en esta categoría.</h3>
                                <p>Intenta seleccionar otra categoría del listado o ver todos los productos del catálogo.</p>
                            @elseif(!empty($tieneFiltroPrecio))
                                <i class="fas fa-coins"></i>
                                <h3>No hay productos en el rango de precios seleccionado</h3>
                                <p>Tus filtros se mantienen aplicados. Puedes ajustar el rango de precios (o el resto de filtros) en el panel izquierdo y volver a buscar, o ver todos los productos sin filtros.</p>
                            @else
                                <i class="fas fa-search"></i>
                                <h3>No se encontraron productos</h3>
                                <p>Ningún producto coincide con los criterios de búsqueda. Tus filtros se mantienen aplicados; modifica el panel izquierdo o quita los filtros para ver el catálogo completo.</p>
                            @endif
                            <div class="empty-state-actions">
                                <a href="#filter-form" class="btn btn-outline-primary scroll-to-filters">
                                    <i class="fas fa-sliders-h"></i>
                                    Modificar filtros
                                </a>
                                <a href="{{ route('clientes.catalogo') }}" class="btn btn-primary">
                                    <i class="fas fa-th"></i>
                                    Ver todos los productos
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal para agregar al carrito (mismo que en home) -->
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
    // Bloquear "Aplicar Filtros" cuando precio mínimo > precio máximo
    (function() {
        var form = document.getElementById('filter-form');
        var btn = document.getElementById('apply-filters-btn');
        var minInput = document.getElementById('precio_min');
        var maxInput = document.getElementById('precio_max');
        if (!form || !btn || !minInput || !maxInput) return;

        function checkPriceRange() {
            var min = parseFloat(minInput.value);
            var max = parseFloat(maxInput.value);
            var minFilled = minInput.value.trim() !== '' && !isNaN(min);
            var maxFilled = maxInput.value.trim() !== '' && !isNaN(max);
            var invalid = minFilled && maxFilled && min > max;
            btn.disabled = invalid;
        }

        minInput.addEventListener('input', checkPriceRange);
        minInput.addEventListener('change', checkPriceRange);
        maxInput.addEventListener('input', checkPriceRange);
        maxInput.addEventListener('change', checkPriceRange);
        checkPriceRange(); // estado inicial por si viene con error del servidor
    })();

    // Manejo del modal de agregar al carrito (mismo código que en home)
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

