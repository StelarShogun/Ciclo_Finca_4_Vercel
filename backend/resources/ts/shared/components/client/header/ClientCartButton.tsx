import { Link } from '@inertiajs/react';

type ClientCartButtonProps = {
  cartCount: number;
  className?: string;
};

export function ClientCartButton({ cartCount, className = 'cart-btn cart-btn-link' }: ClientCartButtonProps) {
  return (
    <Link
      href="/cart"
      className={className}
      aria-label={`Ver carrito (${cartCount} productos)`}
      title="Ver carrito"
    >
      <i className="fas fa-shopping-cart" aria-hidden="true" />
      <span className="cart-count">{cartCount > 0 ? cartCount : 0}</span>
    </Link>
  );
}
