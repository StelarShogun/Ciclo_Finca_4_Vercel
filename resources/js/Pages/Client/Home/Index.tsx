import { Head, Link, usePage } from '@inertiajs/react';
import '../../../../css/client/clients-home.css';
import { useEffect } from 'react';
import type { ReactNode } from 'react';

import { CategoryPreview } from '@/features/client/home/components/CategoryPreview';
import { FeaturedProducts } from '@/features/client/home/components/FeaturedProducts';
import { HeroSection } from '@/features/client/home/components/HeroSection';
import { HomeSection } from '@/features/client/home/components/HomeSection';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import type { HomeCategory, HomeHero, HomeProduct } from '@/types/home';
import type { InertiaSharedProps } from '@/types/models';

type ClientHomePageProps = {
  featuredProducts: HomeProduct[];
  categories: HomeCategory[];
  showGuestRegisterCta: boolean;
  hero: HomeHero;
};

export default function ClientHomeIndex({
  categories,
  featuredProducts,
  hero,
  showGuestRegisterCta,
}: ClientHomePageProps) {
  const { auth, csrfToken } = usePage<InertiaSharedProps>().props;
  const isClientAuthenticated = auth.client !== null;

  useEffect(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return undefined;
    }

    const sections = document.querySelectorAll(
      '.home-trust-strip, .featured-section, .categories-section, .benefits-section, .how-it-works-section, .testimonials-section, .final-cta-section',
    );
    sections.forEach((section) => section.classList.add('home-reveal'));

    if (!('IntersectionObserver' in window)) {
      sections.forEach((section) => section.classList.add('is-visible'));
      return undefined;
    }

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.14 },
    );

    sections.forEach((section) => observer.observe(section));

    return () => observer.disconnect();
  }, []);

  return (
    <ClientLayout>
      <Head title="Inicio - Ciclo Finca 4">
        <link
          rel="preload"
          as="image"
          type="image/avif"
          fetchPriority="high"
          imageSizes="100vw"
          imageSrcSet="/assets/images/hero/hero-downhill-480.avif 480w, /assets/images/hero/hero-downhill-768.avif 768w, /assets/images/hero/hero-downhill-1280.avif 1280w, /assets/images/hero/hero-downhill-1600.avif 1600w"
          href="/assets/images/hero/hero-downhill-768.avif"
        />
      </Head>

      <HeroSection hero={hero} />
      <TrustStrip />
      <FeaturedProducts products={featuredProducts} isAuthenticated={isClientAuthenticated} csrfToken={csrfToken} />
      <CategoryPreview categories={categories} />
      <BenefitsSection />
      <HowItWorksSection isAuthenticated={isClientAuthenticated} showGuestRegisterCta={showGuestRegisterCta} />
      <TestimonialsSection />
      <FinalCta isAuthenticated={isClientAuthenticated} showGuestRegisterCta={showGuestRegisterCta} />
    </ClientLayout>
  );
}

function TrustStrip() {
  return (
    <section className="home-trust-strip" aria-label="Indicadores de confianza">
      <div className="container">
        <div className="trust-items" role="list">
          <TrustItem icon="fas fa-users" title="Atención experta" text="acompañamiento personalizado" />
          <TrustItem icon="fas fa-tools" title="Taller propio" text="preparación técnica incluida" />
          <TrustItem icon="fas fa-star" title="4.9/5" text="satisfacción de servicio" />
        </div>
      </div>
    </section>
  );
}

function TrustItem({ icon, text, title }: { icon: string; text: string; title: string }) {
  return (
    <div className="trust-item" role="listitem">
      <i className={icon} aria-hidden="true" />
      <div>
        <strong>{title}</strong>
        <span>{text}</span>
      </div>
    </div>
  );
}

function BenefitsSection() {
  const benefits = [
    ['fas fa-tools', 'Taller propio', 'Revisión, ajuste y preparación en tienda para que salgas rodando con confianza.'],
    ['fas fa-user-tie', 'Asesoría personalizada', 'Te orientamos para elegir la opción correcta según tu ruta, MTB o gravel.'],
    ['fas fa-warehouse', 'Inventario y disponibilidad', 'Confirmamos disponibilidad y te decimos cuándo está listo para retirar.'],
    ['fas fa-shield-alt', 'Soporte post-retirada', 'Acompañamos después de tu retiro con recomendaciones de uso y cuidados.'],
  ] as const;

  return (
    <HomeSection
      className="benefits-section"
      title="Encargos listos para retirar"
      subtitle="Elige en el catálogo y te ayudamos a dejar tu compra lista para retirar en tienda."
      ariaLabel="Beneficios del servicio"
    >
      <div className="benefits-grid" role="list" aria-label="Beneficios principales">
        {benefits.map(([icon, title, description]) => (
          <div className="benefit-card" role="listitem" key={title}>
            <i className={icon} aria-hidden="true" />
            <h3 className="benefit-title">{title}</h3>
            <p className="benefit-desc">{description}</p>
          </div>
        ))}
      </div>
    </HomeSection>
  );
}

