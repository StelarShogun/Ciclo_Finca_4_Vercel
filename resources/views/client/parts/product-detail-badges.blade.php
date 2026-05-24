@php
    $stockLabel = $product->clientCatalogStockLabel();
    $brand = $primaryBrand ?? null;
    $parentCategory = $taxonomy['parentCategory'] ?? null;
    $subcategory = $taxonomy['subcategory'] ?? null;
    $catalogParentUrl = $taxonomy['catalogParentUrl'] ?? null;
    $catalogSubcategoryUrl = $taxonomy['catalogSubcategoryUrl'] ?? null;

    $stockModifier = match ($stockLabel) {
        'En stock' => 'stock',
        'Últimas unidades' => 'low-stock',
        'Agotado' => 'out-stock',
        default => 'unavailable',
    };
@endphp
<div class="product-detail-badges" aria-label="Información rápida del producto">
    @if($parentCategory && $catalogParentUrl)
        <a href="{{ $catalogParentUrl }}"
           class="product-badge product-badge--category product-detail-badge product-detail-badge--category">
            <i class="fas fa-layer-group product-badge__icon" aria-hidden="true"></i>
            {{ $parentCategory->name }}
        </a>
    @endif

    @if($subcategory && $catalogSubcategoryUrl)
        <a href="{{ $catalogSubcategoryUrl }}"
           class="product-badge product-badge--subcategory product-detail-badge product-detail-badge--subcategory">
            <i class="fas fa-tag product-badge__icon" aria-hidden="true"></i>
            {{ $subcategory->name }}
        </a>
    @endif

    @if($brand)
        <a href="{{ $catalogBrandUrl }}"
           class="product-badge product-badge--brand product-detail-badge product-detail-badge--brand">
            <i class="fas fa-tag product-badge__icon" aria-hidden="true"></i>
            {{ $brand->name }}
        </a>
    @endif

    <span @class([
        'product-badge',
        'product-badge--' . $stockModifier,
        'product-detail-badge',
        'product-detail-badge--stock',
        'product-detail-badge--stock-available' => $stockLabel === 'En stock',
        'product-detail-badge--stock-low' => $stockLabel === 'Últimas unidades',
        'product-detail-badge--stock-out' => $stockLabel === 'Agotado',
        'product-detail-badge--stock-na' => $stockLabel === 'No disponible',
    ])>
        @if($stockLabel === 'En stock')
            <i class="fas fa-check-circle product-badge__icon" aria-hidden="true"></i>
        @elseif($stockLabel === 'Últimas unidades')
            <i class="fas fa-exclamation-triangle product-badge__icon" aria-hidden="true"></i>
        @else
            <i class="fas fa-times-circle product-badge__icon" aria-hidden="true"></i>
        @endif
        {{ $stockLabel }}
    </span>

    @if($product->is_featured)
        <span class="product-badge product-badge--featured product-detail-badge product-detail-badge--featured">
            <i class="fas fa-star product-badge__icon" aria-hidden="true"></i>
            Destacado
        </span>
    @endif

    @if($isNoveltyProduct ?? false)
        <span class="product-badge product-badge--new product-detail-badge product-detail-badge--novelty">
            <i class="fas fa-bolt product-badge__icon" aria-hidden="true"></i>
            Novedad
        </span>
    @endif
</div>
