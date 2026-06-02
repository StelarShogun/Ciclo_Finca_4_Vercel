import { Link } from '@inertiajs/react';
import { useRef } from 'react';

import type { HomeCategory } from '@/types/home';

type CategoryPreviewProps = {
  categories: HomeCategory[];
};

export function CategoryPreview({ categories }: CategoryPreviewProps) {
  const trackRef = useRef<HTMLDivElement>(null);

  if (categories.length === 0) {
    return null;
  }

  function scroll(direction: 'prev' | 'next') {
    const track = trackRef.current;
    if (!track) {
      return;
    }

    const firstSlide = track.querySelector<HTMLElement>('.category-slide');
    const step = firstSlide ? firstSlide.getBoundingClientRect().width + 18 : track.clientWidth * 0.85;
    track.scrollBy({ left: direction === 'next' ? step : -step, behavior: 'smooth' });
  }

  return (
    <section className="categories-section" aria-labelledby="categories-heading">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title" id="categories-heading">
            Explora por categoría
          </h2>
          <p className="section-subtitle">Desliza para ver cada familia de productos y sus subcategorías</p>
        </div>

        <div className="categories-top-actions">
          <Link href="/catalog" className="categories-all-link">
            <i className="fas fa-bicycle" aria-hidden="true" />
            Ver todo el catálogo
          </Link>
          <span className="categories-swipe-hint">
            <i className="fas fa-hand-point-right" aria-hidden="true" />
            Desliza para descubrir más
          </span>
        </div>

        <div className="categories-carousel-wrap">
          <button
            type="button"
            className="categories-carousel-btn categories-carousel-btn--prev"
            aria-label="Categoría anterior"
            onClick={() => scroll('prev')}
          >
            <i className="fas fa-chevron-left" aria-hidden="true" />
          </button>
          <div className="categories-carousel" role="region" aria-roledescription="carrusel" aria-label="Categorías de productos">
            <div className="categories-carousel-track" ref={trackRef}>
              {categories.map((category) => (
                <article className="category-slide" key={category.id}>
                  <div className="category-slide-card">
                    <Link href={category.url} className="category-slide-main">
                      <div className="category-icon category-icon--lg" aria-hidden="true">
                        <i className={category.iconClass} />
                      </div>
                      <h3 className="category-name">{category.name}</h3>
                      {category.description ? <p className="category-slide-tagline">{category.description}</p> : null}
                      <span className="category-slide-cta">
                        Ver todo en {category.name}
                        <i className="fas fa-arrow-right" aria-hidden="true" />
                      </span>
                    </Link>

                    {category.children.length > 0 ? (
                      <div className="category-subchips" role="group" aria-label={`Subcategorías de ${category.name}`}>
                        {category.children.map((child) => (
                          <Link href={child.url} className="category-subchip" key={child.id}>
                            {child.name}
                          </Link>
                        ))}
                      </div>
                    ) : null}
                  </div>
                </article>
              ))}
            </div>
          </div>
          <button
            type="button"
            className="categories-carousel-btn categories-carousel-btn--next"
            aria-label="Siguiente categoría"
            onClick={() => scroll('next')}
          >
            <i className="fas fa-chevron-right" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>
  );
}
