import { useId, useState } from 'react';
import type { PropsWithChildren, ReactNode } from 'react';

type CollapsibleSectionProps = PropsWithChildren<{
  /** Título de la zona. */
  title: ReactNode;
  /** Clase de icono FontAwesome (p. ej. "fa-box"). */
  icon?: string;
  /** Texto auxiliar bajo el título dentro del cuerpo. */
  description?: ReactNode;
  /** Contenido alineado a la derecha del header (badges, contadores). */
  aside?: ReactNode;
  /** Si arranca abierta (por defecto sí). */
  defaultOpen?: boolean;
  className?: string;
}>;

/**
 * Zona de formulario expandible/retráctil — recupera el patrón "form-section"
 * previo a la migración. Accesible: el toggle es un <button> con aria-expanded
 * y aria-controls; el cuerpo se oculta del árbol de accesibilidad al colapsar.
 */
export function CollapsibleSection({
  title,
  icon,
  description,
  aside,
  defaultOpen = true,
  className = '',
  children,
}: CollapsibleSectionProps) {
  const [open, setOpen] = useState(defaultOpen);
  const bodyId = useId();

  return (
    <section className={`cf4-section${open ? '' : ' is-collapsed'} ${className}`.trim()}>
      <button
        type="button"
        className="cf4-section__toggle"
        aria-expanded={open}
        aria-controls={bodyId}
        onClick={() => setOpen((value) => !value)}
      >
        <span className="cf4-section__title">
          {icon ? <i className={`fas ${icon} cf4-section__icon`} aria-hidden="true" /> : null}
          {title}
        </span>
        {aside ? <span className="cf4-section__aside">{aside}</span> : null}
        <i className="fas fa-chevron-down cf4-section__chevron" aria-hidden="true" />
      </button>
      <div id={bodyId} className="cf4-section__body" role="region" hidden={!open}>
        {description ? <p className="cf4-section__desc">{description}</p> : null}
        <div className="cf4-section__content">{children}</div>
      </div>
    </section>
  );
}
