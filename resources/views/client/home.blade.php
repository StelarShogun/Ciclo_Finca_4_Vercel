@extends('client.layouts.app')

@section('title', 'Inicio - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<!-- Hero: imagen full-bleed + overlay; reemplaza public/assets/images/hero/hero-downhill.jpg por tu arte -->
<section class="hero-section" aria-label="Bienvenida a Ciclo Finca 4">
    <div class="hero-backdrop" aria-hidden="true">
        <img src="{{ asset('assets/images/hero/hero-downhill.jpg') }}"
             alt=""
             width="1920"
             height="1080"
             fetchpriority="high"
             decoding="async">
    </div>
    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge">
                🚴 Ciclos listos para rodar
            </div>

            <h1 class="hero-title">
                Tus bicicletas y componentes<br>
                <strong>de calidad en tienda</strong>
            </h1>

            <div class="hero-divider"></div>

            <p class="hero-subtitle">
                Explora nuestro catálogo y deja tu solicitud para retiro en tienda con asesoría personalizada
            </p>

            <p class="hero-description">
                Bicicletas, componentes y accesorios listos para que disfrutes del ciclismo con confianza.
            </p>

            <div class="hero-actions">
                <a href="{{ route('clients.catalog') }}" class="btn btn-primary">
                    <span>Ver Catálogo</span>
                    <i class="fas fa-arrow-right"></i>
                </a>

                <a href="#benefits-section" class="btn btn-secondary">
                    Conoce Nuestro Servicio
                </a>
            </div>

            <div class="hero-benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span class="benefit-text">Asesoría en tienda</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span class="benefit-text">Preparación completa</span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">✓</span>
                    <span class="benefit-text">Retiro rápido</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
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
                            <!-- Fallback to favicon if product image is missing -->
                            <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default.png')) }}" 
                                 alt="{{ $product->name }}"
                                 data-fallback-src="{{ asset('favicon.svg') }}"
                                 onerror="this.src=this.dataset.fallbackSrc;">
                            <!-- Badge shown when stock is critically low -->
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
            
            <div class="section-footer">
                <a href="{{ route('clients.catalog') }}" class="btn btn-secondary">
                    Ver Todos los Productos
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @else
            <!-- No featured products available -->
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No hay productos destacados disponibles en este momento</p>
            </div>
        @endif
    </div>
</section>

<!-- Categories: carrusel de padres + chips de subcategorías -->
@if($categories->count() > 0)
@php
    $categoryIcons = config('category_icons', []);
@endphp
<section class="categories-section" aria-labelledby="categories-heading">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="categories-heading">Explora por categoría</h2>
            <p class="section-subtitle">Desliza para ver cada familia de productos y sus subcategorías</p>
        </div>

        <div class="categories-carousel-wrap" data-categories-carousel>
            <button type="button" class="categories-carousel-btn categories-carousel-btn--prev" aria-label="Categoría anterior" data-carousel-prev>
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="categories-carousel" role="region" aria-roledescription="carrusel" aria-label="Categorías de productos">
                <div class="categories-carousel-track" data-carousel-track>
                    @foreach($categories as $category)
                        @php
                            $iconKey = strtolower(trim($category->name));
                            $faIcon = $categoryIcons[$iconKey] ?? 'bicycle';
                        @endphp
                        <article class="category-slide">
                            <div class="category-slide-card">
                                <a href="{{ route('clients.catalog', ['category_id' => $category->category_id]) }}" class="category-slide-main">
                                    <div class="category-icon category-icon--lg" aria-hidden="true">
                                        <i class="fas fa-{{ $faIcon }}"></i>
                                    </div>
                                    <h3 class="category-name">{{ $category->name }}</h3>
                                    @if($category->description)
                                        <p class="category-slide-tagline">{{ Str::limit($category->description, 72) }}</p>
                                    @endif
                                    <span class="category-slide-cta">
                                        Ver todo en {{ $category->name }}
                                        <i class="fas fa-arrow-right"></i>
                                    </span>
                                </a>
                                @if($category->childCategories->isNotEmpty())
                                    <div class="category-subchips" role="group" aria-label="Subcategorías de {{ $category->name }}">
                                        @foreach($category->childCategories as $sub)
                                            <a href="{{ route('clients.catalog', ['category_id' => $sub->category_id]) }}" class="category-subchip">
                                                {{ $sub->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <button type="button" class="categories-carousel-btn categories-carousel-btn--next" aria-label="Siguiente categoría" data-carousel-next>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
@endif

<!-- Marketing: Encargos y retiro en tienda -->
<section class="benefits-section" id="benefits-section" aria-label="Beneficios del servicio">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Encargos listos para retirar</h2>
            <p class="section-subtitle">Elige en el catálogo y te ayudamos a dejar tu compra lista para retirar en tienda.</p>
        </div>

        <div class="benefits-grid" role="list" aria-label="Beneficios principales">
            <div class="benefit-card" role="listitem">
                <i class="fas fa-tools" aria-hidden="true"></i>
                <h3 class="benefit-title">Taller propio</h3>
                <p class="benefit-desc">Revisión, ajuste y preparación en tienda para que salgas rodando con confianza.</p>
            </div>

            <div class="benefit-card" role="listitem">
                <i class="fas fa-user-tie" aria-hidden="true"></i>
                <h3 class="benefit-title">Asesoría personalizada</h3>
                <p class="benefit-desc">Te orientamos para elegir la opción correcta según tu ruta, MTB o gravel.</p>
            </div>

            <div class="benefit-card" role="listitem">
                <i class="fas fa-warehouse" aria-hidden="true"></i>
                <h3 class="benefit-title">Inventario y disponibilidad</h3>
                <p class="benefit-desc">Confirmamos disponibilidad y te decimos cuándo está listo para retirar.</p>
            </div>

            <div class="benefit-card" role="listitem">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <h3 class="benefit-title">Soporte post-retirada</h3>
                <p class="benefit-desc">Acompañamos después de tu retiro con recomendaciones de uso y cuidados.</p>
            </div>
        </div>
    </div>
</section>

<!-- Marketing: Cómo funciona -->
<section class="how-it-works-section" aria-label="Cómo funciona el encargo">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Cómo funciona</h2>
            <p class="section-subtitle">Tres pasos simples para dejar tu encargo listo para retirar en tienda.</p>
        </div>

        <div class="steps-grid" role="list" aria-label="Pasos del encargo">
            <div class="step-card" role="listitem">
                <div class="step-number">1</div>
                <h3 class="step-title">Explora el catálogo</h3>
                <p class="step-desc">Busca bicicletas, componentes y accesorios según tu estilo de ciclismo.</p>
                <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-lg step-cta">Ver catálogo</a>
            </div>

            <div class="step-card" role="listitem">
                <div class="step-number">2</div>
                <h3 class="step-title">Deja tu solicitud</h3>
                <p class="step-desc">Agrega los productos al carrito y finaliza tu solicitud para que podamos confirmarte disponibilidad.</p>
                @auth('clients')
                    <a href="{{ route('clients.cart') }}" class="btn btn-secondary btn-lg step-cta">Ir al carrito</a>
                @else
                    <a href="{{ route('login.show') }}" class="btn btn-secondary btn-lg step-cta">Inicia sesión</a>
                @endauth
            </div>

            <div class="step-card" role="listitem">
                <div class="step-number">3</div>
                <h3 class="step-title">Retira en tienda</h3>
                <p class="step-desc">Te confirmamos cuando esté listo para retirar y coordinamos tu visita.</p>
                <div class="step-note">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>Retiro en tienda.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Marketing: Testimonios (estáticos por ahora) -->
<section class="testimonials-section" aria-label="Testimonios de clientes">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Clientes que confían en nosotros</h2>
            <p class="section-subtitle">Tu bici y tus componentes, con asesoría y preparación en tienda.</p>
        </div>

        <div class="testimonials-grid" role="list" aria-label="Lista de testimonios">
            <div class="testimonial-card" role="listitem">
                <div class="testimonial-stars" aria-hidden="true">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">“Me orientaron con la talla y ajustes. Retiré listo y pude salir ese mismo día.”</p>
                <p class="testimonial-author">Cliente verificado</p>
            </div>

            <div class="testimonial-card" role="listitem">
                <div class="testimonial-stars" aria-hidden="true">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">“Excelente atención en tienda. Respondieron mis dudas y dejaron todo preparado.”</p>
                <p class="testimonial-author">Cliente verificado</p>
            </div>

            <div class="testimonial-card" role="listitem">
                <div class="testimonial-stars" aria-hidden="true">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">“Encargo claro y puntual para retirar. Gran asesoría en componentes.”</p>
                <p class="testimonial-author">Cliente verificado</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="final-cta-section" aria-label="Llamado a la acción final">
    <div class="container">
        <div class="final-cta-card">
            <div>
                <h2 class="final-cta-title">¿Listo para tu próximo rodaje?</h2>
                <p class="final-cta-subtitle">Explora el catálogo y deja tu solicitud para que lo preparemos en tienda.</p>
            </div>
            <div class="final-cta-actions">
                <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-th" aria-hidden="true"></i>
                    Ver Catálogo
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Modal: select quantity before adding a product to cart -->
<!-- Product details are populated dynamically by JS -->
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