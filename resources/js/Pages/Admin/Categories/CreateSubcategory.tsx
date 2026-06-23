import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

type ParentCategory = { category_id: number; name: string };
type SubByParent = Record<string, Array<{ category_id: number; name: string }>>;
type HierarchyRow = { category_id: number; name: string; parent_name: string | null; is_parent: boolean };

type PageProps = {
  categories: ParentCategory[];
  subcategoriesByParent: SubByParent;
  hierarchy: HierarchyRow[];
  pagination: Pagination;
};

export default function CreateSubcategory({ categories, hierarchy, pagination, subcategoriesByParent }: PageProps) {
  useFlashToasts();
  const { data, errors, processing, reset, setData, post } = useForm({
    name: '',
    description: '',
    parent_category_id: '',
  });

  const hint = data.parent_category_id ? subcategoriesByParent[data.parent_category_id] ?? [] : [];

  function submit(event: FormEvent) {
    event.preventDefault();
    post('/categories/subcategories', { onSuccess: () => reset('name', 'description') });
  }

  return (
    <AdminLayout title="Nueva subcategoría">
      <Head title="Nueva subcategoría - Ciclo Finca 4 Admin" />

      <div className="form-container">
        <PageHeader title="Nueva subcategoría" kicker="Categorías">
          <p>Las subcategorías son los tipos concretos dentro de una categoría padre.</p>
        </PageHeader>

        <div className="form-card">
          <form onSubmit={submit} className="form-body">
            <div className="form-group">
              <label htmlFor="name">Nombre de la subcategoría *</label>
              <input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
              {errors.name ? <div className="field-error">{errors.name}</div> : null}
            </div>

            <div className="form-group">
              <label htmlFor="description">Descripción</label>
              <textarea
                id="description"
                rows={3}
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
              />
              {errors.description ? <div className="field-error">{errors.description}</div> : null}
            </div>

            <div className="form-group">
              <label htmlFor="parent_category_id">Categoría padre *</label>
              <select
                id="parent_category_id"
                value={data.parent_category_id}
                onChange={(e) => setData('parent_category_id', e.target.value)}
                required
              >
                <option value="">Selecciona una categoría padre</option>
                {categories.map((category) => (
                  <option key={category.category_id} value={String(category.category_id)}>
                    {category.name}
                  </option>
                ))}
              </select>
              {errors.parent_category_id ? <div className="field-error">{errors.parent_category_id}</div> : null}
              <small className="form-text text-muted">
                ¿No ves la categoría padre? <Link href="/categories/parents/create">Crear categoría padre</Link>.
              </small>
            </div>

            {data.parent_category_id ? (
              <div className="form-group optional">
                <label>Subcategorías actuales del padre seleccionado</label>
                <div className="info-section">
                  {hint.length === 0 ? (
                    <span className="text-muted">Aún no hay subcategorías para este padre.</span>
                  ) : (
                    <ul className="subcategory-hint-list">
                      {hint.map((sub) => (
                        <li key={sub.category_id}>{sub.name}</li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>
            ) : null}

            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={processing}>
                {processing ? 'Guardando…' : 'Crear subcategoría'}
              </button>
              <Link href="/inventory" className="btn btn-secondary">
                Cancelar
              </Link>
            </div>
          </form>
        </div>

        <div className="form-card" style={{ marginTop: 18 }}>
          <h3>Jerarquía de categorías</h3>
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Categoría</th>
                  <th>Padre</th>
                  <th>Tipo</th>
                </tr>
              </thead>
              <tbody>
                {hierarchy.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="empty-cell">
                      No hay categorías registradas.
                    </td>
                  </tr>
                ) : (
                  hierarchy.map((row) => (
                    <tr key={row.category_id}>
                      <td>{row.name}</td>
                      <td>{row.parent_name ?? '—'}</td>
                      <td>{row.is_parent ? 'Padre' : 'Subcategoría'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          <InertiaListPagination pagination={pagination} label="categorías" />
        </div>
      </div>
    </AdminLayout>
  );
}
