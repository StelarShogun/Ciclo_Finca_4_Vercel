<picture>
    @if(!empty($mobileWebp))
        <source type="image/webp" media="(max-width: 767px)" srcset="{{ $mobileWebp }}">
    @endif

    @if(!empty($desktopWebp))
        <source type="image/webp" srcset="{{ $desktopWebp }}">
    @endif

    <img
        src="{{ $fallback }}"
        alt="{{ $alt ?? '' }}"
        @if(!empty($class)) class="{{ $class }}" @endif
        @if(!empty($width)) width="{{ $width }}" @endif
        @if(!empty($height)) height="{{ $height }}" @endif
        @if(!empty($loading)) loading="{{ $loading }}" @endif
        decoding="async"
        @if(!empty($fetchpriority)) fetchpriority="{{ $fetchpriority }}" @endif
        data-fallback-src="{{ asset('favicon.svg') }}"
        onerror="this.src=this.dataset.fallbackSrc;">
</picture>
