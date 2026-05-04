@extends('client.layouts.app')

@section('title', $product->name . ' - Ciclo Finca 4')

@push('meta')
@php
    $canonicalProductUrl = $product->clientProductUrl();
    $metaDesc = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($product->description ?? ''))), 155);
    if ($metaDesc === '') {
        $metaDesc = $product->name.' — Ciclo Finca 4';
    }
    $ogImage = $product->getFirstMediaUrl('main_image') ?: asset('assets/images/products/'.($product->image ?? 'default.png'));
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

        @php
    $mainUrl     = $product->getFirstMediaUrl('main_image');
    $galleryUrls = $product->getMedia('gallery')->map(fn($m) => $m->getUrl())->toArray();
    $fallbackUrl = asset('assets/images/products/' . ($product->image ?? 'default.png'));
    $allImages   = array_values(array_filter(array_merge($mainUrl ? [$mainUrl] : [], $galleryUrls)));
    if (empty($allImages)) { $allImages = [$fallbackUrl]; }
@endphp

        <div class="product-detail-layout">
            <div class="product-detail-image">
                <div class="product-carousel" id="product-carousel">
                    <div class="carousel-viewport">
                        <div class="carousel-track" id="carousel-track">
                            @foreach($allImages as $imgUrl)
                                <div class="carousel-slide">
                                    <img src="{{ $imgUrl }}" alt="{{ $product->name }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if(count($allImages) > 1)
                        <button class="carousel-btn carousel-btn--prev" id="carousel-prev" aria-label="Imagen anterior" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-btn carousel-btn--next" id="carousel-next" aria-label="Imagen siguiente">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="carousel-dots" id="carousel-dots">
                            @foreach($allImages as $i => $imgUrl)
                                <button class="carousel-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}" aria-label="Imagen {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="product-detail-info">
                <div class="product-detail-category">{{ $product->category->name ?? 'Uncategorized' }}</div>
                <h1 class="product-detail-name">{{ $product->name }}</h1>

                @if($productReviews->isNotEmpty() && $averageStars !== null)
                    <div class="product-detail-rating-summary">
                        @include('client.parts.product-stars-inline', [
                            'avgStars' => $averageStars,
                            'reviewCount' => $productReviews->count(),
                            'variant' => 'detail',
                        ])
                    </div>
                @endif

                @if($product->description)
                    <div class="product-detail-description">
                        <p>{{ $product->description }}</p>
                    </div>
                @endif

                @if($product->classificationValues->isNotEmpty())
                    <ul class="product-detail-classifications" style="list-style:none;padding:0;margin:0.75rem 0;font-size:0.95rem;">
                        @foreach($product->classificationValues as $cv)
                            <li style="margin-bottom:0.35rem;">
                                <strong>{{ optional($cv->dimension)->label ?? '—' }}:</strong> {{ $cv->value }}
                            </li>
                        @endforeach
                    </ul>
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

        <section class="related-products" style="margin-top: 2rem;">
            <h2 class="section-title">Reseñas del producto</h2>

            @if(session('status'))
                <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('status') }}</div>
            @endif

            @if($errors->has('review'))
                <div class="alert alert-danger" style="margin-bottom:1rem;">{{ $errors->first('review') }}</div>
            @endif

            @auth('clients')
                @if($clientCanReview)
                    <form method="POST" action="{{ route('clients.products.review.store', ['product' => $product->product_id]) }}" style="margin-bottom:1rem;">
                        @csrf
                        <label for="stars"><strong>Tu reseña (1 a 5 estrellas)</strong></label>
                        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;margin-top:0.5rem;">
                            <select id="stars" name="stars" class="form-control" style="max-width:220px;">
                                <option value="">Selecciona una calificación</option>
                                @for($star = 1; $star <= 5; $star++)
                                    <option value="{{ $star }}" @selected((int) old('stars', $clientReview->stars ?? 0) === $star)>
                                        {{ $star }} estrella{{ $star > 1 ? 's' : '' }}
                                    </option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-primary">
                                {{ $clientReview ? 'Actualizar reseña' : 'Guardar reseña' }}
                            </button>
                        </div>
                        @error('stars')
                            <small style="color:#d9534f;display:block;margin-top:0.5rem;">{{ $message }}</small>
                        @enderror
                    </form>
                @else
                    <p style="margin-bottom:1rem;">Podrás reseñar este producto cuando tengas una compra completada del mismo.</p>
                @endif
            @else
                <p style="margin-bottom:1rem;">
                    <a href="{{ route('login.show') }}">Inicia sesión</a> para reseñar productos que hayas comprado.
                </p>
            @endauth

            @if($productReviews->isNotEmpty())
                <p class="product-reviews-section-intro">
                    Calificación promedio: <strong>{{ number_format((float) $averageStars, 1) }}</strong>/5
                    · {{ $productReviews->count() }} reseña{{ $productReviews->count() === 1 ? '' : 's' }}
                    (más recientes primero)
                </p>
                <div class="product-reviews-list-scroll" role="region" aria-label="Lista de reseñas">
                    @foreach($productReviews as $review)
                        @php
                            $c = $review->client;
                            $author = $c
                                ? trim(implode(' ', array_filter([$c->name, $c->first_surname, $c->second_surname])))
                                : '';
                            if ($author === '') {
                                $author = 'Cliente';
                            }
                            $publishedAt = $review->created_at;
                        @endphp
                        <article class="product-review-item">
                            <div class="product-review-item__head">
                                <strong class="product-review-item__author">{{ $author }}</strong>
                                <time class="product-review-item__date" datetime="{{ $publishedAt?->toAtomString() }}">
                                    {{ $publishedAt?->format('d/m/Y H:i') }}
                                </time>
                            </div>
                            <div class="product-review-item__stars" role="img" aria-label="{{ (int) $review->stars }} de 5 estrellas">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="{{ $i <= (int) $review->stars ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>
                                @endfor
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p>Aún no hay reseñas para este producto.</p>
            @endif
        </section>

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
                                    @php $relatedImgUrl = $related->getFirstMediaUrl('main_image') ?: asset('assets/images/products/' . ($related->image ?? 'default.png')); @endphp
                                    <img src="{{ $relatedImgUrl }}" alt="{{ $related->name }}"
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
                                @php $relRs = $productReviewStats[(int) $related->product_id] ?? null; @endphp
                                @if($relRs && ($relRs['count'] ?? 0) > 0)
                                    @include('client.parts.product-stars-inline', [
                                        'avgStars' => $relRs['avg'],
                                        'reviewCount' => $relRs['count'],
                                        'variant' => 'related',
                                    ])
                                @endif
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