<div class="product-detail-reviews-hero">
    <div class="product-detail-reviews-hero__score">
        <span class="product-detail-reviews-hero__average" aria-hidden="true">
            {{ number_format((float) ($averageStars ?? 0), 1) }}
        </span>
        <div class="product-detail-reviews-hero__stars">
            @include('client.parts.product-stars-inline', [
                'avgStars' => $averageStars ?? 0,
                'reviewCount' => $totalReviewsCount,
                'variant' => 'detail',
                'emptyLabel' => 'Sin valoraciones',
            ])
        </div>
        <p class="product-detail-reviews-hero__count">
            @if(($totalReviewsCount ?? 0) > 0)
                {{ $totalReviewsCount }} {{ $totalReviewsCount === 1 ? 'reseña' : 'reseñas' }}
            @else
                Aún no hay reseñas
            @endif
        </p>
    </div>

    <div class="product-detail-reviews-hero__aside">
        @auth('clients')
            @if($clientCanReview)
                <a href="#product-review-form" class="btn btn-primary product-detail-reviews-hero__cta">
                    Escribir reseña
                </a>
                <p class="product-detail-reviews-hero__note">
                    Compartí tu experiencia con este producto.
                </p>
            @else
                <p class="product-detail-reviews-hero__note product-detail-reviews-hero__note--muted">
                    Solo clientes que hayan comprado este producto pueden dejar una reseña.
                </p>
            @endif
        @else
            <a href="{{ route('login.show') }}" class="btn btn-outline product-detail-reviews-hero__cta">
                Iniciar sesión para reseñar
            </a>
            <p class="product-detail-reviews-hero__note">
                Solo clientes que hayan comprado este producto pueden dejar una reseña.
            </p>
        @endauth

        @if(($totalReviewsCount ?? 0) === 0)
            <div class="product-detail-reviews-hero__empty" role="status">
                <i class="far fa-comment-dots" aria-hidden="true"></i>
                <p>Todavía no hay valoraciones públicas. Sé el primero en compartir tu opinión después de tu compra.</p>
            </div>
        @endif
    </div>
</div>
