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

                <div class="product-detail-rating-summary">
                    @include('client.parts.product-stars-inline', [
                        'avgStars' => $averageStars ?? 0,
                        'reviewCount' => $totalReviewsCount,
                        'variant' => 'detail',
                        'emptyLabel' => 'Aún no hay valoraciones disponibles',
                    ])
                </div>

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

            @php
                $productRouteParams = ['id' => $product->product_id, 'slug' => $product->clientPublicSlug()];
                $maxDist = max(max($starDistribution), 1);
            @endphp

            @if($totalReviewsCount > 0)
                <p class="product-reviews-section-intro">
                    Calificación promedio: <strong>{{ number_format((float) $averageStars, 1) }}</strong>/5
                    · {{ $totalReviewsCount }} valoración{{ $totalReviewsCount === 1 ? '' : 'es' }} con reseña
                </p>

                <div class="product-star-distribution" aria-label="Distribución de calificaciones">
                    @for($level = 5; $level >= 1; $level--)
                        @php $cnt = (int) ($starDistribution[$level] ?? 0); @endphp
                        <div class="product-star-distribution__row">
                            <span class="product-star-distribution__label">{{ $level }} ★</span>
                            <div class="product-star-distribution__track" role="presentation">
                                <span class="product-star-distribution__fill" style="width: {{ (int) round(($cnt / $maxDist) * 100) }}%;"></span>
                            </div>
                            <span class="product-star-distribution__count">{{ $cnt }}</span>
                        </div>
                    @endfor
                </div>

                <nav class="product-reviews-sort" aria-label="Ordenar reseñas">
                    <span class="product-reviews-sort__label">Ordenar:</span>
                    <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'recent'])) }}"
                       @class(['product-reviews-sort__link', 'is-active' => $reviewsSort === 'recent'])>Más recientes</a>
                    <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'stars_high'])) }}"
                       @class(['product-reviews-sort__link', 'is-active' => $reviewsSort === 'stars_high'])>Mayor calificación</a>
                    <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'stars_low'])) }}"
                       @class(['product-reviews-sort__link', 'is-active' => $reviewsSort === 'stars_low'])>Menor calificación</a>
                </nav>

                @if($myHighlightedReview)
                    <div class="product-reviews-highlight" role="region" aria-label="Tu reseña">
                        @include('client.parts.product-review-row', [
                            'review' => $myHighlightedReview,
                            'verified' => $verifiedPurchaserIds->contains((int) $myHighlightedReview->client_id),
                            'mine' => true,
                        ])
                    </div>
                @endif

                <div class="product-reviews-list-wrap" role="region" aria-label="Reseñas de otros compradores">
                    @forelse($productReviewsPaginated as $review)
                        @include('client.parts.product-review-row', [
                            'review' => $review,
                            'verified' => $verifiedPurchaserIds->contains((int) $review->client_id),
                            'mine' => false,
                        ])
                    @empty
                        @if(! $myHighlightedReview)
                            <p class="product-reviews-empty-other">Aún no hay valoraciones disponibles.</p>
                        @endif
                    @endforelse
                </div>

                @if($productReviewsPaginated->hasPages())
                    <nav class="product-reviews-pagination" aria-label="Páginas de reseñas">
                        <div class="product-reviews-pagination__inner">
                            @if($productReviewsPaginated->onFirstPage())
                                <span class="product-reviews-pagination__btn is-disabled" aria-disabled="true">Anterior</span>
                            @else
                                <a class="product-reviews-pagination__btn" href="{{ $productReviewsPaginated->previousPageUrl() }}" rel="prev">Anterior</a>
                            @endif
                            <span class="product-reviews-pagination__meta">
                                {{ $productReviewsPaginated->firstItem() }}–{{ $productReviewsPaginated->lastItem() }}
                                de {{ $productReviewsPaginated->total() }}
                            </span>
                            @if(! $productReviewsPaginated->hasMorePages())
                                <span class="product-reviews-pagination__btn is-disabled" aria-disabled="true">Siguiente</span>
                            @else
                                <a class="product-reviews-pagination__btn" href="{{ $productReviewsPaginated->nextPageUrl() }}" rel="next">Siguiente</a>
                            @endif
                        </div>
                    </nav>
                @endif
            @else
                <p class="product-reviews-empty">Aún no hay valoraciones disponibles.</p>
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
                                @include('client.parts.product-stars-inline', [
                                    'avgStars' => (float) data_get($relRs, 'avg', 0),
                                    'reviewCount' => (int) data_get($relRs, 'count', 0),
                                    'variant' => 'related',
                                ])
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