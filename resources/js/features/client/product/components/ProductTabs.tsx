import { ProductReviewsSection } from '@/features/client/product/components/ProductReviewsSection';
import { RelatedProductCard } from '@/features/client/product/components/RelatedProductCard';
import type { ProductDetail, ProductDetailPageProps } from '@/features/client/product/types';
import type { InertiaSharedProps } from '@/shared/types/models';

type ProductTabsProps = {
  activeTab: string;
  authClient: InertiaSharedProps['auth']['client'];
  csrfToken: string;
  isAuthenticated: boolean;
  product: ProductDetail;
  productPath: string;
  relatedProducts: ProductDetailPageProps['relatedProducts'];
  reviews: ProductDetailPageProps['reviews'];
  setActiveTab: (tab: string) => void;
  specs: ProductDetailPageProps['specs'];
  tabs: ProductDetailPageProps['tabs'];
};

export function ProductTabs({
  activeTab,
  authClient,
  csrfToken,
  isAuthenticated,
  product,
  productPath,
  relatedProducts,
  reviews,
  setActiveTab,
  specs,
  tabs,
}: ProductTabsProps) {
  return (
    <div className="product-detail-tabs" id="product-detail-tabs" data-default-tab={tabs.defaultTab}>
      <div className="product-detail-tabs__nav" role="tablist" aria-label="Información del producto">
        {(['description', 'specs', 'reviews', 'related'] as const).map((tab) => {
          if (tab === 'description' && !tabs.hasDescription) return null;
          if (tab === 'specs' && !tabs.hasSpecs) return null;
          if (tab === 'related' && !tabs.hasRelated) return null;

          const labels: Record<string, string> = {
            description: 'Descripción',
            specs: 'Especificaciones',
            reviews: 'Reseñas',
            related: 'Relacionados',
          };

          return (
            <button
              key={tab}
              type="button"
              role="tab"
              className={`product-detail-tabs__btn${activeTab === tab ? ' is-active' : ''}`}
              data-tab={tab}
              aria-selected={activeTab === tab}
              onClick={() => setActiveTab(tab)}
            >
              {labels[tab]}
              {tab === 'reviews' && reviews.totalCount > 0 ? (
                <span className="product-detail-tabs__count">{reviews.totalCount}</span>
              ) : null}
            </button>
          );
        })}
      </div>

      <div className="product-detail-tabs__panels">
        {tabs.hasDescription ? (
          <section
            id="product-tab-description"
            className="product-detail-tab-panel"
            data-panel="description"
            role="tabpanel"
            hidden={activeTab !== 'description'}
          >
            {product.description ? (
              <article className="product-detail-card product-detail-description-card">
                <h2 className="product-detail-card__title">Descripción del producto</h2>
                <div className="product-detail-description__body" style={{ whiteSpace: 'pre-wrap' }}>
                  {product.description}
                </div>
              </article>
            ) : (
              <article className="product-detail-card product-detail-description-card product-detail-description-card--empty">
                <h2 className="product-detail-card__title">Descripción del producto</h2>
                <p className="product-detail-description__empty">
                  Este producto aún no tiene una descripción detallada. Consultá con nuestro equipo o revisá las especificaciones técnicas.
                </p>
              </article>
            )}
          </section>
        ) : null}

        {tabs.hasSpecs ? (
          <section id="product-tab-specs" className="product-detail-tab-panel" data-panel="specs" role="tabpanel" hidden={activeTab !== 'specs'}>
            <article className="product-detail-card">
              <h2 className="product-detail-card__title">Características técnicas</h2>
              {specs.length > 0 ? (
                <div className="product-detail-specs__chips">
                  {specs.map((spec, index) => (
                    <span key={`${spec.value}-${index}`} className="product-detail-spec-chip">
                      {spec.dimensionLabel ? <span className="product-detail-spec-chip__label">{spec.dimensionLabel}</span> : null}
                      <span className="product-detail-spec-chip__value">{spec.value}</span>
                    </span>
                  ))}
                </div>
              ) : (
                <p className="product-detail-specs__empty">Pronto publicaremos las características técnicas de este producto.</p>
              )}
            </article>
          </section>
        ) : null}

        <section id="product-tab-reviews" className="product-detail-tab-panel product-detail-reviews" data-panel="reviews" role="tabpanel" hidden={activeTab !== 'reviews'}>
          <ProductReviewsSection authClient={authClient} productId={product.id} productPath={productPath} reviews={reviews} />
        </section>

        {tabs.hasRelated ? (
          <section id="product-tab-related" className="product-detail-tab-panel product-detail-related" data-panel="related" role="tabpanel" hidden={activeTab !== 'related'}>
            <h2 className="product-detail-card__title">Productos relacionados</h2>
            <div className="product-detail-related-scroll">
              <div className="products-grid products-grid--related">
                {relatedProducts.map((related) => (
                  <RelatedProductCard
                    key={related.id}
                    csrfToken={csrfToken}
                    isAuthenticated={isAuthenticated}
                    related={related}
                  />
                ))}
              </div>
            </div>
          </section>
        ) : null}
      </div>
    </div>
  );
}
