import { CartCheckoutActions } from '@/features/client/cart/components/CartCheckoutActions';
import type { CartPaymentMethod } from '@/features/client/cart/types';

type CartSummaryProps = {
  subtotalFormatted: string;
  totalFormatted: string;
  paymentMethod: CartPaymentMethod;
  pickupPolicyLine: string;
  isCheckingOut?: boolean;
  onCheckout: () => void;
  onPaymentMethodChange: (method: CartPaymentMethod) => void;
};

const paymentOptions: Array<{
  value: CartPaymentMethod;
  label: string;
  iconClass: string;
}> = [
  { value: 'cash', label: 'Efectivo', iconClass: 'fas fa-money-bill-wave' },
  { value: 'sinpe', label: 'SINPE Móvil', iconClass: 'fas fa-mobile-screen-button' },
  { value: 'transfer', label: 'Transferencia', iconClass: 'fas fa-building-columns' },
];

export function CartSummary({
  isCheckingOut = false,
  onCheckout,
  onPaymentMethodChange,
  paymentMethod,
  pickupPolicyLine,
  subtotalFormatted,
  totalFormatted,
}: CartSummaryProps) {
  return (
    <aside className="cart-summary" aria-labelledby="cart-summary-title">
      <div className="summary-card">
        <h2 id="cart-summary-title" className="summary-title">
          Total del pedido
        </h2>

        <fieldset className="cart-payment-fieldset">
          <legend className="cart-payment-legend" id="cart-payment-legend">
            Forma de pago
          </legend>
          <p className="cart-payment-hint">Podés cambiarla luego; usamos esto para preparar tu pedido.</p>
          <div className="cart-payment-options" role="radiogroup" aria-labelledby="cart-payment-legend">
            {paymentOptions.map((option) => (
              <label key={option.value} className="cart-payment-option">
                <input
                  type="radio"
                  name="checkout_payment_method"
                  value={option.value}
                  className="cart-payment-input"
                  checked={paymentMethod === option.value}
                  onChange={() => onPaymentMethodChange(option.value)}
                />
                <span className="cart-payment-card">
                  <i className={option.iconClass} aria-hidden="true" />
                  <span className="cart-payment-label">{option.label}</span>
                </span>
              </label>
            ))}
          </div>
        </fieldset>

        <div className="summary-details">
          <div className="summary-row">
            <span>Subtotal</span>
            <span>{subtotalFormatted}</span>
          </div>
          <div className="summary-row summary-row--muted">
            <span>Impuestos</span>
            <span>Incluidos / no aplican</span>
          </div>
          <div className="summary-row summary-total">
            <span>Total estimado</span>
            <span>{totalFormatted}</span>
          </div>
        </div>

        <CartCheckoutActions
          disabled={isCheckingOut}
          onCheckout={onCheckout}
          pickupPolicyLine={pickupPolicyLine}
        />
      </div>
    </aside>
  );
}
