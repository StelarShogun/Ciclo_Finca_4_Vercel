@php
    use App\Support\ClientPickupPolicy;

    $variant = $variant ?? 'default';
    $heading = $heading ?? 'Retiro en tienda';
@endphp

<aside @class([
    'cf4-pickup-policy',
    'cf4-pickup-policy--compact' => $variant === 'compact',
    'cf4-pickup-policy--highlight' => $variant === 'highlight',
]) aria-label="Política de retiro en tienda">
    <div class="cf4-pickup-policy__head">
        <i class="fas fa-store" aria-hidden="true"></i>
        <strong>{{ $heading }}</strong>
    </div>
    <p class="cf4-pickup-policy__text">{{ ClientPickupPolicy::fullNotice() }}</p>
</aside>
