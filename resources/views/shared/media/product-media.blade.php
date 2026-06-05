@php
    use App\Services\Media\ProductImageUrls;

    $variant = $variant ?? 'card';
    $alt = $alt ?? $product->name;
    $loading = $loading ?? 'lazy';
    $sizes = $sizes ?? '(max-width: 767px) 45vw, 240px';
    $href = $href ?? null;
    $linkClass = $linkClass ?? 'product-image__link';
    $imgClass = $imgClass ?? ($variant === 'thumb-card' ? 'product-card-image' : null);
    $usesPlaceholder = ProductImageUrls::usesPlaceholder($product);
    $iconClass = ProductImageUrls::placeholderIconClass($product);
    $isCompactThumb = in_array($variant, ['thumb-table', 'thumb-card', 'thumb-invoice', 'cart', 'favorite', 'suggestion'], true);
    $compactThumbSize = match ($variant) {
        'thumb-table', 'thumb-invoice' => 48,
        'thumb-card' => 60,
        'cart' => 96,
        'favorite' => 74,
        'suggestion' => 30,
        default => 48,
    };
@endphp
@if($usesPlaceholder)
    @if($href)
        <a href="{{ $href }}" class="{{ $linkClass }}" aria-label="{{ $alt }}">
            <div @class(['product-media-placeholder', 'product-media-placeholder--' . $variant]) role="img" aria-label="Sin imagen: {{ $alt }}">
                <i class="{{ $iconClass }}" aria-hidden="true"></i>
            </div>
        </a>
    @else
        <div @class(['product-media-placeholder', 'product-media-placeholder--' . $variant]) role="img" aria-label="Sin imagen: {{ $alt }}">
            <i class="{{ $iconClass }}" aria-hidden="true"></i>
        </div>
    @endif
@elseif($isCompactThumb)
    <img src="{{ ProductImageUrls::fallbackUrl($product) }}"
         alt="{{ $alt }}"
         @if($imgClass) class="{{ $imgClass }}" @endif
         width="{{ $compactThumbSize }}"
         height="{{ $compactThumbSize }}"
         loading="lazy"
         decoding="async">
@else
    @php $cardImg = ProductImageUrls::cardPicture($product); @endphp
    @if($href)
        <a href="{{ $href }}" class="{{ $linkClass }}" aria-label="{{ $alt }}">
            @include('shared.media.responsive-picture', [
                'desktopWebp' => $cardImg['desktopWebp'],
                'mobileWebp' => $cardImg['mobileWebp'],
                'fallback' => $cardImg['fallback'],
                'alt' => $alt,
                'loading' => $loading,
                'sizes' => $sizes,
            ])
        </a>
    @else
        @include('shared.media.responsive-picture', [
            'desktopWebp' => $cardImg['desktopWebp'],
            'mobileWebp' => $cardImg['mobileWebp'],
            'fallback' => $cardImg['fallback'],
            'alt' => $alt,
            'loading' => $loading,
            'sizes' => $sizes,
        ])
    @endif
@endif
