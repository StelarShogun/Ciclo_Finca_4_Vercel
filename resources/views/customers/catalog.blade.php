@extends('customers.layouts.app')

@section('title', 'Catálogo - Ciclo Pérez')

@section('content')
<div class="catalog-container">
    <!-- Header del Catálogo -->
    <div class="catalog-header">
        <div class="container">
            <h1 class="catalog-title">Catálogo de Productos</h1>
            <p class="catalog-subtitle">Explora nuestra amplia selección de productos</p>
        </div>
    </div>

    <div class="container">
        <div class="catalog-layout">
            <!-- Sidebar de Filtros -->
            <aside class="catalog-sidebar">
                <div class="filters-card">
                    <h3 class="filters-title">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </h3>
                    
                    <form method="GET" action="{{ route('customers.catalog') }}" id="filter-form">
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
                        
                        <!-- Categoría -->
                        <div class="filter-group">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-control">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->category_id }}" 
                                            {{ request('categoria_id') == $categoria->category_id ? 'selected' : '' }}>
                                        {{ $categoria->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Rango de Precio -->
                        <div class="filter-group">
                            <label>Rango de Precio</label>
                            <div class="price-range">
                                <input type="number" 
                                       id="precio_min" 
                                       name="precio_min" 
                                       class="form-control" 
                                       placeholder="Mínimo"
                                       value="{{ request('precio_min') }}">
                                <span class="price-separator">-</span>
                                <input type="number" 
                                       id="precio_max" 
                                       name="precio_max" 
                                       class="form-control" 
                                       placeholder="Máximo"
                                       value="{{ request('precio_max') }}">
                            </div>
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
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                                Aplicar Filtros
                            </button>
                            <a href="{{ route('customers.catalog') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Contenido Principal -->
            <main class="catalog-content">
                <div class="catalog-results" id="catalog-results-container">
                    @include('customers.partials.catalog-results', ['productos' => $productos])
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
(function() {
    var catalogUrl = '{{ route('customers.catalog') }}';
    var container = document.getElementById('catalog-results-container');
    var form = document.getElementById('filter-form');

    function loadCatalog(urlOrParams) {
        var url = (typeof urlOrParams === 'string' && urlOrParams.indexOf('catalog') !== -1)
            ? urlOrParams
            : catalogUrl + (urlOrParams ? '?' + urlOrParams : '');
        if (container) container.classList.add('loading');

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    if (typeof Swal !== 'undefined') Swal.fire('Aviso', data.error, 'warning');
                    return;
                }
                if (data.html && container) container.innerHTML = data.html;
                if (typeof url === 'string' && url.indexOf('?') !== -1) {
                    var u = new URL(url, window.location.origin);
                    window.history.replaceState({}, '', u.pathname + u.search);
                }
                bindPaginationLinks();
            })
            .catch(function() {
                if (container) container.innerHTML = '<p class="text-danger">Error al cargar los productos.</p>';
            })
            .finally(function() {
                if (container) container.classList.remove('loading');
            });
    }

    function bindPaginationLinks() {
        if (!container) return;
        var links = container.querySelectorAll('.pagination-wrapper a[href]');
        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                var href = this.getAttribute('href');
                if (!href || href.indexOf('catalog') === -1) return;
                e.preventDefault();
                loadCatalog(href);
            });
        });
    }

    var filterInputs = form.querySelectorAll('#buscar, #categoria_id, #precio_min, #precio_max, #ordenar, #direccion');
    var searchTimeout;
    filterInputs.forEach(function(el) {
        el.addEventListener('change', function() {
            loadCatalog(new URLSearchParams(new FormData(form)));
        });
        if (el.id === 'buscar') {
            el.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    loadCatalog(new URLSearchParams(new FormData(form)));
                }, 400);
            });
        }
    });

    var currentProductId = null;
    var catalogContent = document.querySelector('.catalog-content');
    if (catalogContent) {
        catalogContent.addEventListener('click', function(e) {
            var btn = e.target.closest('.add-to-cart-btn');
            if (!btn) return;
            e.preventDefault();
            currentProductId = btn.dataset.productId;
            var productName = btn.dataset.productName;
            var productPrice = parseFloat(btn.dataset.productPrice);
            var productStock = parseInt(btn.dataset.productStock);

            document.getElementById('preview-name').textContent = productName;
            document.getElementById('preview-price').textContent = '₡' + productPrice.toLocaleString('es-CR');
            document.getElementById('preview-stock').textContent = 'Stock disponible: ' + productStock;
            document.getElementById('cart-quantity').max = productStock;
            document.getElementById('cart-quantity').value = 1;

            var productCard = btn.closest('.product-card');
            var productImage = productCard ? productCard.querySelector('.product-image img') : null;
            if (productImage) document.getElementById('preview-image').src = productImage.src;

            document.getElementById('add-to-cart-modal').classList.add('active');
        });
    }

    document.getElementById('confirm-add-to-cart').addEventListener('click', function() {
        var quantity = parseInt(document.getElementById('cart-quantity').value, 10);
        if (quantity < 1) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
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
})();
</script>
@endpush
