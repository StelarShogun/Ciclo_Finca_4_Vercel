@php
    $specValues = $product->classificationValues;
    $hasClassifications = $specValues->isNotEmpty();
    $descriptionExcerpt = trim((string) ($product->description ?? ''));
    $showDescriptionChip = ! $hasClassifications && $descriptionExcerpt !== '';
@endphp
@if($hasClassifications || $showDescriptionChip)
    <div class="product-detail-specs__chips">
        @foreach($specValues as $cv)
            <span class="product-detail-spec-chip">
                @if(optional($cv->dimension)->label)
                    <span class="product-detail-spec-chip__label">{{ $cv->dimension->label }}</span>
                @endif
                <span class="product-detail-spec-chip__value">{{ $cv->value }}</span>
            </span>
        @endforeach
        @if($showDescriptionChip)
            <span class="product-detail-spec-chip product-detail-spec-chip--highlight">
                <span class="product-detail-spec-chip__value">{{ \Illuminate\Support\Str::limit($descriptionExcerpt, 120) }}</span>
            </span>
        @endif
    </div>
@else
    <p class="product-detail-specs__empty">Pronto publicaremos las características técnicas de este producto.</p>
@endif
