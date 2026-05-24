@php
    $parentCategory = $taxonomy['parentCategory'] ?? null;
    $subcategory = $taxonomy['subcategory'] ?? null;
    $catalogParentUrl = $taxonomy['catalogParentUrl'] ?? null;
    $catalogSubcategoryUrl = $taxonomy['catalogSubcategoryUrl'] ?? null;
@endphp
@if($parentCategory || $subcategory)
    <section class="product-detail-taxonomy" aria-labelledby="product-detail-taxonomy-heading">
        <h2 id="product-detail-taxonomy-heading" class="product-detail-section-title">Categoría</h2>
        <div class="product-detail-taxonomy__chips">
            @if($parentCategory && $catalogParentUrl)
                <a href="{{ $catalogParentUrl }}" class="product-detail-taxonomy-chip">
                    <span class="product-detail-taxonomy-chip__label">Categoría</span>
                    <span class="product-detail-taxonomy-chip__value">{{ $parentCategory->name }}</span>
                </a>
            @endif
            @if($subcategory && $catalogSubcategoryUrl)
                <a href="{{ $catalogSubcategoryUrl }}" class="product-detail-taxonomy-chip product-detail-taxonomy-chip--sub">
                    <span class="product-detail-taxonomy-chip__label">Subcategoría</span>
                    <span class="product-detail-taxonomy-chip__value">{{ $subcategory->name }}</span>
                </a>
            @endif
        </div>
    </section>
@endif
