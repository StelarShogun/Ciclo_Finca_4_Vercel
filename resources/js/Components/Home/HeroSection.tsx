import type { HomeHero } from '@/types/home';

type HeroSectionProps = {
  hero: HomeHero;
};

export function HeroSection({ hero }: HeroSectionProps) {
  return (
    <>
      <div className="cf4-header-spacer" aria-hidden="true" />
      <section className="hero-section" aria-label="Bienvenida a Ciclo Finca 4">
        <div className="hero-backdrop" aria-hidden="true">
          <picture>
            <source
              type="image/avif"
              srcSet="/assets/images/hero/hero-downhill-480.avif 480w, /assets/images/hero/hero-downhill-768.avif 768w, /assets/images/hero/hero-downhill-1280.avif 1280w, /assets/images/hero/hero-downhill-1600.avif 1600w"
              sizes="100vw"
            />
            <source
              type="image/webp"
              srcSet="/assets/images/hero/hero-downhill-480.webp 480w, /assets/images/hero/hero-downhill-768.webp 768w, /assets/images/hero/hero-downhill-1280.webp 1280w, /assets/images/hero/hero-downhill-1600.webp 1600w, /assets/images/hero/hero-downhill-1920.webp 1920w"
              sizes="100vw"
            />
            <img
              src="/assets/images/hero/hero-downhill-1280.jpg"
              alt=""
              width={1920}
              height={1080}
              sizes="100vw"
              fetchPriority="high"
              decoding="async"
            />
          </picture>
        </div>
        <div className="hero-overlay" aria-hidden="true" />

        <div className="hero-container">
          <div className="hero-content">
            <div className="hero-badge">Atención ciclista especializada en tienda</div>

            <h1 className="hero-title">
              {hero.title}
              <strong>{hero.emphasis}</strong>
            </h1>

            <div className="hero-divider" />

            <p className="hero-subtitle">{hero.subtitle}</p>
            <p className="hero-description">{hero.description}</p>

            <div className="hero-actions">
              <a href="/catalog" className="btn btn-primary">
                <span>Ver Catálogo</span>
                <i className="fas fa-arrow-right" aria-hidden="true" />
              </a>

              <a href="#benefits-section" className="btn btn-secondary">
                Cómo funciona el retiro
              </a>
            </div>

            <div className="hero-benefits">
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span className="benefit-text">Asesoría especializada</span>
              </div>
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span className="benefit-text">Preparación en taller</span>
              </div>
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span className="benefit-text">Retiro puntual</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
