@php
    $stockLabel = $product->clientCatalogStockLabel();
    $stockQty = (int) ($product->stock_current ?? 0);
    $purchasable = $product->isPurchasableByClient();
    $isLow = $product->clientShowsLowStockWarning();
@endphp
<div @class([
    'product-detail-stock-card',
    'product-detail-stock-card--available' => $purchasable && ! $isLow,
    'product-detail-stock-card--low' => $purchasable && $isLow,
    'product-detail-stock-card--unavailable' => ! $purchasable,
]) role="status">
    @if($purchasable && $isLow)
        <span class="product-detail-stock-card__icon" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
        <div class="product-detail-stock-card__text">
            <strong class="product-detail-stock-card__title">Últimas unidades</strong>
            <span class="product-detail-stock-card__subtitle">Solo quedan {{ number_format($stockQty, 0, ',', '.') }} disponibles</span>
        </div>
    @elseif($purchasable)
        <span class="product-detail-stock-card__icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
        <div class="product-detail-stock-card__text">
            <strong class="product-detail-stock-card__title">En stock</strong>
            <span class="product-detail-stock-card__subtitle">{{ number_format($stockQty, 0, ',', '.') }} unidades disponibles</span>
        </div>
    @else
        <span class="product-detail-stock-card__icon" aria-hidden="true"><i class="fas fa-times-circle"></i></span>
        <div class="product-detail-stock-card__text">
            <strong class="product-detail-stock-card__title">{{ $stockLabel }}</strong>
            @if($stockLabel === 'Agotado')
                <span class="product-detail-stock-card__subtitle">Este producto no tiene unidades disponibles por ahora.</span>
            @else
                <span class="product-detail-stock-card__subtitle">No está disponible para compra en este momento.</span>
            @endif
        </div>
    @endif
</div>
