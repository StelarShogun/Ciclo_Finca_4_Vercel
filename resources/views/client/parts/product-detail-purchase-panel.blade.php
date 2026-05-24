<aside class="product-detail-purchase-panel" aria-label="Comprar producto">
    @include('client.parts.product-detail-badges')

    <h1 class="product-detail-name">{{ $product->name }}</h1>

    @if($product->clientCatalogAssignedSku())
        <p class="product-detail-sku">SKU: {{ $product->clientCatalogAssignedSku() }}</p>
    @endif

    <div class="product-detail-rating-summary">
        @include('client.parts.product-stars-inline', [
            'avgStars' => $averageStars ?? 0,
            'reviewCount' => $totalReviewsCount,
            'variant' => 'detail',
            'emptyLabel' => 'Aún no hay valoraciones',
        ])
    </div>

    <div class="product-detail-price" data-unit-price="{{ (int) $product->sale_price }}">
        <span class="product-detail-price__label">Precio</span>
        <span class="product-detail-price__amount">₡{{ number_format($product->sale_price, 0, ',', '.') }}</span>
    </div>

    @include('client.parts.product-detail-stock')
    @include('client.parts.product-detail-actions')
    @include('client.parts.product-detail-trust')
</aside>
