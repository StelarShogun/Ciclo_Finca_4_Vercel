import { useCallback, useEffect, useState } from 'react';

import { ResponsivePicture } from '@/features/client/product/components/ResponsivePicture';
import type { ProductDetail } from '@/features/client/product/types';
import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';

type ProductGalleryProps = {
  product: ProductDetail;
};

export function ProductGallery({ product }: ProductGalleryProps) {
  const slideCount = product.carouselSlides.length;
  const [currentSlide, setCurrentSlide] = useState(0);

  const goTo = useCallback(
    (index: number) => {
      if (slideCount < 1) {
        return;
      }

      setCurrentSlide(Math.max(0, Math.min(slideCount - 1, index)));
    },
    [slideCount],
  );

  useEffect(() => {
    if (slideCount < 2) {
      return;
    }

    function onKeyDown(event: KeyboardEvent) {
      if (!document.getElementById('product-carousel')) {
        return;
      }

      if (event.key === 'ArrowLeft') {
        goTo(currentSlide - 1);
      }

      if (event.key === 'ArrowRight') {
        goTo(currentSlide + 1);
      }
    }

    document.addEventListener('keydown', onKeyDown);

    return () => document.removeEventListener('keydown', onKeyDown);
  }, [currentSlide, goTo, slideCount]);

  return (
    <div className="product-detail-image product-detail-hero__gallery">
      <div className="product-detail-gallery">
        <div
          className={`product-detail-media${product.showImagePlaceholder ? ' product-detail-media--placeholder' : ''}`}
        >
          {product.showImagePlaceholder ? (
            <span className="product-detail-image-placeholder">
              <img alt={product.name} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
              <i className={product.placeholderIconClass} aria-hidden="true" />
            </span>
          ) : (
            <div className="product-carousel" id="product-carousel" data-slide-count={slideCount}>
              <div className="carousel-viewport">
                <div
                  className="carousel-track"
                  id="carousel-track"
                  style={{ transform: `translateX(-${currentSlide * 100}%)` }}
                >
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
                  <button
                    className="carousel-btn carousel-btn--prev"
                    id="carousel-prev"
                    aria-label="Imagen anterior"
                    disabled={currentSlide === 0}
                    type="button"
                    onClick={() => goTo(currentSlide - 1)}
                  >
                    <i className="fas fa-chevron-left" aria-hidden="true" />
                  </button>
                  <button
                    className="carousel-btn carousel-btn--next"
                    id="carousel-next"
                    aria-label="Imagen siguiente"
                    disabled={currentSlide >= slideCount - 1}
                    type="button"
                    onClick={() => goTo(currentSlide + 1)}
                  >
                    <i className="fas fa-chevron-right" aria-hidden="true" />
                  </button>
                </>
              ) : null}
            </div>
          )}
        </div>

        {!product.showImagePlaceholder && slideCount > 0 ? (
          <ul className="product-detail-thumbs" id="product-detail-thumbs" aria-label="Miniaturas del producto">
            {product.carouselSlides.map((slide, index) => (
              <li key={`thumb-${slide.fallback}`}>
              <button
                type="button"
                className={`product-detail-thumb${index === currentSlide ? ' is-active' : ''}`}
                data-thumb-index={index}
                aria-label={`Ver imagen ${index + 1}`}
                aria-current={index === currentSlide ? 'true' : 'false'}
                onClick={() => goTo(index)}
              >
                <ResponsivePicture alt="" fallback={slide.fallback} desktopWebp={slide.desktopWebp} mobileWebp={slide.mobileWebp} />
              </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </div>
  );
}
