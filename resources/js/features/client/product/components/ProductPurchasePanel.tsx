import { Link } from '@inertiajs/react';

import { ProductStarsInline } from '@/features/client/product/components/ProductStarsInline';
import { ProductStockCard } from '@/features/client/product/components/ProductStockCard';
import { QuantitySelector } from '@/features/client/product/components/QuantitySelector';
import type { ProductDetail, ProductDetailPageProps } from '@/features/client/product/types';
import type { InertiaSharedProps } from '@/shared/types/models';

type ProductPurchasePanelProps = {
  authClient: InertiaSharedProps['auth']['client'];
  isNovelty: boolean;
  orderReservationHours: number;
  primaryBrand: ProductDetailPageProps['primaryBrand'];
  product: ProductDetail;
  reviewAvg: number;
  reviewCount: number;
  taxonomy: ProductDetailPageProps['taxonomy'];
  whatsappConsultUrl: string | null;
};

export function ProductPurchasePanel({
  authClient,
  isNovelty,
  orderReservationHours,
  primaryBrand,
  product,
  reviewAvg,
  reviewCount,
  taxonomy,
  whatsappConsultUrl,
}: ProductPurchasePanelProps) {
  const stockModifier =
    product.stockLabel === 'En stock'
      ? 'stock'
      : product.stockLabel === 'Últimas unidades'
        ? 'low-stock'
        : product.stockLabel === 'Agotado'
          ? 'out-stock'
          : 'unavailable';

  return (
    <div className="product-detail-info product-detail-hero__buy">
      <aside className="product-detail-purchase-panel" aria-label="Comprar producto">
        <div className="product-detail-badges" aria-label="Información rápida del producto">
          {taxonomy.parentCategory ? (
            <Link href={taxonomy.parentCategory.url} className="product-badge product-badge--category product-detail-badge product-detail-badge--category">
              <i className="fas fa-layer-group product-badge__icon" aria-hidden="true" />
              {taxonomy.parentCategory.name}
            </Link>
          ) : null}
          {taxonomy.subcategory ? (
            <Link href={taxonomy.subcategory.url} className="product-badge product-badge--subcategory product-detail-badge product-detail-badge--subcategory">
              <i className="fas fa-tag product-badge__icon" aria-hidden="true" />
              {taxonomy.subcategory.name}
            </Link>
          ) : null}
          {primaryBrand ? (
            <Link href={primaryBrand.catalogUrl} className="product-badge product-badge--brand product-detail-badge product-detail-badge--brand">
              <i className="fas fa-tag product-badge__icon" aria-hidden="true" />
              {primaryBrand.name}
            </Link>
          ) : null}
          <span className={`product-badge product-badge--${stockModifier} product-detail-badge product-detail-badge--stock`}>
            <i className="fas fa-check-circle product-badge__icon" aria-hidden="true" />
            {product.stockLabel}
          </span>
          {product.isFeatured ? (
            <span className="product-badge product-badge--featured product-detail-badge product-detail-badge--featured">
              <i className="fas fa-star product-badge__icon" aria-hidden="true" />
              Destacado
            </span>
          ) : null}
          {isNovelty ? (
            <span className="product-badge product-badge--new product-detail-badge product-detail-badge--novelty">
              <i className="fas fa-bolt product-badge__icon" aria-hidden="true" />
              Novedad
            </span>
          ) : null}
        </div>

        <h1 className="product-detail-name">{product.name}</h1>
        {product.sku ? <p className="product-detail-sku">SKU: {product.sku}</p> : null}

        <div className="product-detail-rating-summary">
          <ProductStarsInline avgStars={reviewAvg} reviewCount={reviewCount} variant="detail" emptyLabel="Aún no hay valoraciones" />
        </div>

        <div className="product-detail-price" data-unit-price={product.price}>
          <span className="product-detail-price__label">Precio</span>
          <span className="product-detail-price__amount">{product.priceFormatted}</span>
        </div>

        <ProductStockCard product={product} />

        {product.canBuy ? (
          <div className="product-detail-actions">
            <QuantitySelector product={product} />

            <div className="product-detail-actions__buttons">
              {authClient ? (
                <>
                  <button
                    type="button"
                    className="btn btn-primary btn-lg product-detail-actions__cart add-to-cart-btn"
                    data-purchasable="1"
                    data-product-id={product.id}
                    data-product-name={product.name}
                    data-product-price={product.price}
                    data-product-stock={product.stockCurrent}
                  >
                    <i className="fas fa-cart-plus" aria-hidden="true" />
                    Agregar al carrito
                  </button>
                  <button
                    type="button"
                    className={`product-detail-favorite product-favorite-btn${product.isFavorite ? ' is-active' : ''}`}
                    data-product-favorite-btn
                    data-product-id={product.id}
                    aria-pressed={product.isFavorite}
                    aria-label={product.isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
                  >
                    <span className="product-detail-favorite__icon" aria-hidden="true">
                      <i className={`${product.isFavorite ? 'fas' : 'far'} fa-heart`} />
                    </span>
                    <span className="product-detail-favorite__label">{product.isFavorite ? 'En favoritos' : 'Agregar a favoritos'}</span>
                  </button>
                </>
              ) : (
                <button type="button" className="btn btn-primary btn-lg product-detail-actions__cart guest-add-btn" data-purchasable="1" data-product-stock={product.stockCurrent}>
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar al carrito
                </button>
              )}
              {whatsappConsultUrl ? (
                <a href={whatsappConsultUrl} className="btn btn-outline product-detail-actions__whatsapp" target="_blank" rel="noopener noreferrer">
                  <i className="fab fa-whatsapp" aria-hidden="true" />
                  Consultar por WhatsApp
                </a>
              ) : null}
            </div>
          </div>
        ) : null}

        <ul className="product-detail-trust" aria-label="Beneficios de compra">
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-store" /></span>
            <span className="product-detail-trust__text">Retiro en tienda</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-money-bill-wave" /></span>
            <span className="product-detail-trust__text">Pago al retirar</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-clock" /></span>
            <span className="product-detail-trust__text">Reserva por {orderReservationHours} horas</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-boxes" /></span>
            <span className="product-detail-trust__text">Stock actualizado</span>
          </li>
          {whatsappConsultUrl ? (
            <li className="product-detail-trust__item product-detail-trust__item--whatsapp">
              <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-comment-alt" /></span>
              <span className="product-detail-trust__text">Atención por WhatsApp</span>
            </li>
          ) : null}
        </ul>
      </aside>
    </div>
  );
}
