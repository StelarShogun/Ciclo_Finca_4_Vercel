import { Head, Link } from '@inertiajs/react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

type ProductRow = {
  product_id: number;
  name: string;
  parent_category: string | null;
  subcategory: string | null;
  values: Array<{ id: number; dimension: string | null; value: string }>;
};

type PageProps = {
  products: ProductRow[];
  pagination: Pagination;
};

export default function Index({ pagination, products }: PageProps) {
  useFlashToasts();

  return (
    <AdminLayout title="Características por producto">
      <Head title="Características por producto - Ciclo Finca 4 Admin" />

      <div className="product-classifications-container">
        <PageHeader
          title="Características por producto"
          kicker="Clasificación"
          actions={
            <Link href="/inventory" className="btn btn-secondary">
              <i className="fas fa-arrow-left" aria-hidden="true" /> Inventario
            </Link>
          }
        >
          <p>Asigná valores de atributos (según el tipo concreto del producto).</p>
        </PageHeader>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Categoría → Subcategoría</th>
                  <th>Atributo → valor</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {products.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="empty-cell">
                      No hay productos para clasificar.
                    </td>
                  </tr>
                ) : (
                  products.map((product) => (
                    <tr key={product.product_id}>
                      <td>{product.name}</td>
                      <td>
                        {(product.parent_category ?? '—')} → {(product.subcategory ?? '—')}
                      </td>
                      <td>
                        {product.values.length === 0 ? (
                          <span className="text-muted">Sin atributos asignados</span>
                        ) : (
                          <ul className="classification-values-list">
                            {product.values.map((value) => (
                              <li key={value.id}>
                                <strong>{value.dimension ?? '—'}:</strong> {value.value}
                              </li>
                            ))}
                          </ul>
                        )}
                      </td>
                      <td className="admin-table__col--actions">
                        <Link
                          href={`/products/${product.product_id}/classifications/edit`}
                          className="action-btn edit"
                          title="Editar atributos"
                        >
                          <i className="fas fa-edit" aria-hidden="true" />
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <InertiaListPagination pagination={pagination} label="productos" />
        </div>
      </div>
    </AdminLayout>
  );
}
