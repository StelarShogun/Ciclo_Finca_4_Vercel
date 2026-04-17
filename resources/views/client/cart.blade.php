@extends('client.layouts.app')

@section('title', 'Carrito de Compras - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')
<div class="cart-container">
    <div class="container">
        <div class="cart-page-card">
            <div class="cart-header">
                <h1 class="cart-title">
                    <i class="fas fa-shopping-cart"></i>
                    Carrito de Compras
                </h1>
                <div class="cart-header-actions">
                    <a href="{{ route('clients.catalog') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i>
                        Continuar Comprando
                    </a>
                    @if(count($cartItems) > 0)
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-cart">
                            <i class="fas fa-trash-alt"></i>
                            Vaciar Carrito
                        </button>
                    @endif
                </div>
            </div>

            @if(count($cartItems) > 0)
                <div class="cart-layout">
                    <div class="cart-items">
                        @foreach($cartItems as $item)
                            <div class="cart-item" data-product-id="{{ $item['product_id'] }}">
                                <div class="cart-item-image">
                                    <img src="{{ $item['image_url'] }}"
                                         alt="{{ $item['name'] }}"
                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                         onerror="this.src=this.dataset.fallbackSrc;">
                                </div>
                                <div class="item-info">
                                    <h3 class="item-name">{{ $item['name'] }}</h3>
                                    <p class="item-price">₡{{ number_format($item['price'], 0, ',', '.') }} c/u</p>
                                    <p class="item-stock">Disponibles: {{ $item['stock_available'] }} unidades</p>
                                </div>
                                <div class="item-controls">
                                    <label class="item-controls-label">Cantidad:</label>
                                    {{-- max attribute enforces stock limit on the client side --}}
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn" data-action="decrease" data-product-id="{{ $item['product_id'] }}" aria-label="Disminuir">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number"
                                               class="quantity-input"
                                               value="{{ $item['quantity'] }}"
                                               min="1"
                                               max="{{ $item['stock_available'] }}"
                                               data-product-id="{{ $item['product_id'] }}"
                                               aria-label="Cantidad">
                                        <button type="button" class="quantity-btn" data-action="increase" data-product-id="{{ $item['product_id'] }}" aria-label="Aumentar">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="cart-item-right">
                                    <div class="item-subtotal">
                                        <span class="subtotal-label">Subtotal:</span>
                                        <span class="subtotal-amount">₡{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="cart-item-actions">
                                        <button type="button" class="btn btn-danger btn-sm cart-remove-item"
                                                data-product-id="{{ $item['product_id'] }}"
                                                data-product-name="{{ $item['name'] }}"
                                                title="Eliminar del carrito">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <aside class="cart-summary">
                        <div class="summary-card">
                            <h3 class="summary-title">Resumen del Pedido</h3>
                            <div class="summary-details">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span id="cart-subtotal">₡{{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div class="summary-row">
                                    <span>Impuestos:</span>
                                    <span id="cart-taxes">₡0</span>
                                </div>
                                <div class="summary-row summary-total">
                                    <span>Total:</span>
                                    <span id="cart-total-amount">₡{{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="summary-actions">
                                {{-- Checkout is handled via JS; no direct form submit --}}
                                <button class="btn btn-primary btn-block btn-lg" id="proceed-checkout">
                                    <i class="fas fa-check"></i>
                                    Confirmar Compra
                                </button>
                                <p class="checkout-note">
                                    <i class="fas fa-info-circle"></i>
                                    Te contactaremos para confirmar tu pedido
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                <div class="cart-empty">
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Tu carrito está vacío</h2>
                        <p>Agrega productos desde nuestro catálogo</p>
                        <a href="{{ route('clients.catalog') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-th"></i>
                            Ver Catálogo
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-page.js'])
@endpush