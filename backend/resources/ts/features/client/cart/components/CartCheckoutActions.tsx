type CartCheckoutActionsProps = {
  pickupPolicyLine: string;
  disabled?: boolean;
  onCheckout: () => void;
};

export function CartCheckoutActions({ disabled = false, onCheckout, pickupPolicyLine }: CartCheckoutActionsProps) {
  return (
    <div className="summary-actions">
      <button type="button" className="btn btn-primary btn-block btn-lg" disabled={disabled} onClick={onCheckout}>
        <i className={disabled ? 'fas fa-spinner fa-spin' : 'fas fa-check'} aria-hidden="true" />
        {disabled ? 'Procesando...' : 'Confirmar pedido'}
      </button>
      <p className="checkout-note">
        <i className="fas fa-circle-info" aria-hidden="true" />
        {pickupPolicyLine}
      </p>
    </div>
  );
}
