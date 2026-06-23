import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/products/inventory.css';

import type {
  BrandOption,
  CategoryOption,
  InventoryFilters,
  InventoryProduct,
  SubByParent,
  SupplierOption,
} from './types';
import { StockModal } from './components/StockModal';
import type { StockTarget } from './components/StockModal';
import { ViewProductModal } from './components/ViewProductModal';
import { ProductFormModal } from './components/ProductFormModal';
import { ImportModal } from './components/ImportModal';

type PageProps = {
  products: InventoryProduct[];
  pagination: Pagination;
  filters: InventoryFilters;
  categories: CategoryOption[];
  subcategoriesByParent: SubByParent;
  brands: BrandOption[];
  suppliers: SupplierOption[];
  lowStockProductsCount: number;
  exportQuery: string;
  blobUploadUrl: string;
};

const currency = new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC', maximumFractionDigits: 0 });

export default function Index(props: PageProps) {
  const {
    blobUploadUrl,
    brands,
    categories,
    exportQuery,
    filters,
    lowStockProductsCount,
    pagination,
    products,
    subcategoriesByParent,
    suppliers,
  } = props;

  useFlashToasts();
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { showToast } = useToast();
  const { confirm } = useConfirmDialog();

  const [view, setView] = useState<'table' | 'grid'>('table');
  const [f, setF] = useState<InventoryFilters>(filters);
  const [exportOpen, setExportOpen] = useState(false);

  const [stockTarget, setStockTarget] = useState<StockTarget | null>(null);
  const [viewId, setViewId] = useState<number | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [importOpen, setImportOpen] = useState(false);

  const subcategories = f.parent_category_id ? subcategoriesByParent[f.parent_category_id] ?? [] : [];

  function applyFilters(event: FormEvent) {
    event.preventDefault();
    router.get('/inventory', { ...f }, { preserveScroll: true, preserveState: true, replace: true });
  }

  function clearFilters() {
    setF({ search: '', parent_category_id: '', subcategory_id: '', stock_status: '', status: '' });
    router.get('/inventory', {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  function reload() {
    router.reload({ only: ['products', 'pagination', 'lowStockProductsCount'] });
  }

  function openNew() {
    setEditingId(null);
    setFormOpen(true);
  }

  function openEdit(id: number) {
    setEditingId(id);
    setFormOpen(true);
  }

  async function toggleFeatured(product: InventoryProduct) {
    try {
      const r = await fetch(`/products/${product.product_id}/toggle-featured`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      const data = await r.json().catch(() => ({}));
      if (!r.ok || data.success === false) {
        showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo actualizar el destacado.' });
        return;
      }
      reload();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión.' });
    }
  }

  async function setActive(product: InventoryProduct, activate: boolean) {
    if (!activate) {
      const ok = await confirm({
        title: '¿Desactivar producto?',
        text: `Se desactivará "${product.name}".`,
        icon: 'warning',
        confirmText: 'Sí, desactivar',
        cancelText: 'Cancelar',
      });
      if (!ok) return;
    }
    const url = `/products/${product.product_id}${activate ? '/activate' : ''}`;
    try {
      const r = await fetch(url, {
        method: activate ? 'PATCH' : 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      const data = await r.json().catch(() => ({}));
      if (!r.ok || data.success === false) {
        showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo actualizar el estado.' });
        return;
      }
      showToast({ variant: 'success', title: activate ? 'Producto reactivado' : 'Producto desactivado' });
      reload();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión.' });
    }
  }

  const exportLinks = [
    { href: `/inventory/export/bundle${exportQuery}${exportQuery ? '&' : '?'}scope=all`, label: 'Catálogo completo (ZIP + imágenes)' },
    { href: `/inventory/export/bundle${exportQuery}`, label: 'ZIP con filtros actuales' },
    { href: `/inventory/export/json${exportQuery}`, label: 'JSON (datos)' },
    { href: `/inventory/export/xml${exportQuery}`, label: 'XML' },
    { href: `/inventory/export/excel${exportQuery}`, label: 'Excel' },
    { href: `/inventory/export/pdf${exportQuery}`, label: 'PDF' },
  ];

  function renderActions(product: InventoryProduct) {
    return (
      <div className="actions-container">
        <button type="button" className="action-btn view" title="Ver" onClick={() => setViewId(product.product_id)}>
          <i className="fas fa-eye" aria-hidden="true" />
        </button>
        <button type="button" className="action-btn edit" title="Editar" onClick={() => openEdit(product.product_id)}>
          <i className="fas fa-edit" aria-hidden="true" />
        </button>
        <button
          type="button"
          className="action-btn stock-adjust"
          title="Agregar stock"
          onClick={() => setStockTarget({ product_id: product.product_id, name: product.name, stock: product.stock, action: 'add' })}
        >
          <i className="fas fa-plus-circle" aria-hidden="true" />
        </button>
        <button
          type="button"
          className="action-btn stock-adjust"
          title="Retirar stock"
          onClick={() => setStockTarget({ product_id: product.product_id, name: product.name, stock: product.stock, action: 'remove' })}
        >
          <i className="fas fa-minus-circle" aria-hidden="true" />
        </button>
        {product.status === 'inactive' ? (
          <button type="button" className="action-btn activate" title="Reactivar" onClick={() => setActive(product, true)}>
            <i className="fas fa-check-circle" aria-hidden="true" />
          </button>
        ) : (
          <button type="button" className="action-btn delete" title="Desactivar" onClick={() => setActive(product, false)}>
            <i className="fas fa-ban" aria-hidden="true" />
          </button>
        )}
      </div>
    );
  }

  function thumb(product: InventoryProduct, size: number) {
    if (product.uses_placeholder) {
      return (
        <div className="product-media-placeholder" role="img" aria-label={`Sin imagen: ${product.name}`}>
          <i className={product.placeholder_icon} aria-hidden="true" />
        </div>
      );
    }
    return <img src={product.image_url} alt={product.name} width={size} height={size} />;
  }

  return (
    <AdminLayout title="Inventario">
      <Head title="Inventario - Ciclo Finca 4 Admin" />

      <div className="inventory-page">
        <PageHeader
          title="Inventario"
          kicker="Productos"
          actions={
            <div className="inventory-header-actions">
              <button type="button" className="btn btn-primary" onClick={openNew}>
                <i className="fas fa-plus" aria-hidden="true" /> Nuevo producto
              </button>
              <div className="inventory-export-dropdown">
                <button type="button" className="btn btn-secondary" onClick={() => setExportOpen((o) => !o)}>
                  <i className="fas fa-file-export" aria-hidden="true" /> Exportar
                </button>
                {exportOpen ? (
                  <div className="inventory-export-menu" role="menu">
                    {exportLinks.map((link) => (
                      <a key={link.label} href={link.href} className="inventory-export-menu__item" role="menuitem">
                        {link.label}
                      </a>
                    ))}
                  </div>
                ) : null}
              </div>
              <button type="button" className="btn btn-secondary" onClick={() => setImportOpen(true)}>
                <i className="fas fa-file-import" aria-hidden="true" /> Importar
              </button>
            </div>
          }
        />

        <section className="inventory-kpi-grid" aria-label="Resumen de inventario">
          <div className="inventory-kpi-card">
            <div className="inventory-kpi-card-head">
              <h3>Stock bajo</h3>
              <i className="fas fa-box-open" aria-hidden="true" />
            </div>
            <p className="inventory-kpi-card-value">{lowStockProductsCount.toLocaleString('es-CR')}</p>
          </div>
        </section>

        <form className="cf4-filters filter-form" onSubmit={applyFilters}>
          <div className="filter-group">
            <label htmlFor="parent-category-filter">Categoría</label>
            <select
              id="parent-category-filter"
              value={f.parent_category_id}
              onChange={(e) => setF({ ...f, parent_category_id: e.target.value, subcategory_id: '' })}
            >
              <option value="">Todas las categorías</option>
              {categories.map((c) => (
                <option key={c.category_id} value={String(c.category_id)}>{c.name}</option>
              ))}
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="subcategory-filter">Subcategoría</label>
            <select
              id="subcategory-filter"
              value={f.subcategory_id}
              onChange={(e) => setF({ ...f, subcategory_id: e.target.value })}
              disabled={!f.parent_category_id}
            >
              <option value="">Todos los tipos</option>
              {subcategories.map((s) => (
                <option key={s.category_id} value={String(s.category_id)}>{s.name}</option>
              ))}
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="stock-filter">Estado de stock</label>
            <select id="stock-filter" value={f.stock_status} onChange={(e) => setF({ ...f, stock_status: e.target.value })}>
              <option value="">Todos los estados</option>
              <option value="in-stock">En stock</option>
              <option value="low">Stock bajo</option>
              <option value="out">Sin stock</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="status-filter">Estado del producto</label>
            <select id="status-filter" value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })}>
              <option value="">Todos los estados</option>
              <option value="active">Activo</option>
              <option value="inactive">Inactivo</option>
              <option value="out_of_stock">Agotado</option>
              <option value="discontinued">Descontinuado</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="search-filter">Buscar</label>
            <input
              id="search-filter"
              type="text"
              placeholder="Nombre o código"
              value={f.search}
              onChange={(e) => setF({ ...f, search: e.target.value })}
            />
          </div>
          <div className="filter-actions">
            <button type="submit" className="btn btn-primary">
              <i className="fas fa-search" aria-hidden="true" /> Filtrar
            </button>
            <button type="button" className="btn btn-secondary" onClick={clearFilters}>
              Limpiar
            </button>
          </div>
        </form>

        <section className="products-section">
          <div className="products-header">
            <div className="products-count">
              <i className="fas fa-box-open products-count__icon" aria-hidden="true" />
              <span>
                <strong>{pagination.total}</strong> producto{pagination.total === 1 ? '' : 's'}
              </span>
            </div>
            <div className="view-options">
              <button
                type="button"
                className={`view-btn ${view === 'table' ? 'active' : ''}`}
                title="Vista de tabla"
                onClick={() => setView('table')}
              >
                <i className="fas fa-table" aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`view-btn ${view === 'grid' ? 'active' : ''}`}
                title="Vista de tarjetas"
                onClick={() => setView('grid')}
              >
                <i className="fas fa-th" aria-hidden="true" />
              </button>
            </div>
          </div>

          {products.length === 0 ? (
            <div className="alert alert-info" style={{ margin: '16px 0' }}>
              <i className="fas fa-info-circle" aria-hidden="true" /> No hay productos que coincidan con los filtros.
            </div>
          ) : view === 'table' ? (
            <div className="products-table table-view">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Disponibilidad</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th className="admin-table__col--actions">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {products.map((product) => (
                    <tr key={product.product_id}>
                      <td className="product-cell">
                        <div className="product-cell-content">
                          <div className="product-thumb-wrap product-thumb-wrap--table">{thumb(product, 48)}</div>
                          <div>
                            <div className="product-name">{product.name}</div>
                            <div className="product-sku text-muted">{product.sku}</div>
                          </div>
                        </div>
                      </td>
                      <td>{product.category_name}</td>
                      <td>
                        <span className={product.stock_badge_class}>{product.stock}</span>
                      </td>
                      <td>{product.availability_label}</td>
                      <td>{currency.format(Number(product.price))}</td>
                      <td>
                        <span className={`status-badge ${product.status_class}`}>{product.status_label}</span>
                      </td>
                      <td className="admin-table__col--actions">{renderActions(product)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="products-grid">
              {products.map((product) => (
                <div className="product-card" key={product.product_id}>
                  <div className="product-card-header">
                    <div className="product-thumb-wrap product-thumb-wrap--card">{thumb(product, 60)}</div>
                    <button
                      type="button"
                      className={`featured-star-btn ${product.is_featured ? 'is-featured' : ''}`}
                      aria-pressed={product.is_featured}
                      title="Destacado en tienda"
                      onClick={() => toggleFeatured(product)}
                    >
                      <i className={`${product.is_featured ? 'fas' : 'far'} fa-star`} aria-hidden="true" />
                    </button>
                  </div>
                  <div className="product-card-info">
                    <h4>{product.name}</h4>
                    <span className="sku">SKU: {product.sku}</span>
                    <div className="product-card-meta">
                      <span className={`status-badge ${product.status_class}`}>{product.status_label}</span>
                      <span>{currency.format(Number(product.price))}</span>
                    </div>
                    <div className="product-card-actions">{renderActions(product)}</div>
                  </div>
                </div>
              ))}
            </div>
          )}

          <InertiaListPagination pagination={pagination} label="inventario" />
        </section>
      </div>

      <StockModal target={stockTarget} csrfToken={csrfToken} onClose={() => setStockTarget(null)} onDone={() => { setStockTarget(null); reload(); }} />
      <ViewProductModal productId={viewId} onClose={() => setViewId(null)} />
      <ProductFormModal
        open={formOpen}
        editingId={editingId}
        csrfToken={csrfToken}
        categories={categories}
        subcategoriesByParent={subcategoriesByParent}
        brands={brands}
        suppliers={suppliers}
        onClose={() => setFormOpen(false)}
        onSaved={() => { setFormOpen(false); reload(); }}
      />
      <ImportModal
        open={importOpen}
        blobUploadUrl={blobUploadUrl}
        csrfToken={csrfToken}
        onClose={() => setImportOpen(false)}
        onFinished={reload}
      />
    </AdminLayout>
  );
}
