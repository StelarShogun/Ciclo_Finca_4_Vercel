@if(session('status'))
    <div class="alert alert-success product-detail-alert">{{ session('status') }}</div>
@endif

@if($errors->has('review'))
    <div class="alert alert-danger product-detail-alert">{{ $errors->first('review') }}</div>
@endif

@include('client.parts.product-detail-reviews-header')

@auth('clients')
    @if($clientCanReview)
        <form method="POST"
              id="product-review-form"
              action="{{ route('clients.products.review.store', ['product' => $product->product_id]) }}"
              class="product-detail-review-form">
            @csrf
            <label for="stars" class="product-detail-review-form__label">
                <strong>Escribir reseña</strong> (1 a 5 estrellas)
            </label>
            <div class="product-detail-review-form__row">
                <select id="stars" name="stars" class="form-control product-detail-review-form__select">
                    <option value="">Selecciona una calificación</option>
                    @for($star = 1; $star <= 5; $star++)
                        <option value="{{ $star }}" @selected((int) old('stars', $clientReview->stars ?? 0) === $star)>
                            {{ $star }} estrella{{ $star > 1 ? 's' : '' }}
                        </option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary">
                    {{ $clientReview ? 'Actualizar reseña' : 'Publicar reseña' }}
                </button>
            </div>
            @error('stars')
                <small class="product-detail-review-form__error">{{ $message }}</small>
            @enderror
        </form>
    @endif
@endauth

@php
    $productRouteParams = ['id' => $product->product_id, 'slug' => $product->clientPublicSlug()];
@endphp

@if($totalReviewsCount > 0)
    <div class="product-reviews-toolbar">
        <div class="product-reviews-toolbar__card">
            <div class="product-reviews-toolbar__head">
                <span class="product-reviews-toolbar__head-icon" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>
                <span class="product-reviews-toolbar__head-text">Ver y ordenar reseñas</span>
            </div>
            <div class="product-reviews-toolbar__body">
                <form method="get"
                      action="{{ route('clients.product', $productRouteParams) }}"
                      class="product-reviews-filter"
                      id="product-reviews-filter-form">
                    <input type="hidden" name="reviews_sort" value="{{ $reviewsSort }}">
                    <label for="review_filter" class="product-reviews-filter__label">
                        <i class="fas fa-star-half-alt product-reviews-filter__label-icon" aria-hidden="true"></i>
                        Filtrar por calificación
                    </label>
                    <div class="product-reviews-filter__control">
                        <select name="review_filter"
                                id="review_filter"
                                class="product-reviews-filter__select"
                                onchange="this.form.submit()">
                            <option value="all" @selected($reviewFilter === 'all')>
                                Todas las reseñas ({{ $totalReviewsCount }})
                            </option>
                            @for($level = 5; $level >= 1; $level--)
                                @php $cnt = (int) ($starDistribution[$level] ?? 0); @endphp
                                <option value="{{ $level }}" @selected((string) $reviewFilter === (string) $level)>
                                    {{ $level }} {{ $level === 1 ? 'estrella' : 'estrellas' }} ({{ $cnt }})
                                </option>
                            @endfor
                        </select>
                    </div>
                </form>

                <nav class="product-reviews-sort" aria-label="Ordenar reseñas">
                    <span class="product-reviews-sort__label">
                        <i class="fas fa-sort-amount-down product-reviews-sort__label-icon" aria-hidden="true"></i>
                        Ordenar por
                    </span>
                    <div class="product-reviews-sort__chips">
                        <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'recent', 'review_filter' => $reviewFilter])) }}#product-detail-tabs"
                           @class(['product-reviews-sort__chip', 'is-active' => $reviewsSort === 'recent'])>Más recientes</a>
                        <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'stars_high', 'review_filter' => $reviewFilter])) }}#product-detail-tabs"
                           @class(['product-reviews-sort__chip', 'is-active' => $reviewsSort === 'stars_high'])>Mayor calificación</a>
                        <a href="{{ route('clients.product', array_merge($productRouteParams, ['reviews_sort' => 'stars_low', 'review_filter' => $reviewFilter])) }}#product-detail-tabs"
                           @class(['product-reviews-sort__chip', 'is-active' => $reviewsSort === 'stars_low'])>Menor calificación</a>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    @if($showMyHighlightedReview)
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
            @if($reviewFilter !== 'all')
                <p class="product-reviews-empty-other">No hay reseñas con esta calificación.</p>
            @elseif(! $showMyHighlightedReview)
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
                    <a class="product-reviews-pagination__btn" href="{{ $productReviewsPaginated->previousPageUrl() }}#product-detail-tabs" rel="prev">Anterior</a>
                @endif
                <span class="product-reviews-pagination__meta">
                    {{ $productReviewsPaginated->firstItem() }}–{{ $productReviewsPaginated->lastItem() }}
                    de {{ $productReviewsPaginated->total() }}
                </span>
                @if(! $productReviewsPaginated->hasMorePages())
                    <span class="product-reviews-pagination__btn is-disabled" aria-disabled="true">Siguiente</span>
                @else
                    <a class="product-reviews-pagination__btn" href="{{ $productReviewsPaginated->nextPageUrl() }}#product-detail-tabs" rel="next">Siguiente</a>
                @endif
            </div>
        </nav>
    @endif
@endif
