import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import { ProductStarsInline } from '@/features/client/product/components/ProductStarsInline';
import { ResponsivePicture } from '@/features/client/product/components/ResponsivePicture';
import type { ProductDetailPageProps } from '@/features/client/product/types';
import { addToCart } from '@/features/client/cart/api';
import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';
import { toggleFavorite } from '@/features/client/favorites/api';
import { useToast } from '@/shared/hooks/useToast';

type RelatedProductCardProps = {
  csrfToken: string;
  isAuthenticated: boolean;
  related: ProductDetailPageProps['relatedProducts'][number];
};

export function RelatedProductCard({ csrfToken, isAuthenticated, related }: RelatedProductCardProps) {
  const { showToast } = useToast();
  const [favoriteOverride, setFavoriteOverride] = useState<boolean | null>(null);
  const isFavorite = favoriteOverride ?? related.isFavorite;
  const [isBusy, setIsBusy] = useState(false);
  const outOfStock = related.stockLabel === 'Agotado';

  async function handleAddToCart() {
    if (!related.canBuy) {
      return;
    }

    if (!isAuthenticated) {
      router.visit('/login');
      return;
    }

    setIsBusy(true);

    try {
      const result = await addToCart(related.id, 1, csrfToken);
      if (!result.success) {
        showToast({
          variant: 'error',
          title: 'No se pudo agregar',
          message: result.message ?? 'No se pudo agregar el producto.',
        });
        return;
      }

      showToast({
        variant: 'success',
        title: 'Producto agregado',
        message: result.message ?? `${related.name} se agregó al carrito.`,
      });

      if (typeof result.cartCount === 'number') {
        window.dispatchEvent(new CustomEvent('cf4:cart-count', { detail: { count: result.cartCount } }));
      }
    } catch {
      showToast({
        variant: 'error',
        title: 'Error',
        message: 'No se pudo agregar el producto.',
      });
    } finally {
      setIsBusy(false);
    }
  }

  async function handleFavoriteToggle() {
    if (!isAuthenticated) {
      router.visit('/login');
      return;
    }

    setIsBusy(true);

    try {
      const result = await toggleFavorite(related.id, csrfToken);
      if (!result.success) {
        showToast({
          variant: 'error',
          title: 'No se pudo actualizar',
          message: result.message ?? 'No se pudo actualizar el favorito.',
        });
        return;
      }

      setFavoriteOverride(result.isFavorite);
    } catch {
      showToast({
        variant: 'error',
        title: 'Error',
        message: 'No se pudo actualizar el favorito.',
      });
    } finally {
      setIsBusy(false);
    }
  }

  return (
    <article className={`product-card product-card--related${outOfStock ? ' product-card--out-of-stock' : ''}`}>
      <div className="product-image product-image--related">
        {isAuthenticated ? (
          <button
            type="button"
            className={`product-favorite-btn${isFavorite ? ' is-active' : ''}`}
            disabled={isBusy}
            aria-pressed={isFavorite}
            aria-label={isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
            onClick={() => void handleFavoriteToggle()}
          >
            <i className={`${isFavorite ? 'fas' : 'far'} fa-heart`} aria-hidden="true" />
          </button>
        ) : null}
        <Link className="product-image__link" href={related.url} aria-label={`Ver producto: ${related.name}`}>
          {related.image.usesPlaceholder ? (
            <span className="product-media-placeholder">
              <img alt={related.name} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
              <i className={related.image.placeholderIconClass} aria-hidden="true" />
            </span>
          ) : (
            <ResponsivePicture
              alt={related.name}
              desktopWebp={related.image.desktopWebp}
              mobileWebp={related.image.mobileWebp}
              fallback={related.image.fallback}
            />
          )}
        </Link>
      </div>
      <div className="product-info">
        <div className="product-card-meta-badges">
          <span className="product-category">{related.categoryName}</span>
          {related.brandName ? <span className="product-card-brand-badge">{related.brandName}</span> : null}
        </div>
        <h3 className="product-name">
          <Link href={related.url}>{related.name}</Link>
        </h3>
        <ProductStarsInline avgStars={related.reviews.avg} reviewCount={related.reviews.count} variant="related" />
        {related.sku ? <p className="product-card-sku">SKU: {related.sku}</p> : null}
        <p className="product-availability-text product-stock-badge">{related.stockLabel}</p>
        {related.canBuy ? (
          <p className="product-stock-qty">{related.stockCurrent.toLocaleString('es-CR')} unidades disponibles</p>
        ) : null}
        <div className="product-footer">
          <div className="product-price">{related.priceFormatted}</div>
          <div className="product-actions">
            {related.canBuy ? (
              <button
                type="button"
                className="btn-product btn-agregar"
                disabled={isBusy}
                onClick={() => void handleAddToCart()}
              >
                <i className="fas fa-cart-plus" aria-hidden="true" />
                Agregar
              </button>
            ) : (
              <button type="button" className="btn-product btn-agotado" disabled>
                <i className="fas fa-ban" aria-hidden="true" />
                {related.stockLabel === 'Agotado' ? 'Agotado' : 'No disponible'}
              </button>
            )}
          </div>
        </div>
      </div>
    </article>
  );
}
