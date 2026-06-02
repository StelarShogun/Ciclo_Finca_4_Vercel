@php
    $currentStep = $currentStep ?? 2;
    $steps = [
        1 => ['label' => 'Productos', 'icon' => 'fa-bicycle'],
        2 => ['label' => 'Carrito', 'icon' => 'fa-cart-shopping'],
        3 => ['label' => 'Confirmación', 'icon' => 'fa-check'],
    ];
@endphp

<nav class="cf4-checkout-progress" aria-label="Progreso del pedido">
    <ol class="cf4-checkout-progress__list">
        @foreach($steps as $stepNumber => $step)
            @php
                $isComplete = $stepNumber < $currentStep;
                $isCurrent = $stepNumber === $currentStep;
            @endphp
            <li @class([
                'cf4-checkout-progress__item',
                'is-complete' => $isComplete,
                'is-current' => $isCurrent,
            ])>
                <span class="cf4-checkout-progress__marker" aria-hidden="true">
                    @if($isComplete)
                        <i class="fas fa-check"></i>
                    @else
                        <i class="fas {{ $step['icon'] }}"></i>
                    @endif
                </span>
                <span class="cf4-checkout-progress__label">{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>
</nav>
