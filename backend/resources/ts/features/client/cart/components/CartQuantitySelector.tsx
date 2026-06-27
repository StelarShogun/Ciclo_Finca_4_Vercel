import type { CartItem } from '@/features/client/cart/types';

type CartQuantitySelectorProps = {
  item: CartItem;
  disabled?: boolean;
  onChange: (quantity: number) => void;
};

export function CartQuantitySelector({ disabled = false, item, onChange }: CartQuantitySelectorProps) {
  const inputId = `qty-label-${item.productId}`;

  return (
    <div className="item-controls" aria-label="Cantidad">
      <span className="item-controls-label" id={inputId}>
        Cantidad
      </span>
      <div className="quantity-controls cart-qty-controls">
        <button
          type="button"
          className="quantity-btn"
          aria-label="Disminuir cantidad"
          disabled={disabled || !item.canUpdate || item.quantity <= 1}
          onClick={() => onChange(item.quantity - 1)}
        >
          <i className="fas fa-minus" aria-hidden="true" />
        </button>
        <input
          type="number"
          className="quantity-input"
          value={item.quantity}
          min={1}
          max={item.stockCurrent}
          aria-labelledby={inputId}
          disabled={disabled || !item.canUpdate}
          onChange={(event) => onChange(Number(event.currentTarget.value))}
        />
        <button
          type="button"
          className="quantity-btn"
          aria-label="Aumentar cantidad"
          disabled={disabled || !item.canUpdate || item.quantity >= item.stockCurrent}
          onClick={() => onChange(item.quantity + 1)}
        >
          <i className="fas fa-plus" aria-hidden="true" />
        </button>
      </div>
    </div>
  );
}
