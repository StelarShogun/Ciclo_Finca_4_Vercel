import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/reports/reports-hub.css';

const nf = new Intl.NumberFormat('es-CR');

type ProductRow = {
  product_id: number;
  sku: string;
  name: string;
  category_name: string;
  supplier_name: string | null;
  stock_badge_class: string;
  stock_label: string;
  stock_current: number;
};

type PageProps = { products: ProductRow[]; pagination: Pagination; filters: { search: string } };

export default function MovementsIndex({ products, pagination, filters }: PageProps) {
  const [search, setSearch] = useState(filters.search);

  function submit(event: FormEvent) {
    event.preventDefault();
    router.get('/inventory/movements', search.trim() ? { search: search.trim() } : {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  function clear() {
    setSearch('');
    router.get('/inventory/movements', {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  return (
    <AdminLayout title="Movimientos de inventario">
      <Head title="Movimientos de inventario - Reportes" />

      <div className="reports-hub">
        <nav className="reports-breadcrumb" aria-label="Migas de pan">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <span>Movimientos de inventario</span>
        </nav>

        <PageHeader title="Movimientos de inventario" kicker="Reportes">
          <p>Selecciona un producto para consultar su historial de entradas, salidas y devoluciones.</p>
        </PageHeader>

        <FiltersSection onSubmit={submit} onClear={clear} submitLabel="Filtrar">
          <div className="filter-group filters-grow">
            <label htmlFor="inv-search-input">Buscar producto</label>
            <input type="search" name="search" id="inv-search-input" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nombre o SKU…" autoComplete="off" />
          </div>
        </FiltersSection>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Producto</th>
                  <th>Categoría</th>
                  <th>Proveedor</th>
                  <th>Estado stock</th>
                  <th className="text-end">Stock actual</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {products.length === 0 ? (
                  <tr><td colSpan={7} className="empty-cell">Ningún producto coincide con la búsqueda.</td></tr>
                ) : (
                  products.map((p) => (
                    <tr key={p.product_id}>
                      <td><strong className="po-number">{p.sku}</strong></td>
                      <td>{p.name}</td>
                      <td>{p.category_name}</td>
                      <td>{p.supplier_name ? <span><i className="fas fa-truck-fast" style={{ fontSize: '0.75rem', marginRight: '0.3rem', opacity: 0.6 }} aria-hidden="true" /> {p.supplier_name}</span> : <span className="text-muted">—</span>}</td>
                      <td><span className={`order-status-pill ${p.stock_badge_class}`}>{p.stock_label}</span></td>
                      <td className="text-end"><strong>{nf.format(p.stock_current)}</strong> <span style={{ fontSize: '0.78rem', color: '#6b7280' }}>unid.</span></td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <a href={`/inventory/movements/${p.product_id}`} className="action-btn secondary" data-tooltip="Ver movimientos" aria-label="Ver movimientos">
                            <i className="fas fa-clock-rotate-left" aria-hidden="true" />
                          </a>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            <InertiaListPagination pagination={pagination} label="productos" />
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
