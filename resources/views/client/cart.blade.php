@extends('client.layouts.app')

@section('title', 'Carrito de Compras - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<section class="cart-shell" aria-labelledby="cart-page-title">
    <header class="cart-hero">
        <div class="container cart-hero-inner">
            <p class="cart-hero-kicker">Ciclo Finca 4</p>
            <h1 id="cart-page-title" class="cart-hero-title">Tu carrito</h1>
            <p class="cart-hero-subtitle">Revisá cantidades, elegí cómo pagar y confirmá cuando estés listo.</p>
        </div>
    </header>

    <div class="container cart-body">
        @if(session('cart_stock_adjusted'))
            <div class="cart-flash cart-flash--warning" role="alert">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span>{{ session('cart_stock_adjusted') }}</span>
            </div>
        @endif

        <div class="cart-page-card">
            <div class="cart-toolbar">
                <div class="cart-toolbar-text">
                    <span class="cart-toolbar-label">Resumen rápido</span>
                    @if(count($cartItems) > 0)
                        <span class="cart-toolbar-count">{{ count($cartItems) }} {{ count($cartItems) === 1 ? 'artículo' : 'artículos' }}</span>
                    @endif
                </div>
                <div class="cart-toolbar-actions">
                    <a href="{{ route('clients.catalog') }}" class="btn btn-ghost-cart">
                        <i class="fas fa-bicycle" aria-hidden="true"></i>
                        Seguir comprando
                    </a>
                    @if(count($cartItems) > 0)
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-cart">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            Vaciar carrito
                        </button>
                    @endif
                </div>
            </div>

            @if(count($cartItems) > 0)
                <div class="cart-layout">
                    <div class="cart-items" role="list" aria-label="Productos en el carrito">
                        @foreach($cartItems as $item)
                            @php
                                $productUrl = $item['product_url'] ?? route('clients.catalog', ['search' => $item['name']]);
                            @endphp
                            <article class="cart-item" role="listitem" data-product-id="{{ $item['product_id'] }}">
                                <a href="{{ $productUrl }}" class="cart-item-image" tabindex="-1" aria-hidden="true">
                                    <img src="{{ $item['image_url'] }}"
                                         alt=""
                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                         onerror="this.src=this.dataset.fallbackSrc;">
                                </a>
                                <div class="cart-item-main">
                                    <h3 class="item-name">
                                        <a href="{{ $productUrl }}">{{ $item['name'] }}</a>
                                    </h3>
                                    <div class="cart-item-meta">
                                        <span class="item-price">₡{{ number_format($item['price'], 0, ',', '.') }} <span class="item-price-unit">c/u</span></span>
                                        <span class="item-stock-badge" title="Stock disponible en tienda">
                                            <i class="fas fa-boxes-stacked" aria-hidden="true"></i>
                                            {{ $item['stock_available'] }} disponibles
                                        </span>
                                    </div>
                                </div>
                                <div class="item-controls" aria-label="Cantidad">
                                    <span class="item-controls-label" id="qty-label-{{ $item['product_id'] }}">Cantidad</span>
                                    <div class="quantity-controls cart-qty-controls">
                                        <button type="button" class="quantity-btn" data-action="decrease" data-product-id="{{ $item['product_id'] }}" aria-label="Disminuir cantidad">
                                            <i class="fas fa-minus" aria-hidden="true"></i>
                                        </button>
                                        <input type="number"
                                               class="quantity-input"
                                               value="{{ $item['quantity'] }}"
                                               min="1"
                                               max="{{ $item['stock_available'] }}"
                                               data-product-id="{{ $item['product_id'] }}"
                                               aria-labelledby="qty-label-{{ $item['product_id'] }}">
                                        <button type="button" class="quantity-btn" data-action="increase" data-product-id="{{ $item['product_id'] }}" aria-label="Aumentar cantidad">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="cart-item-right">
                                    <div class="item-subtotal">
                                        <span class="subtotal-label">Subtotal</span>
                                        <span class="subtotal-amount">₡{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                    </div>
                                    <button type="button"
                                            class="btn btn-icon-danger cart-remove-item"
                                            data-product-id="{{ $item['product_id'] }}"
                                            data-product-name="{{ $item['name'] }}"
                                            title="Quitar del carrito"
                                            aria-label="Quitar {{ $item['name'] }} del carrito">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="cart-summary" aria-labelledby="cart-summary-title">
                        <div class="summary-card">
                            <h2 id="cart-summary-title" class="summary-title">Total del pedido</h2>

                            <fieldset class="cart-payment-fieldset">
                                <legend class="cart-payment-legend" id="cart-payment-legend">Forma de pago</legend>
                                <p class="cart-payment-hint">Podés cambiarla luego; usamos esto para preparar tu pedido.</p>
                                <div class="cart-payment-options" role="radiogroup" aria-labelledby="cart-payment-legend">
                                    <label class="cart-payment-option">
                                        <input type="radio" name="checkout_payment_method" value="cash" class="cart-payment-input" checked>
                                        <span class="cart-payment-card">
                                            <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                                            <span class="cart-payment-label">Efectivo</span>
                                        </span>
                                    </label>
                                    <label class="cart-payment-option">
                                        <input type="radio" name="checkout_payment_method" value="sinpe" class="cart-payment-input">
                                        <span class="cart-payment-card">
                                            <i class="fas fa-mobile-screen-button" aria-hidden="true"></i>
                                            <span class="cart-payment-label">SINPE Móvil</span>
                                        </span>
                                    </label>
                                    <label class="cart-payment-option">
                                        <input type="radio" name="checkout_payment_method" value="transfer" class="cart-payment-input">
                                        <span class="cart-payment-card">
                                            <i class="fas fa-building-columns" aria-hidden="true"></i>
                                            <span class="cart-payment-label">Transferencia</span>
                                        </span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="summary-details">
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span id="cart-subtotal">₡{{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div class="summary-row summary-row--muted">
                                    <span>Impuestos</span>
                                    <span id="cart-taxes">Incluidos / no aplican</span>
                                </div>
                                <div class="summary-row summary-total">
                                    <span>Total estimado</span>
                                    <span id="cart-total-amount">₡{{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="summary-actions">
                                <button type="button" class="btn btn-primary btn-block btn-lg" id="proceed-checkout">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    Confirmar pedido
                                </button>
                                <p class="checkout-note">
                                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                                    Te contactamos para confirmar disponibilidad y retiro en tienda.
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <div class="cart-empty">
                    <div class="cart-empty-inner">
                        <div class="cart-empty-icon" aria-hidden="true">
                            <i class="fas fa-cart-shopping"></i>
                        </div>
                        <h2 class="cart-empty-title">Tu carrito está vacío</h2>
                        <p class="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>
                        <div class="cart-empty-actions">
                            <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-bicycle" aria-hidden="true"></i>
                                Ir al catálogo
                            </a>
                            <a href="{{ route('clients.catalog') }}#catalog-spotlight-heading" class="btn btn-ghost-cart btn-lg">
                                <i class="fas fa-star" aria-hidden="true"></i>
                                Ver destacados
                            </a>
                        </div>
                        <p class="cart-empty-home-link">
                            <a href="{{ route('clients.home') }}" class="cart-empty-home-anchor">Volver al inicio</a>
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-page.js'])
@endpush
