import { Link } from '@inertiajs/react';

import { ImageFallback } from '@/shared/components/ui/ImageFallback';
import type { CartFeaturedProduct } from '@/features/client/cart/types';

type CartEmptyStateProps = {
  featuredProducts?: CartFeaturedProduct[];
};

export function CartEmptyState({ featuredProducts = [] }: CartEmptyStateProps) {
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

      {featuredProducts.length > 0 ? (
        <div className="cart-empty-featured" aria-labelledby="cart-empty-featured-title">
          <h3 id="cart-empty-featured-title" className="cart-empty-featured__title">
            <i className="fas fa-star" aria-hidden="true" />
            Productos destacados
          </h3>
          <ul className="cart-empty-featured__grid">
            {featuredProducts.map((product) => (
              <li key={product.id} className="cart-empty-featured__item">
                <Link href={product.url} className="cart-empty-featured__card">
                  <span className="cart-empty-featured__media">
                    <ImageFallback image={product.image} alt={product.name} />
                  </span>
                  <span className="cart-empty-featured__name">{product.name}</span>
                  <span className="cart-empty-featured__price">{product.priceFormatted}</span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  );
}
