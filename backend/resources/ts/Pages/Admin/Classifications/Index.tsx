import { Head, Link } from '@inertiajs/react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

type Subcategory = {
  category_id: number;
  name: string;
  parent_name: string | null;
  dimensions_count: number;
};

type PageProps = {
  subcategories: Subcategory[];
  pagination: Pagination;
};

export default function Index({ pagination, subcategories }: PageProps) {
  useFlashToasts();

  return (
    <AdminLayout title="Opciones por tipo">
      <Head title="Opciones por tipo - Ciclo Finca 4 Admin" />

      <div className="classifications-catalog">
        <PageHeader title="Opciones por tipo" kicker="Clasificación">
          <p>Definí los atributos (Color, Talla…) y sus valores para cada tipo de producto.</p>
        </PageHeader>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Tipo (subcategoría)</th>
                  <th>Categoría padre</th>
                  <th>Atributos</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {subcategories.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="empty-cell">
                      No hay subcategorías registradas.
                    </td>
                  </tr>
                ) : (
                  subcategories.map((sub) => (
                    <tr key={sub.category_id}>
                      <td>{sub.name}</td>
                      <td>{sub.parent_name ?? '—'}</td>
                      <td>{sub.dimensions_count}</td>
                      <td className="admin-table__col--actions">
                        <Link href={`/classifications/catalog/${sub.category_id}`} className="btn btn-secondary">
                          <i className="fas fa-sliders-h" aria-hidden="true" /> Gestionar
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <InertiaListPagination pagination={pagination} label="subcategorías" />
        </div>
      </div>
    </AdminLayout>
  );
}
