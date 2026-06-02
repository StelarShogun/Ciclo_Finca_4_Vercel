import type { ProductDetail } from '@/features/client/product/types';

type QuantitySelectorProps = {
  product: ProductDetail;
};

export function QuantitySelector({ product }: QuantitySelectorProps) {
  return (
    <div className="product-detail-qty">
      <label className="product-detail-qty__label" htmlFor="product-quantity">
        Cantidad
      </label>
      <div className="product-detail-qty-stepper quantity-controls">
        <button type="button" className="quantity-btn product-detail-qty-stepper__btn" id="decrease-qty" aria-label="Disminuir cantidad">
          <i className="fas fa-minus" aria-hidden="true" />
        </button>
        <input
          type="number"
          id="product-quantity"
          className="quantity-input product-detail-qty-stepper__input"
          defaultValue={1}
          min={1}
          max={product.stockCurrent}
          inputMode="numeric"
          aria-describedby="product-qty-max-hint product-qty-subtotal"
        />
        <button type="button" className="quantity-btn product-detail-qty-stepper__btn" id="increase-qty" aria-label="Aumentar cantidad">
          <i className="fas fa-plus" aria-hidden="true" />
        </button>
      </div>
      <p className="product-detail-qty__hint" id="product-qty-max-hint">
        Máximo disponible: {product.stockCurrent.toLocaleString('es-CR')} unidades
      </p>
      <p className="product-detail-qty__subtotal" id="product-qty-subtotal" aria-live="polite">
        Subtotal: {product.priceFormatted}
      </p>
    </div>
  );
}
