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
    @vite([
        'resources/css/client/clients-page.css',
        'resources/css/client/product-badges.css',
        'resources/css/client/product-detail.css',
    ])
@endpush

@section('content')
@php
    use App\Support\ProductImageUrls;

    $legacyFallback = ProductImageUrls::fallbackUrl($product);
    $showImagePlaceholder = ProductImageUrls::usesPlaceholder($product);
    $carouselSlides = [];

    if (! $showImagePlaceholder) {
        if ($mainMedia = $product->getFirstMedia('main_image')) {
            $carouselSlides[] = ProductImageUrls::carouselSlide($mainMedia, $legacyFallback);
        }

        foreach ($product->getMedia('gallery') as $galleryMedia) {
            if (ProductImageUrls::mediaIsDisplayable($galleryMedia)) {
                $carouselSlides[] = ProductImageUrls::carouselSlide($galleryMedia, $galleryMedia->getUrl());
            }
        }

        if ($carouselSlides === [] && ProductImageUrls::legacyImageIsDisplayable($product->image)) {
            $carouselSlides[] = [
                'fallback' => asset('assets/images/products/'.$product->image),
                'desktopWebp' => null,
                'mobileWebp' => null,
            ];
        }
    }

    if ($carouselSlides === [] && ProductImageUrls::usesPlaceholder($product)) {
        $showImagePlaceholder = true;
    }

    $hasDescription = filled($product->description);
    $hasSpecs = $product->classificationValues->isNotEmpty() || $hasDescription;
    $hasRelated = $relatedProducts->count() > 0;
    $defaultTab = $hasDescription
        ? 'description'
        : ($product->classificationValues->isNotEmpty() ? 'specs' : 'reviews');
    if (request()->has('reviews_sort') || request()->has('review_filter') || request()->has('page')) {
        $defaultTab = 'reviews';
    }
@endphp
<div class="product-detail-container product-detail-page">
    <div class="container">
        @php
            $productBackUrl = ($taxonomy['catalogSubcategoryUrl'] ?? null)
                ?: (($taxonomy['catalogParentUrl'] ?? null) ?: route('clients.catalog'));
        @endphp

        @include('client.parts.mobile-back-nav', [
            'backUrl' => $productBackUrl,
            'backLabel' => 'Volver al catálogo',
        ])

        <nav class="breadcrumb product-detail-breadcrumb" aria-label="Ruta de navegación">
            <a href="{{ route('clients.home') }}">Inicio</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('clients.catalog') }}">Catálogo</a>
            @if(($taxonomy['parentCategory'] ?? null) && ($taxonomy['catalogParentUrl'] ?? null))
                <span aria-hidden="true">/</span>
                <a href="{{ $taxonomy['catalogParentUrl'] }}">{{ $taxonomy['parentCategory']->name }}</a>
            @endif
            @if(($taxonomy['subcategory'] ?? null) && ($taxonomy['catalogSubcategoryUrl'] ?? null))
                <span aria-hidden="true">/</span>
                <a href="{{ $taxonomy['catalogSubcategoryUrl'] }}">{{ $taxonomy['subcategory']->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $product->name }}</span>
        </nav>

        <div class="product-detail-layout product-detail-hero">
            <div class="product-detail-image product-detail-hero__gallery">
                @include('client.parts.product-detail-gallery')
            </div>
            <div class="product-detail-info product-detail-hero__buy">
                @include('client.parts.product-detail-purchase-panel')
            </div>
        </div>

        <div class="product-detail-tabs"
             id="product-detail-tabs"
             data-default-tab="{{ $defaultTab }}">
            <div class="product-detail-tabs__nav" role="tablist" aria-label="Información del producto">
                <button type="button"
                        role="tab"
                        class="product-detail-tabs__btn"
                        data-tab="description"
                        aria-controls="product-tab-description"
                        id="product-tab-btn-description">
                    Descripción
                </button>
                <button type="button"
                        role="tab"
                        class="product-detail-tabs__btn"
                        data-tab="specs"
                        aria-controls="product-tab-specs"
                        id="product-tab-btn-specs">
                    Especificaciones
                </button>
                <button type="button"
                        role="tab"
                        class="product-detail-tabs__btn"
                        data-tab="reviews"
                        aria-controls="product-tab-reviews"
                        id="product-tab-btn-reviews">
                    Reseñas
                    @if($totalReviewsCount > 0)
                        <span class="product-detail-tabs__count">{{ $totalReviewsCount }}</span>
                    @endif
                </button>
                @if($hasRelated)
                    <button type="button"
                            role="tab"
                            class="product-detail-tabs__btn"
                            data-tab="related"
                            aria-controls="product-tab-related"
                            id="product-tab-btn-related">
                        Relacionados
                    </button>
                @endif
            </div>

            <div class="product-detail-tabs__panels">
                <section id="product-tab-description"
                         class="product-detail-tab-panel"
                         data-panel="description"
                         role="tabpanel"
                         aria-labelledby="product-tab-btn-description"
                         @if($defaultTab !== 'description') hidden @endif>
                    @include('client.parts.product-detail-description-card')
                </section>

                <section id="product-tab-specs"
                         class="product-detail-tab-panel"
                         data-panel="specs"
                         role="tabpanel"
                         aria-labelledby="product-tab-btn-specs"
                         @if($defaultTab !== 'specs') hidden @endif>
                    <article class="product-detail-card">
                        <h2 class="product-detail-card__title">Características técnicas</h2>
                        @include('client.parts.product-detail-specs')
                    </article>
                </section>

                <section id="product-tab-reviews"
                         class="product-detail-tab-panel product-detail-reviews"
                         data-panel="reviews"
                         role="tabpanel"
                         aria-labelledby="product-tab-btn-reviews"
                         @if($defaultTab !== 'reviews') hidden @endif>
                    @include('client.parts.product-detail-reviews-section')
                </section>

                @if($hasRelated)
                    <section id="product-tab-related"
                             class="product-detail-tab-panel product-detail-related"
                             data-panel="related"
                             role="tabpanel"
                             aria-labelledby="product-tab-btn-related"
                             @if($defaultTab !== 'related') hidden @endif>
                        <h2 class="product-detail-card__title">Productos relacionados</h2>
                        @include('client.parts.product-detail-related-section')
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @auth('clients')
        @php
            $favoriteToggleUrl = \Illuminate\Support\Facades\Route::has('clients.favorites.toggle')
                ? route('clients.favorites.toggle')
                : url('/favorites/toggle');
        @endphp
        <script>
            window.catalogFavoriteConfig = {
                toggleUrl: @json($favoriteToggleUrl),
            };
        </script>
    @else
        <script>
            window.catalogFavoriteConfig = {
                loginUrl: @json(route('login.show')),
            };
        </script>
    @endauth
    @vite(['resources/js/client/clients-product.js'])
@endpush
