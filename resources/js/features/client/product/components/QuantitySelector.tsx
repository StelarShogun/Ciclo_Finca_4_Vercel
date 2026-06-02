import { formatColones } from '@/features/client/product/utils/formatColones';
import type { ProductDetail } from '@/features/client/product/types';

type QuantitySelectorProps = {
  onQuantityChange: (quantity: number) => void;
  product: ProductDetail;
  quantity: number;
};

export function QuantitySelector({ onQuantityChange, product, quantity }: QuantitySelectorProps) {
  const minQty = 1;
  const maxQty = product.stockCurrent;

  function clamp(value: number): number {
    if (Number.isNaN(value) || value < minQty) {
      return minQty;
    }

    if (value > maxQty) {
      return maxQty;
    }

    return value;
  }

  function setQuantity(next: number) {
    onQuantityChange(clamp(next));
  }

  const atMin = quantity <= minQty;
  const atMax = quantity >= maxQty;
  const subtotalFormatted = formatColones(product.price * quantity);

  return (
    <div className="product-detail-qty">
      <label className="product-detail-qty__label" htmlFor="product-quantity">
        Cantidad
      </label>
      <div className="product-detail-qty-stepper quantity-controls">
        <button
          type="button"
          className="quantity-btn product-detail-qty-stepper__btn"
          aria-label="Disminuir cantidad"
          disabled={atMin}
          aria-disabled={atMin}
          onClick={() => setQuantity(quantity - 1)}
        >
          <i className="fas fa-minus" aria-hidden="true" />
        </button>
        <input
          type="number"
          id="product-quantity"
          className="quantity-input product-detail-qty-stepper__input"
          value={quantity}
          min={minQty}
          max={maxQty}
          inputMode="numeric"
          aria-describedby="product-qty-max-hint product-qty-subtotal"
          onChange={(event) => setQuantity(parseInt(event.target.value, 10))}
        />
        <button
          type="button"
          className="quantity-btn product-detail-qty-stepper__btn"
          aria-label="Aumentar cantidad"
          disabled={atMax}
          aria-disabled={atMax}
          onClick={() => setQuantity(quantity + 1)}
        >
          <i className="fas fa-plus" aria-hidden="true" />
        </button>
      </div>
      <p className="product-detail-qty__hint" id="product-qty-max-hint">
        Máximo disponible: {maxQty.toLocaleString('es-CR')} unidades
      </p>
      <p className="product-detail-qty__subtotal" id="product-qty-subtotal" aria-live="polite">
        Subtotal: {subtotalFormatted}
      </p>
    </div>
  );
}
