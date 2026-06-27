@if (($lowStockProducts ?? 0) > 0)
    <div id="low-stock-toast" class="ls-toast ls-toast--visible" role="alert" aria-live="assertive">
        <div class="ls-toast__icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="ls-toast__body">
            <strong class="ls-toast__title">Alerta de inventario</strong>
            <p class="ls-toast__msg">
                {{ $lowStockProducts }} producto{{ $lowStockProducts > 1 ? 's' : '' }}
                {{ $lowStockProducts > 1 ? 'están' : 'está' }} por debajo del stock mínimo configurado.
            </p>
            <a href="{{ route('inventory') }}" class="ls-toast__link">
                Ver inventario <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <button class="ls-toast__close" id="close-low-stock-toast" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif
