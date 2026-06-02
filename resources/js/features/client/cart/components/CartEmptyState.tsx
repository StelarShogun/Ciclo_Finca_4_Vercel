import { Link } from '@inertiajs/react';

export function CartEmptyState() {
  return (
    <div className="cart-empty">
      <div className="cart-empty-inner">
        <div className="cart-empty-icon" aria-hidden="true">
          <i className="fas fa-cart-shopping" />
        </div>
        <h2 className="cart-empty-title">Tu carrito está vacío</h2>
        <p className="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>
        <div className="cart-empty-actions">
          <Link href="/catalog" className="btn btn-primary btn-lg">
            <i className="fas fa-bicycle" aria-hidden="true" />
            Ir al catálogo
          </Link>
          <Link href="/catalog#catalog-spotlight-heading" className="btn btn-ghost-cart btn-lg">
            <i className="fas fa-star" aria-hidden="true" />
            Ver destacados
          </Link>
        </div>
        <p className="cart-empty-home-link">
          <Link href="/" className="cart-empty-home-anchor">
            Volver al inicio
          </Link>
        </p>
      </div>
    </div>
  );
}
