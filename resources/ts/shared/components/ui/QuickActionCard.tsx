import { Link } from '@inertiajs/react';

type QuickActionCardProps = {
  href: string;
  /** Icono FontAwesome, p. ej. "fa-cash-register". */
  icon: string;
  title: string;
  description: string;
  /** Resalta la acción principal con el verde de marca. */
  primary?: boolean;
};

/**
 * Tarjeta de acción rápida reutilizable (dashboard y otros hubs).
 * Mantiene icono, tamaños, espaciado, bordes, hover y colores consistentes.
 * Estilos en resources/css/admin/dashboard/dashboard.css (.quick-action*).
 */
export function QuickActionCard({ href, icon, title, description, primary = false }: QuickActionCardProps) {
  return (
    <Link href={href} className={`quick-action${primary ? ' quick-action--primary' : ''}`}>
      <span className="quick-action__icon">
        <i className={`fas ${icon}`} aria-hidden="true" />
      </span>
      <span className="quick-action__text">
        <span className="quick-action__title">{title}</span>
        <span className="quick-action__desc">{description}</span>
      </span>
      <i className="fas fa-arrow-right quick-action__arrow" aria-hidden="true" />
    </Link>
  );
}
