import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Modal } from '@/shared/components/ui/Modal';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { Breadcrumbs } from '@/shared/components/ui/Breadcrumbs';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/brands/brand.css';

type Brand = { id: number; name: string };

type BrandsPageProps = {
  brands: Brand[];
  pagination: Pagination;
  filters: { name: string };
};

type SaveResponse = {
  success: boolean;
  message?: string;
  errors?: Record<string, string[]>;
  duplicate?: boolean;
  exact?: boolean;
  existing?: { id: number; name: string };
};

export default function Index({ brands, filters, pagination }: BrandsPageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { showToast } = useToast();
  const { confirm } = useConfirmDialog();

  const [search, setSearch] = useState(filters.name ?? '');
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Brand | null>(null);
  const [name, setName] = useState('');
  const [nameError, setNameError] = useState('');
  const [saving, setSaving] = useState(false);

  function submitSearch(event: FormEvent) {
    event.preventDefault();
    router.get('/brands', { name: search }, { preserveScroll: true, preserveState: true, replace: true });
  }

  function clearSearch() {
    setSearch('');
    router.get('/brands', {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  function openCreate() {
    setEditing(null);
    setName('');
    setNameError('');
    setModalOpen(true);
  }

  function openEdit(brand: Brand) {
    setEditing(brand);
    setName(brand.name);
    setNameError('');
    setModalOpen(true);
  }

  async function saveBrand(event: FormEvent) {
    event.preventDefault();
    setNameError('');

    if (!name.trim()) {
      setNameError('El nombre de la marca es obligatorio.');
      return;
    }

    setSaving(true);
    const url = editing ? `/brands/${editing.id}` : '/brands';
    const method = editing ? 'PUT' : 'POST';

    try {
      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ name: name.trim() }),
      });

      const data = (await response.json().catch(() => ({}))) as SaveResponse;

      if (!response.ok || !data.success) {
        if (data.duplicate && data.existing) {
          setNameError(
            data.exact
              ? `Ya existe la marca "${data.existing.name}".`
              : `Ya existe una marca similar: "${data.existing.name}".`,
          );
        } else if (data.errors?.name?.[0]) {
          setNameError(data.errors.name[0]);
        } else {
          showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo guardar la marca.' });
        }
        return;
      }

      showToast({ variant: 'success', title: editing ? 'Marca actualizada' : 'Marca creada', message: data.message });
      setModalOpen(false);
      router.reload({ only: ['brands', 'pagination'] });
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al guardar la marca.' });
    } finally {
      setSaving(false);
    }
  }

  async function deleteBrand(brand: Brand) {
    const ok = await confirm({
      title: '¿Eliminar marca?',
      text: `Se eliminará la marca "${brand.name}". Esta acción no se puede deshacer.`,
      icon: 'warning',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!ok) {
      return;
    }

    try {
      const response = await fetch(`/brands/${brand.id}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
      });
      const data = (await response.json().catch(() => ({}))) as SaveResponse;

      if (!response.ok || !data.success) {
        showToast({ variant: 'error', title: 'No se pudo eliminar', message: data.message ?? 'Error al eliminar la marca.' });
        return;
      }

      showToast({ variant: 'success', title: 'Marca eliminada', message: data.message });
      router.reload({ only: ['brands', 'pagination'] });
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al eliminar la marca.' });
    }
  }

  return (
    <AdminLayout title="Gestión de Marcas">
      <Head title="Marcas - Ciclo Finca 4 Admin" />

      <div className="brands-container">
        <PageHeader
          title="Gestión de Marcas"
          kicker="Marcas"
          icon="fa-tags"
          breadcrumb={<Breadcrumbs items={[{ label: 'Inicio', href: '/dashboard' }, { label: 'Marcas' }]} />}
          actions={
            <div className="brands-actions">
              <span className="brands-count-badge">
                <i className="fas fa-tags" aria-hidden="true" /> {pagination.total} marca(s)
              </span>
              <button type="button" className="btn btn-primary" onClick={openCreate}>
                <i className="fas fa-plus" aria-hidden="true" /> Nueva marca
              </button>
            </div>
          }
        >
          <p>Administra las marcas asociadas a los productos del inventario.</p>
        </PageHeader>

        <FiltersSection onSubmit={submitSearch} onClear={clearSearch}>
          <div className="filter-group">
            <label htmlFor="buscarNombre">Nombre de la Marca</label>
            <input
              type="text"
              id="buscarNombre"
              name="name"
              placeholder="Buscar por nombre..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
        </FiltersSection>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="brands-table admin-table">
              <thead>
                <tr>
                  <th>Marca</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {brands.length === 0 ? (
                  <tr>
                    <td colSpan={2} className="brands-empty">
                      No hay marcas registradas.
                    </td>
                  </tr>
                ) : (
                  brands.map((brand) => (
                    <tr key={brand.id}>
                      <td className="brand-name">{brand.name}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button
                            type="button"
                            className="btn-icon btn-edit"
                            title="Editar"
                            onClick={() => openEdit(brand)}
                          >
                            <i className="fas fa-edit" aria-hidden="true" />
                          </button>
                          <button
                            type="button"
                            className="btn-icon btn-delete"
                            title="Eliminar"
                            onClick={() => deleteBrand(brand)}
                          >
                            <i className="fas fa-trash" aria-hidden="true" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <InertiaListPagination pagination={pagination} label="marcas" />
        </div>
      </div>

      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Editar marca' : 'Nueva marca'}
        footer={
          <>
            <button type="button" className="btn btn-secondary" onClick={() => setModalOpen(false)}>
              Cancelar
            </button>
            <button type="submit" form="form-marca" className="btn btn-primary" disabled={saving}>
              {saving ? 'Guardando…' : 'Guardar'}
            </button>
          </>
        }
      >
        <form id="form-marca" onSubmit={saveBrand}>
          <div className="form-group">
            <label htmlFor="marca-nombre">
              Nombre <span className="required">*</span>
            </label>
            <input
              type="text"
              id="marca-nombre"
              name="name"
              placeholder="Ej: Trek, Giant, Shimano..."
              maxLength={100}
              value={name}
              onChange={(event) => setName(event.target.value)}
              autoFocus
            />
            {nameError ? <span className="field-error">{nameError}</span> : null}
          </div>
        </form>
      </Modal>
    </AdminLayout>
  );
}
