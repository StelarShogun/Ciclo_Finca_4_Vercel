@php
    use App\Support\ProductImageUrls;

    $variant = $variant ?? 'card';
    $alt = $alt ?? $product->name;
    $loading = $loading ?? 'lazy';
    $sizes = $sizes ?? '(max-width: 767px) 45vw, 240px';
    $href = $href ?? null;
    $linkClass = $linkClass ?? 'product-image__link';
    $imgClass = $imgClass ?? ($variant === 'thumb-card' ? 'product-card-image' : null);
    $usesPlaceholder = ProductImageUrls::usesPlaceholder($product);
    $iconClass = ProductImageUrls::placeholderIconClass($product);
    $isAdminThumb = in_array($variant, ['thumb-table', 'thumb-card'], true);
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
@elseif($isAdminThumb)
    <img src="{{ ProductImageUrls::fallbackUrl($product) }}"
         alt="{{ $alt }}"
         @if($imgClass) class="{{ $imgClass }}" @endif
         width="{{ $variant === 'thumb-table' ? 48 : 60 }}"
         height="{{ $variant === 'thumb-table' ? 48 : 60 }}"
         loading="lazy"
         decoding="async">
@else
    @php $cardImg = ProductImageUrls::cardPicture($product); @endphp
    @if($href)
        <a href="{{ $href }}" class="{{ $linkClass }}" aria-label="{{ $alt }}">
            @include('client.parts.responsive-picture', [
                'desktopWebp' => $cardImg['desktopWebp'],
                'mobileWebp' => $cardImg['mobileWebp'],
                'fallback' => $cardImg['fallback'],
                'alt' => $alt,
                'loading' => $loading,
                'sizes' => $sizes,
            ])
        </a>
    @else
        @include('client.parts.responsive-picture', [
            'desktopWebp' => $cardImg['desktopWebp'],
            'mobileWebp' => $cardImg['mobileWebp'],
            'fallback' => $cardImg['fallback'],
            'alt' => $alt,
            'loading' => $loading,
            'sizes' => $sizes,
        ])
    @endif
@endif
