@php
    $isInactive = ($product->status ?? '') === 'inactive';
@endphp
@if ($isInactive)
    <button type="button"
            class="action-btn activate"
            data-action="activate"
            data-product-id="{{ $product->product_id }}"
            data-product-name="{{ $product->name }}"
            title="Reactivar producto">
        <i class="fas fa-check-circle"></i>
    </button>
@else
    <button type="button"
            class="action-btn delete"
            data-action="deactivate"
            data-product-id="{{ $product->product_id }}"
            data-product-name="{{ $product->name }}"
            title="Desactivar producto">
        <i class="fas fa-ban"></i>
    </button>
@endif
