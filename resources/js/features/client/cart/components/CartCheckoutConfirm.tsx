import type { CartItem, CartPaymentMethod } from '@/features/client/cart/types';

const paymentMeta: Record<CartPaymentMethod, { label: string; iconClass: string }> = {
  cash: { label: 'Efectivo', iconClass: 'fas fa-money-bill-wave' },
  sinpe: { label: 'SINPE Móvil', iconClass: 'fas fa-mobile-screen-button' },
  transfer: { label: 'Transferencia', iconClass: 'fas fa-building-columns' },
};

type CartCheckoutConfirmProps = {
  items: CartItem[];
  totalFormatted: string;
  paymentMethod: CartPaymentMethod;
};

export function CartCheckoutConfirm({ items, paymentMethod, totalFormatted }: CartCheckoutConfirmProps) {
  const payment = paymentMeta[paymentMethod];
  const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <div className="checkout-confirm">
      <ul className="checkout-confirm__items" aria-label="Productos del pedido">
        {items.map((item) => (
          <li key={item.productId} className="checkout-confirm__item">
            <span className="checkout-confirm__item-qty" aria-hidden="true">
              {item.quantity}×
            </span>
            <span className="checkout-confirm__item-name">{item.name}</span>
            <span className="checkout-confirm__item-subtotal">{item.subtotalFormatted}</span>
          </li>
        ))}
      </ul>

      <dl className="checkout-confirm__meta">
        <div className="checkout-confirm__row">
          <dt>Artículos</dt>
          <dd>{totalQuantity}</dd>
        </div>
        <div className="checkout-confirm__row">
          <dt>Método de pago</dt>
          <dd>
            <i className={payment.iconClass} aria-hidden="true" /> {payment.label}
          </dd>
        </div>
        <div className="checkout-confirm__row checkout-confirm__row--total">
          <dt>Total</dt>
          <dd>{totalFormatted}</dd>
        </div>
      </dl>

      <p className="checkout-confirm__note">
        <i className="fas fa-store" aria-hidden="true" />
        Tu pedido quedará listo para retiro en tienda.
      </p>
    </div>
  );
}
