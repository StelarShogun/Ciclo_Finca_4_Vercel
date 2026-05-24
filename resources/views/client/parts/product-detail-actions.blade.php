@if($product->isPurchasableByClient())
    <div class="product-detail-actions">
        <div class="product-detail-qty">
            <label class="product-detail-qty__label" for="product-quantity">Cantidad</label>
            <div class="product-detail-qty-stepper quantity-controls">
                <button type="button"
                        class="quantity-btn product-detail-qty-stepper__btn"
                        id="decrease-qty"
                        aria-label="Disminuir cantidad">
                    <i class="fas fa-minus" aria-hidden="true"></i>
                </button>
                <input type="number"
                       id="product-quantity"
                       class="quantity-input product-detail-qty-stepper__input"
                       value="1"
                       min="1"
                       max="{{ $product->stock_current }}"
                       inputmode="numeric"
                       aria-describedby="product-qty-max-hint product-qty-subtotal">
                <button type="button"
                        class="quantity-btn product-detail-qty-stepper__btn"
                        id="increase-qty"
                        aria-label="Aumentar cantidad">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                </button>
            </div>
            <p class="product-detail-qty__hint" id="product-qty-max-hint">
                Máximo disponible: {{ number_format((int) $product->stock_current, 0, ',', '.') }} unidades
            </p>
            <p class="product-detail-qty__subtotal" id="product-qty-subtotal" aria-live="polite">
                Subtotal: ₡{{ number_format($product->sale_price, 0, ',', '.') }}
            </p>
        </div>

        <div class="product-detail-actions__buttons">
            @auth('clients')
                <button type="button"
                        class="btn btn-primary btn-lg product-detail-actions__cart add-to-cart-btn"
                        data-purchasable="1"
                        data-product-id="{{ $product->product_id }}"
                        data-product-name="{{ $product->name }}"
                        data-product-price="{{ $product->sale_price }}"
                        data-product-stock="{{ $product->stock_current }}">
                    <i class="fas fa-cart-plus" aria-hidden="true"></i>
                    Agregar al carrito
                </button>
                <button type="button"
                        class="product-detail-favorite product-favorite-btn {{ ($isProductFavorite ?? false) ? 'is-active' : '' }}"
                        data-product-favorite-btn
                        data-product-id="{{ $product->product_id }}"
                        aria-pressed="{{ ($isProductFavorite ?? false) ? 'true' : 'false' }}"
                        aria-label="{{ ($isProductFavorite ?? false) ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                    <span class="product-detail-favorite__icon" aria-hidden="true">
                        <i class="{{ ($isProductFavorite ?? false) ? 'fas' : 'far' }} fa-heart"></i>
                    </span>
                    <span class="product-detail-favorite__label">
                        {{ ($isProductFavorite ?? false) ? 'En favoritos' : 'Agregar a favoritos' }}
                    </span>
                </button>
            @else
                <button type="button"
                        class="btn btn-primary btn-lg product-detail-actions__cart guest-add-btn"
                        data-purchasable="1"
                        data-product-stock="{{ $product->stock_current }}">
                    <i class="fas fa-cart-plus" aria-hidden="true"></i>
                    Agregar al carrito
                </button>
            @endauth

            @if(! empty($whatsappConsultUrl))
                <a href="{{ $whatsappConsultUrl }}"
                   class="btn btn-outline product-detail-actions__whatsapp"
                   target="_blank"
                   rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    Consultar por WhatsApp
                </a>
            @endif
        </div>
    </div>
@endif
