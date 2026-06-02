import { useState } from 'react';

import { ImageFallback } from '@/Components/Home/ImageFallback';
import type { HomeProduct } from '@/types/home';

type ProductCardProps = {
  product: HomeProduct;
  isAuthenticated: boolean;
  csrfToken: string;
};

export function ProductCard({ product, isAuthenticated, csrfToken }: ProductCardProps) {
  const [isAdding, setIsAdding] = useState(false);
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const isOutOfStock = product.stockLabel === 'Agotado';

  async function handleAddToCart() {
    if (!product.canBuy) {
      setStatusMessage('Este producto no tiene unidades disponibles.');
      return;
    }

    if (!isAuthenticated) {
      window.location.href = '/login';
      return;
    }

    setIsAdding(true);
    setStatusMessage(null);

    try {
      const response = await fetch('/cart/add', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ product_id: product.id, quantity: 1 }),
      });
      const payload = (await response.json()) as { success?: boolean; message?: string; cart_count?: number };

      if (!response.ok || !payload.success) {
        setStatusMessage(payload.message ?? 'No se pudo agregar el producto.');
        return;
      }

      setStatusMessage(payload.message ?? 'Producto agregado al carrito.');
      window.dispatchEvent(
        new CustomEvent('cf4:cart-count', {
          detail: { count: payload.cart_count },
        }),
      );
    } catch {
      setStatusMessage('No se pudo agregar el producto. Inténtalo de nuevo.');
    } finally {
      setIsAdding(false);
    }
  }

  return (
    <article
      className={`swiper-slide product-card ${isOutOfStock ? 'product-card--out-of-stock' : ''}`}
      style={{ flex: '0 0 min(100%, 300px)', scrollSnapAlign: 'start' }}
    >
      <div className="product-image">
        <a className="product-image__link" href={product.url} aria-label={`Ver producto: ${product.name}`}>
          <ImageFallback image={product.image} alt={product.name} />
        </a>
      </div>

      <div className="product-info">
        <div className="product-category">{product.category}</div>
        <h3 className="product-name">{product.name}</h3>
        <ProductStars avg={product.reviews.avg} count={product.reviews.count} />

        {product.sku ? <p className="product-card-sku">SKU: {product.sku}</p> : null}

        <p
          className={[
            'product-availability-text',
            'product-stock-badge',
            product.stockLabel === 'En stock' ? 'is-available' : '',
            product.stockLabel === 'Últimas unidades' ? 'is-low' : '',
            product.stockLabel === 'Agotado' ? 'is-out' : '',
            product.stockLabel === 'No disponible' ? 'is-na' : '',
          ]
            .filter(Boolean)
            .join(' ')}
        >
          {product.stockLabel}
        </p>

        {product.canBuy ? (
          <p className="product-stock-qty">{product.stockCurrent.toLocaleString('es-CR')} unidades disponibles</p>
        ) : null}

        {product.description ? <p className="product-description">{product.description}</p> : null}

        <div className="product-footer">
          <div className="product-price">{product.priceFormatted}</div>
          <div className="product-actions">
            <a href={product.url} className="btn-product btn-ver-detalles" title="Ver ficha del producto">
              Ver producto
            </a>
            {product.canBuy ? (
              <button type="button" className="btn-product btn-agregar" onClick={handleAddToCart} disabled={isAdding}>
                <i className="fas fa-cart-plus" aria-hidden="true" />
                {isAdding ? 'Agregando' : 'Agregar'}
              </button>
            ) : (
              <button type="button" className="btn-product btn-agotado" disabled>
                <i className="fas fa-ban" aria-hidden="true" />
                {isOutOfStock ? 'Agotado' : 'No disponible'}
              </button>
            )}
          </div>
        </div>

        {statusMessage ? (
          <p className="product-card-status" role="status">
            {statusMessage}
          </p>
        ) : null}
      </div>
    </article>
  );
}

function ProductStars({ avg, count }: { avg: number; count: number }) {
  const rounded = Math.round(avg);

  return (
    <div className="product-stars-inline" aria-label={`${avg.toFixed(1)} de 5 estrellas, ${count} reseñas`}>
      <span className="product-stars-inline__stars" aria-hidden="true">
        {Array.from({ length: 5 }, (_, index) => (
          <i key={index} className={index < rounded ? 'fas fa-star' : 'far fa-star'} />
        ))}
      </span>
      <span className="product-stars-inline__meta">
        {count > 0 ? `${avg.toFixed(1)} (${count})` : 'Sin reseñas'}
      </span>
    </div>
  );
}
