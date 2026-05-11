@php
    /** @var \App\Models\ProductReview $review */
    $verified = $verified ?? false;
    $mine = $mine ?? false;
    $c = $review->client;
    $author = $c
        ? trim(implode(' ', array_filter([$c->name, $c->first_surname, $c->second_surname])))
        : '';
    if ($author === '') {
        $author = 'Cliente';
    }
    $publishedAt = $review->created_at;
@endphp
<article @class(['product-review-item', 'product-review-item--mine' => $mine])>
    @if($mine)
        <div class="product-review-item__badge-mine">Tu reseña</div>
    @endif
    <div class="product-review-item__head">
        <strong class="product-review-item__author">{{ $author }}</strong>
        <time class="product-review-item__date" datetime="{{ $publishedAt?->toAtomString() }}">
            {{ $publishedAt?->format('d/m/Y H:i') }}
        </time>
    </div>
    <div class="product-review-item__stars-row">
        <div class="product-review-item__stars" role="img" aria-label="{{ (int) $review->stars }} de 5 estrellas">
            @for($i = 1; $i <= 5; $i++)
                <i class="{{ $i <= (int) $review->stars ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>
            @endfor
        </div>
        @if($verified)
            <span class="product-review-item__verified" title="Reseña de un comprador con pedido completado">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Compra verificada</span>
            </span>
        @endif
    </div>
</article>
