@extends('client.layouts.app')

@section('title', 'Carrito de Compras - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css'])
@endpush

@section('content')

{{-- Header verde igual que catálogo --}}
<div class="catalog-header">
    <div class="container">
        <h1 class="catalog-title">
            <i class="fas fa-shopping-cart" style="font-size:1.8rem; vertical-align:middle; margin-right:10px;"></i>
            Carrito de Compras
        </h1>
        <p class="catalog-subtitle">Revisa tus productos antes de confirmar</p>
    </div>
</div>

<div class="cart-container">
    <div class="container">
        <div class="cart-page-card">

            {{-- Acciones del header --}}
            <div class="cart-header">
                <a href="{{ route('clients.catalog') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                    <span>Continuar comprando</span>
                </a>
                @if(count($cartItems) > 0)
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-cart">
                        <i class="fas fa-trash-alt"></i>
                        <span>Vaciar carrito</span>
                    </button>
                @endif
            </div>

            @if(count($cartItems) > 0)
                <div class="cart-layout">
                    <div class="cart-items">
                        @foreach($cartItems as $item)
                            <div class="cart-item" data-product-id="{{ $item['product_id'] }}">

                                {{-- Imagen --}}
                                <div class="cart-item-image">
                                    <img src="{{ asset('assets/images/products/' . $item['image']) }}"
                                         alt="{{ $item['name'] }}"
                                         data-fallback-src="{{ asset('favicon.svg') }}"
                                         onerror="this.src=this.dataset.fallbackSrc;">
                                </div>

                                {{-- Info + controles (crecen juntos en móvil) --}}
                                <div class="cart-item-body">
                                    <div class="item-info">
                                        <h3 class="item-name">{{ $item['name'] }}</h3>
                                        <p class="item-price">₡{{ number_format($item['price'], 0, ',', '.') }} c/u</p>
                                        <p class="item-stock">Stock disponible: {{ $item['stock_available'] }}</p>
                                    </div>

                                    <div class="cart-item-footer">
                                        {{-- Controles de cantidad --}}
                                        <div class="item-controls">
                                            <span class="item-controls-label">Cantidad:</span>
                                            <div class="quantity-controls">
                                                <button type="button" class="quantity-btn"
                                                        data-action="decrease"
                                                        data-product-id="{{ $item['product_id'] }}"
                                                        aria-label="Disminuir">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number"
                                                       class="quantity-input"
                                                       value="{{ $item['quantity'] }}"
                                                       min="1"
                                                       max="{{ $item['stock_available'] }}"
                                                       data-product-id="{{ $item['product_id'] }}"
                                                       aria-label="Cantidad">
                                                <button type="button" class="quantity-btn"
                                                        data-action="increase"
                                                        data-product-id="{{ $item['product_id'] }}"
                                                        aria-label="Aumentar">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Subtotal + eliminar --}}
                                        <div class="cart-item-right">
                                            <div class="item-subtotal">
                                                <span class="subtotal-label">Subtotal:</span>
                                                <span class="subtotal-amount">₡{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="cart-item-actions">
                                                <button type="button"
                                                        class="btn btn-danger btn-sm cart-remove-item"
                                                        data-product-id="{{ $item['product_id'] }}"
                                                        data-product-name="{{ $item['name'] }}"
                                                        title="Eliminar del carrito">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>

                    {{-- Resumen lateral --}}
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