function HowItWorksSection({
  isAuthenticated,
  showGuestRegisterCta,
}: {
  isAuthenticated: boolean;
  showGuestRegisterCta: boolean;
}) {
  return (
    <HomeSection
      className="how-it-works-section"
      title="Cómo funciona"
      subtitle="Tres pasos simples para dejar tu encargo listo para retirar en tienda."
      ariaLabel="Cómo funciona el encargo"
    >
      <div className="steps-grid" role="list" aria-label="Pasos del encargo">
        <StepCard number="1" title="Explora el catálogo" text="Busca bicicletas, componentes y accesorios según tu estilo de ciclismo.">
          <Link href="/catalog" className="btn btn-primary btn-lg step-cta">
            Ver catálogo
          </Link>
        </StepCard>
        <StepCard
          number="2"
          title="Deja tu solicitud"
          text="Agrega los productos al carrito y finaliza tu solicitud para que podamos confirmarte disponibilidad."
        >
          {isAuthenticated ? (
            <Link href="/cart" className="btn btn-secondary btn-lg step-cta">
              Ir al carrito
            </Link>
          ) : showGuestRegisterCta ? (
            <Link href="/login" className="btn btn-secondary btn-lg step-cta">
              Inicia sesión
            </Link>
          ) : null}
        </StepCard>
        <StepCard number="3" title="Retira en tienda" text="Te confirmamos cuando esté listo para retirar y coordinamos tu visita.">
          <div className="step-note">
            <i className="fas fa-clock" aria-hidden="true" />
            <span>Retiro en tienda.</span>
          </div>
        </StepCard>
      </div>
    </HomeSection>
  );
}

function StepCard({
  children,
  number,
  text,
  title,
}: {
  children?: ReactNode;
  number: string;
  text: string;
  title: string;
}) {
  return (
    <div className="step-card" role="listitem">
      <div className="step-number">{number}</div>
      <h3 className="step-title">{title}</h3>
      <p className="step-desc">{text}</p>
      {children}
    </div>
  );
}

function TestimonialsSection() {
  const testimonials = [
    ['Me orientaron con la talla y ajustes. Retiré listo y pude salir ese mismo día.', 'Mauricio R. · MTB recreativo'],
    ['Excelente atención en tienda. Respondieron mis dudas y dejaron todo preparado.', 'Andrea M. · Ruta urbana'],
    ['Encargo claro y puntual para retirar. Gran asesoría en componentes.', 'Carlos G. · Gravel fin de semana'],
  ] as const;

  return (
    <HomeSection
      className="testimonials-section"
      title="Clientes que confían en nosotros"
      subtitle="Experiencias reales en atención y preparación de encargos."
      ariaLabel="Testimonios de clientes"
    >
      <div className="testimonials-grid" role="list" aria-label="Lista de testimonios">
        {testimonials.map(([quote, author]) => (
          <div className="testimonial-card" role="listitem" key={author}>
            <div className="testimonial-stars" aria-hidden="true">
              {Array.from({ length: 5 }, (_, index) => (
                <i className="fas fa-star" key={index} />
              ))}
            </div>
            <p className="testimonial-quote">"{quote}"</p>
            <p className="testimonial-author">{author}</p>
          </div>
        ))}
      </div>
    </HomeSection>
  );
}

function FinalCta({
  isAuthenticated,
  showGuestRegisterCta,
}: {
  isAuthenticated: boolean;
  showGuestRegisterCta: boolean;
}) {
  return (
    <section className="final-cta-section" aria-label="Llamado a la acción final">
      <div className="container">
        <div className="final-cta-card">
          <div>
            <h2 className="final-cta-title">¿Listo para tu próximo rodaje?</h2>
            <p className="final-cta-subtitle">Explora el catálogo y deja tu solicitud para prepararlo en tienda con respaldo técnico.</p>
          </div>
          <div className="final-cta-actions">
            <Link href="/catalog" className="btn btn-primary btn-lg">
              <i className="fas fa-bicycle" aria-hidden="true" />
              Ver Catálogo
            </Link>
            {isAuthenticated ? (
              <Link href="/cart" className="btn btn-secondary btn-lg">
                <i className="fas fa-shopping-cart" aria-hidden="true" />
                Ir al carrito
              </Link>
            ) : showGuestRegisterCta ? (
              <Link href="/register" className="btn btn-secondary btn-lg">
                <i className="fas fa-user-plus" aria-hidden="true" />
                Crear cuenta
              </Link>
            ) : null}
          </div>
        </div>
      </div>
    </section>
  );
}
