{{-- $avgStars, $reviewCount, optional $variant: card|detail|related --}}
@php
    $avgStars = (float) ($avgStars ?? 0);
    $reviewCount = (int) ($reviewCount ?? 0);
    $variant = $variant ?? 'card';
    $emptyLabel = $emptyLabel ?? 'Producto no reseñado aún';
@endphp
<div @class([
    'product-stars-inline',
    'product-stars-inline--'.$variant,
    'product-stars-inline--empty' => $reviewCount === 0,
])
    role="img"
    @if($reviewCount > 0)
        aria-label="Promedio {{ number_format($avgStars, 1) }} de 5 según {{ $reviewCount }} reseña{{ $reviewCount === 1 ? '' : 's' }}"
    @else
        aria-label="{{ $emptyLabel }}. Sin valoración."
    @endif>
    @if($reviewCount > 0)
        <span class="product-stars-inline__icons" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                @if($avgStars >= $i)
                    <i class="fas fa-star"></i>
                @elseif($avgStars >= $i - 0.5)
                    <i class="fas fa-star-half-alt"></i>
                @else
                    <i class="far fa-star"></i>
                @endif
            @endfor
        </span>
        @if($variant === 'detail')
            <span class="product-stars-inline__meta">
                <strong>{{ number_format($avgStars, 1) }}</strong>/5
                <span class="product-stars-inline__count">· {{ $reviewCount }} reseña{{ $reviewCount === 1 ? '' : 's' }}</span>
            </span>
        @else
            <span class="product-stars-inline__meta">({{ $reviewCount }})</span>
        @endif
    @else
        <span class="product-stars-inline__icons product-stars-inline__icons--empty" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                <i class="far fa-star"></i>
            @endfor
        </span>
        <span class="product-stars-inline__label-empty">{{ $emptyLabel }}</span>
    @endif
</div>
