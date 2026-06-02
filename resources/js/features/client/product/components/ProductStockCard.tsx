import type { ProductDetail } from '@/features/client/product/types';

type ProductStockCardProps = {
  product: ProductDetail;
};

export function ProductStockCard({ product }: ProductStockCardProps) {
  const purchasable = product.canBuy;

  return (
    <div
      className={`product-detail-stock-card product-detail-stock-card--${
        purchasable && !product.isLowStock ? 'available' : purchasable && product.isLowStock ? 'low' : 'unavailable'
      }`}
      role="status"
    >
      {purchasable && product.isLowStock ? (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-exclamation-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">Últimas unidades</strong>
            <span className="product-detail-stock-card__subtitle">Solo quedan {product.stockCurrent.toLocaleString('es-CR')} disponibles</span>
          </div>
        </>
      ) : purchasable ? (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-check-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">En stock</strong>
            <span className="product-detail-stock-card__subtitle">{product.stockCurrent.toLocaleString('es-CR')} unidades disponibles</span>
          </div>
        </>
      ) : (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-times-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">{product.stockLabel}</strong>
            <span className="product-detail-stock-card__subtitle">
              {product.stockLabel === 'Agotado'
                ? 'Este producto no tiene unidades disponibles por ahora.'
                : 'No está disponible para compra en este momento.'}
            </span>
          </div>
        </>
      )}
    </div>
  );
}
