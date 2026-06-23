import type { FormEvent, PropsWithChildren, ReactNode } from 'react';

type FiltersSectionProps = PropsWithChildren<{
  title?: string;
  onSubmit: (event: FormEvent) => void;
  onClear: () => void;
  footer?: ReactNode;
  after?: ReactNode;
  formId?: string;
  submitLabel?: string;
  clearLabel?: string;
}>;

/**
 * Standard admin listing filters — mirrors resources/views/admin/partials/filters.blade.php
 * (.filters-section > form.admin-filters-form > .filters-grid > .filters-grid__fields).
 * Children are the individual .filter-group inputs.
 */
export function FiltersSection({
  title = 'Filtros de Búsqueda',
  onSubmit,
  onClear,
  footer,
  after,
  formId,
  submitLabel = 'Aplicar Filtros',
  clearLabel = 'Limpiar',
  children,
}: FiltersSectionProps) {
  return (
    <div className="filters-section">
      <div className="filters-header">
        <h2 className="filters-title">{title}</h2>
      </div>

      <form method="GET" onSubmit={onSubmit} id={formId} className="admin-filters-form filter-form">
        <div className="filters-grid">
          <div className="filters-grid__fields">{children}</div>

          <div className="filter-group filter-buttons">
            <button type="submit" className="btn btn-primary filter-btn">
              <i className="fas fa-search" aria-hidden="true" /> {submitLabel}
            </button>
            <button type="button" className="btn btn-primary filter-btn" onClick={onClear}>
              <i className="fas fa-times" aria-hidden="true" /> {clearLabel}
            </button>
          </div>
        </div>

        {footer ? <div className="filters-footer">{footer}</div> : null}
      </form>

      {after ?? null}
    </div>
  );
}
