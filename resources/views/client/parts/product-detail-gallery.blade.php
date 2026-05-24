@php
    $slideCount = count($carouselSlides ?? []);
    $showImagePlaceholder = $showImagePlaceholder ?? false;
@endphp
<div class="product-detail-gallery">
    <div @class([
        'product-detail-media',
        'product-detail-media--placeholder' => $showImagePlaceholder,
    ])>
        @if($showImagePlaceholder)
            @include('client.parts.product-detail-image-placeholder', [
                'product' => $product,
            ])
        @else
            <div class="product-carousel" id="product-carousel" data-slide-count="{{ $slideCount }}">
                <div class="carousel-viewport">
                    <div class="carousel-track" id="carousel-track">
                        @foreach($carouselSlides as $slide)
                            <div class="carousel-slide">
                                @include('client.parts.responsive-picture', [
                                    'desktopWebp' => $slide['desktopWebp'] ?? null,
                                    'mobileWebp' => $slide['mobileWebp'] ?? null,
                                    'fallback' => $slide['fallback'],
                                    'alt' => $product->name,
                                    'loading' => $loop->first ? 'eager' : 'lazy',
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($slideCount > 1)
                    <button class="carousel-btn carousel-btn--prev" id="carousel-prev" aria-label="Imagen anterior" disabled>
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="carousel-btn carousel-btn--next" id="carousel-next" aria-label="Imagen siguiente">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if(! $showImagePlaceholder && $slideCount > 0)
        <div class="product-detail-thumbs" id="product-detail-thumbs" role="list" aria-label="Miniaturas del producto">
            @foreach($carouselSlides as $i => $slide)
                <button type="button"
                        class="product-detail-thumb {{ $i === 0 ? 'is-active' : '' }}"
                        data-thumb-index="{{ $i }}"
                        role="listitem"
                        aria-label="Ver imagen {{ $i + 1 }}"
                        aria-current="{{ $i === 0 ? 'true' : 'false' }}">
                    @include('client.parts.responsive-picture', [
                        'desktopWebp' => $slide['desktopWebp'] ?? null,
                        'mobileWebp' => $slide['mobileWebp'] ?? null,
                        'fallback' => $slide['fallback'],
                        'alt' => '',
                        'loading' => 'lazy',
                    ])
                </button>
            @endforeach
        </div>
    @endif
</div>
