import { ResponsivePicture } from '@/features/client/product/components/ResponsivePicture';
import type { ProductDetail } from '@/features/client/product/types';

type ProductGalleryProps = {
  product: ProductDetail;
};

export function ProductGallery({ product }: ProductGalleryProps) {
  const slideCount = product.carouselSlides.length;

  return (
    <div className="product-detail-image product-detail-hero__gallery">
      <div className="product-detail-gallery">
        <div
          className={`product-detail-media${product.showImagePlaceholder ? ' product-detail-media--placeholder' : ''}`}
        >
          {product.showImagePlaceholder ? (
            <div className="product-detail-image-placeholder" role="img" aria-label={product.name}>
              <i className={product.placeholderIconClass} aria-hidden="true" />
            </div>
          ) : (
            <div className="product-carousel" id="product-carousel" data-slide-count={slideCount}>
              <div className="carousel-viewport">
                <div className="carousel-track" id="carousel-track">
                  {product.carouselSlides.map((slide, index) => (
                    <div key={`${slide.fallback}-${index}`} className="carousel-slide">
                      <ResponsivePicture
                        alt={product.name}
                        desktopWebp={slide.desktopWebp}
                        mobileWebp={slide.mobileWebp}
                        fallback={slide.fallback}
                        loading={index === 0 ? 'eager' : 'lazy'}
                      />
                    </div>
                  ))}
                </div>
              </div>
              {slideCount > 1 ? (
                <>
                  <button className="carousel-btn carousel-btn--prev" id="carousel-prev" aria-label="Imagen anterior" disabled type="button">
                    <i className="fas fa-chevron-left" aria-hidden="true" />
                  </button>
                  <button className="carousel-btn carousel-btn--next" id="carousel-next" aria-label="Imagen siguiente" type="button">
                    <i className="fas fa-chevron-right" aria-hidden="true" />
                  </button>
                </>
              ) : null}
            </div>
          )}
        </div>

        {!product.showImagePlaceholder && slideCount > 0 ? (
          <div className="product-detail-thumbs" id="product-detail-thumbs" role="list" aria-label="Miniaturas del producto">
            {product.carouselSlides.map((slide, index) => (
              <button
                key={`thumb-${slide.fallback}`}
                type="button"
                className={`product-detail-thumb${index === 0 ? ' is-active' : ''}`}
                data-thumb-index={index}
                role="listitem"
                aria-label={`Ver imagen ${index + 1}`}
                aria-current={index === 0 ? 'true' : 'false'}
              >
                <ResponsivePicture alt="" fallback={slide.fallback} desktopWebp={slide.desktopWebp} mobileWebp={slide.mobileWebp} />
              </button>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}
