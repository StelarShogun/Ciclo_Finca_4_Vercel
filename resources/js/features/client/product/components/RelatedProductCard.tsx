import { Link } from '@inertiajs/react';

import { ProductStarsInline } from '@/features/client/product/components/ProductStarsInline';
import { ResponsivePicture } from '@/features/client/product/components/ResponsivePicture';
import type { ProductDetailPageProps } from '@/features/client/product/types';
import type { InertiaSharedProps } from '@/shared/types/models';

type RelatedProductCardProps = {
  authClient: InertiaSharedProps['auth']['client'];
  related: ProductDetailPageProps['relatedProducts'][number];
};

export function RelatedProductCard({ authClient, related }: RelatedProductCardProps) {
  const outOfStock = related.stockLabel === 'Agotado';

  return (
    <article className={`product-card product-card--related${outOfStock ? ' product-card--out-of-stock' : ''}`}>
      <div className="product-image product-image--related">
        {authClient ? (
          <button
            type="button"
            className={`product-favorite-btn${related.isFavorite ? ' is-active' : ''}`}
            data-product-favorite-btn
            data-product-id={related.id}
            aria-pressed={related.isFavorite}
            aria-label={related.isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
          >
            <i className={`${related.isFavorite ? 'fas' : 'far'} fa-heart`} aria-hidden="true" />
          </button>
        ) : null}
        <Link className="product-image__link" href={related.url} aria-label={`Ver producto: ${related.name}`}>
          {related.image.usesPlaceholder ? (
            <div className="product-media-placeholder" role="img" aria-label={related.name}>
              <i className={related.image.placeholderIconClass} aria-hidden="true" />
            </div>
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
            <Link href={related.url} className="btn-product btn-ver-detalles">
              <i className="fas fa-arrow-right" aria-hidden="true" />
              Ver detalles
            </Link>
            {related.canBuy ? (
              authClient ? (
                <button
                  type="button"
                  className="btn-product btn-agregar add-to-cart-btn"
                  data-purchasable="1"
                  data-product-id={related.id}
                  data-product-name={related.name}
                  data-product-price={related.price}
                  data-product-stock={related.stockCurrent}
                >
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar
                </button>
              ) : (
                <button type="button" className="btn-product btn-agregar guest-add-btn" data-purchasable="1" data-product-stock={related.stockCurrent}>
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar
                </button>
              )
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
