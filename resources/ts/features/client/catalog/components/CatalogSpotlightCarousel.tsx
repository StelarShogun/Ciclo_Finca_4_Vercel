import { CatalogProductCard } from '@/features/client/catalog/components/CatalogProductCard';
import type { CatalogSpotlightItem } from '@/types/catalog';

type CatalogSpotlightCarouselProps = {
  items: CatalogSpotlightItem[];
  csrfToken: string;
  isAuthenticated: boolean;
};

export function CatalogSpotlightCarousel({ csrfToken, isAuthenticated, items }: CatalogSpotlightCarouselProps) {
  if (items.length === 0) {
    return null;
  }

  return (
    <section className="catalog-spotlight catalog-spotlight-section" aria-labelledby="catalog-spotlight-heading">
      <div className="catalog-spotlight-inner">
        <header className="catalog-spotlight-header">
          <h2 id="catalog-spotlight-heading" className="catalog-spotlight-title">
            Destacados y novedades
          </h2>
          <p className="catalog-spotlight-subtitle">Productos recomendados y recién incorporados al catálogo.</p>
        </header>
        <section
          className="catalog-spotlight-carousel"
          data-catalog-spotlight-carousel
          data-autoplay-delay="4000"
          aria-roledescription="carrusel"
          aria-label="Productos destacados y novedades del catálogo"
        >
          <div className="swiper catalog-spotlight-swiper">
            <div className="swiper-wrapper">
              {items.map((item, index) => (
                <fieldset
                  key={`${item.kind}-${item.product.id}`}
                  className="swiper-slide catalog-spotlight-slide"
                  aria-roledescription="diapositiva"
                  aria-label={`${index + 1} de ${items.length}: ${item.product.name}`}
                >
                  <CatalogProductCard product={item.product} csrfToken={csrfToken} isAuthenticated={isAuthenticated} />
                </fieldset>
              ))}
            </div>
          </div>
          <button
            type="button"
            className="catalog-spotlight-nav catalog-spotlight-nav--prev"
            data-spotlight-prev
            aria-label="Producto destacado anterior"
          >
            <i className="fas fa-chevron-left" aria-hidden="true" />
          </button>
          <button
            type="button"
            className="catalog-spotlight-nav catalog-spotlight-nav--next"
            data-spotlight-next
            aria-label="Siguiente producto destacado"
          >
            <i className="fas fa-chevron-right" aria-hidden="true" />
          </button>
        </section>
      </div>
    </section>
  );
}
