import { useRef } from 'react';

import { ProductCard } from '@/Components/Home/ProductCard';
import { HomeSection } from '@/Components/Home/HomeSection';
import type { HomeProduct } from '@/types/home';

type FeaturedProductsProps = {
  products: HomeProduct[];
  isAuthenticated: boolean;
  csrfToken: string;
};

export function FeaturedProducts({ products, isAuthenticated, csrfToken }: FeaturedProductsProps) {
  const trackRef = useRef<HTMLDivElement>(null);

  function scrollByCard(direction: 'prev' | 'next') {
    const track = trackRef.current;
    if (!track) {
      return;
    }

    const cardWidth = track.querySelector<HTMLElement>('.product-card')?.getBoundingClientRect().width ?? 280;
    track.scrollBy({ left: direction === 'next' ? cardWidth + 18 : -(cardWidth + 18), behavior: 'smooth' });
  }

  return (
    <HomeSection
      className="featured-section"
      title="Productos Destacados"
      subtitle="Lo más buscado por nuestros clientes esta semana."
    >
      {products.length > 0 ? (
        <>
          <div
            className="catalog-spotlight-carousel featured-products-carousel"
            role="region"
            aria-roledescription="carrusel"
            aria-label="Productos destacados"
          >
            <div className="swiper catalog-spotlight-swiper">
              <div className="swiper-wrapper categories-carousel-track" ref={trackRef}>
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} isAuthenticated={isAuthenticated} csrfToken={csrfToken} />
                ))}
              </div>
            </div>

            <button
              type="button"
              className="catalog-spotlight-nav catalog-spotlight-nav--prev"
              aria-label="Producto destacado anterior"
              onClick={() => scrollByCard('prev')}
            >
              <i className="fas fa-chevron-left" aria-hidden="true" />
            </button>
            <button
              type="button"
              className="catalog-spotlight-nav catalog-spotlight-nav--next"
              aria-label="Siguiente producto destacado"
              onClick={() => scrollByCard('next')}
            >
              <i className="fas fa-chevron-right" aria-hidden="true" />
            </button>
          </div>

          <div className="section-footer">
            <a href="/catalog" className="btn btn-secondary">
              Ver Todos los Productos
              <i className="fas fa-arrow-right" aria-hidden="true" />
            </a>
          </div>
        </>
      ) : (
        <div className="empty-state">
          <i className="fas fa-box-open" aria-hidden="true" />
          <p>No hay productos destacados disponibles en este momento</p>
        </div>
      )}
    </HomeSection>
  );
}
