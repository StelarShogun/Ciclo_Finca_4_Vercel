import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Modal } from '@/shared/components/ui/Modal';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/suppliers/suppliers.css';

type Supplier = {
  supplier_id: number;
  name: string;
  primary_contact: string;
  phone: string;
  email: string;
  address: string;
  delivery_time: number | string;
  rating: number | null;
  created_at: string | null;
};

type SuppliersPageProps = {
  suppliers: Supplier[];
  averageRating: number;
  pagination: Pagination;
  filters: { name: string; contact: string };
};

type FormValues = {
  name: string;
  primary_contact: string;
  phone: string;
  email: string;
  address: string;
  delivery_time: string;
  rating: string;
};

type SaveResponse = {
  success: boolean;
  message?: string;
  errors?: Record<string, string[]>;
};

const EMPTY_FORM: FormValues = {
  name: '',
  primary_contact: '',
  phone: '',
  email: '',
  address: '',
  delivery_time: '',
  rating: '',
};

function toForm(supplier: Supplier): FormValues {
  return {
    name: supplier.name ?? '',
    primary_contact: supplier.primary_contact ?? '',
    phone: supplier.phone ?? '',
    email: supplier.email ?? '',
    address: supplier.address ?? '',
    delivery_time: String(supplier.delivery_time ?? ''),
    rating: supplier.rating != null ? String(supplier.rating) : '',
  };
}

export default function Index({ averageRating, filters, pagination, suppliers }: SuppliersPageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { showToast } = useToast();
  const { confirm } = useConfirmDialog();

  const [name, setName] = useState(filters.name ?? '');
  const [contact, setContact] = useState(filters.contact ?? '');

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Supplier | null>(null);
  const [form, setForm] = useState<FormValues>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);

  const [viewing, setViewing] = useState<Supplier | null>(null);

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    router.get('/suppliers', { name, contact }, { preserveScroll: true, preserveState: true, replace: true });
  }

  function clearFilters() {
    setName('');
    setContact('');
    router.get('/suppliers', {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  function openCreate() {
    setEditing(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  }

  function openEdit(supplier: Supplier) {
    setEditing(supplier);
    setForm(toForm(supplier));
    setErrors({});
    setFormOpen(true);
  }

  function setField(field: keyof FormValues, value: string) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function save(event: FormEvent) {
    event.preventDefault();
    setErrors({});
    setSaving(true);

    const url = editing ? `/suppliers/${editing.supplier_id}` : '/suppliers';
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
        body: JSON.stringify(form),
      });
      const data = (await response.json().catch(() => ({}))) as SaveResponse;

      if (!response.ok || !data.success) {
        if (data.errors) {
          const flat: Record<string, string> = {};
          Object.entries(data.errors).forEach(([key, messages]) => {
            flat[key] = messages[0];
          });
          setErrors(flat);
        } else {
          showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo guardar el proveedor.' });
        }
        return;
      }

      showToast({
        variant: 'success',
        title: editing ? 'Proveedor actualizado' : 'Proveedor registrado',
        message: data.message,
      });
      setFormOpen(false);
      router.reload({ only: ['suppliers', 'averageRating', 'pagination'] });
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al guardar el proveedor.' });
    } finally {
      setSaving(false);
    }
  }

  async function remove(supplier: Supplier) {
    const ok = await confirm({
      title: '¿Eliminar proveedor?',
      text: `Se eliminará "${supplier.name}". Esta acción no se puede deshacer.`,
      icon: 'warning',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!ok) {
      return;
    }

    try {
      const response = await fetch(`/suppliers/${supplier.supplier_id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      const data = (await response.json().catch(() => ({}))) as SaveResponse;

      if (!response.ok || !data.success) {
        showToast({ variant: 'error', title: 'No se pudo eliminar', message: data.message ?? 'Error al eliminar.' });
        return;
      }

      showToast({ variant: 'success', title: 'Proveedor eliminado', message: data.message });
      router.reload({ only: ['suppliers', 'averageRating', 'pagination'] });
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al eliminar.' });
    }
  }

  return (
    <AdminLayout title="Gestión de Proveedores">
      <Head title="Proveedores - Ciclo Finca 4 Admin" />

      <div className="suppliers-container">
        <PageHeader
          title="Gestión de Proveedores"
          kicker="Proveedores"
          icon="fa-truck"
          actions={
            <button type="button" className="btn btn-primary" onClick={openCreate}>
              <i className="fas fa-plus" aria-hidden="true" /> Nuevo proveedor
            </button>
          }
        >
          <p>Administra los proveedores del catálogo y su información de contacto.</p>
        </PageHeader>

        <section className="kpis-section" aria-label="Indicadores de proveedores">
          <div className="kpi-card">
            <div className="kpi-icon"><i className="fas fa-truck" aria-hidden="true" /></div>
            <div className="kpi-content">
              <h3>Total proveedores</h3>
              <p className="kpi-value">{pagination.total}</p>
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-icon success"><i className="fas fa-star" aria-hidden="true" /></div>
            <div className="kpi-content">
              <h3>Evaluación promedio</h3>
              <p className="kpi-value">{averageRating.toFixed(2)}</p>
            </div>
          </div>
        </section>

        <FiltersSection onSubmit={submitFilters} onClear={clearFilters}>
          <div className="filter-group">
            <label htmlFor="buscarNombre">Nombre</label>
            <input
              type="text"
              id="buscarNombre"
              placeholder="Buscar por nombre..."
              value={name}
              onChange={(event) => setName(event.target.value)}
            />
          </div>
          <div className="filter-group">
            <label htmlFor="buscarContacto">Contacto</label>
            <input
              type="text"
              id="buscarContacto"
              placeholder="Buscar por contacto..."
              value={contact}
              onChange={(event) => setContact(event.target.value)}
            />
          </div>
        </FiltersSection>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="suppliers-table admin-table">
              <thead>
                <tr>
                  <th>Proveedor</th>
                  <th>Contacto</th>
                  <th>Teléfono</th>
                  <th>Correo Electrónico</th>
                  <th>Dirección</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {suppliers.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="suppliers-empty">
                      No hay proveedores registrados.
                    </td>
                  </tr>
                ) : (
                  suppliers.map((supplier) => (
                    <tr key={supplier.supplier_id}>
                      <td>{supplier.name}</td>
                      <td>{supplier.primary_contact}</td>
                      <td>{supplier.phone}</td>
                      <td>{supplier.email}</td>
                      <td>{supplier.address}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button type="button" className="btn-icon" data-tooltip="Ver" aria-label="Ver" onClick={() => setViewing(supplier)}>
                            <i className="fas fa-eye" aria-hidden="true" />
                          </button>
                          <button type="button" className="btn-icon btn-edit" data-tooltip="Editar" aria-label="Editar" onClick={() => openEdit(supplier)}>
                            <i className="fas fa-edit" aria-hidden="true" />
                          </button>
                          <button type="button" className="btn-icon btn-delete" data-tooltip="Eliminar" aria-label="Eliminar" onClick={() => remove(supplier)}>
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

          <InertiaListPagination pagination={pagination} label="proveedores" />
        </div>
      </div>

      {/* Crear / Editar */}
      <Modal
        isOpen={formOpen}
        onClose={() => setFormOpen(false)}
        title={editing ? 'Editar proveedor' : 'Nuevo proveedor'}
        className="cf4-modal cf4-modal--wide"
        footer={
          <>
            <button type="button" className="btn btn-secondary" onClick={() => setFormOpen(false)}>
              Cancelar
            </button>
            <button type="submit" form="form-supplier" className="btn btn-primary" disabled={saving}>
              {saving ? 'Guardando…' : 'Guardar'}
            </button>
          </>
        }
      >
        <form id="form-supplier" onSubmit={save} className="supplier-form">
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="sup-name">Nombre del Proveedor *</label>
              <input id="sup-name" type="text" value={form.name} onChange={(e) => setField('name', e.target.value)} required />
              {errors.name ? <div className="field-error">{errors.name}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="sup-contact">Contacto Principal *</label>
              <input id="sup-contact" type="text" value={form.primary_contact} onChange={(e) => setField('primary_contact', e.target.value)} required />
              {errors.primary_contact ? <div className="field-error">{errors.primary_contact}</div> : null}
            </div>
          </div>
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="sup-phone">Teléfono *</label>
              <input id="sup-phone" type="tel" value={form.phone} onChange={(e) => setField('phone', e.target.value)} required />
              {errors.phone ? <div className="field-error">{errors.phone}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="sup-email">Correo Electrónico *</label>
              <input id="sup-email" type="email" value={form.email} onChange={(e) => setField('email', e.target.value)} required />
              {errors.email ? <div className="field-error">{errors.email}</div> : null}
            </div>
          </div>
          <div className="form-group">
            <label htmlFor="sup-address">Dirección *</label>
            <textarea id="sup-address" rows={3} value={form.address} onChange={(e) => setField('address', e.target.value)} required />
            {errors.address ? <div className="field-error">{errors.address}</div> : null}
          </div>
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="sup-delivery">Tiempo de entrega (días) *</label>
              <input id="sup-delivery" type="number" min={1} max={365} value={form.delivery_time} onChange={(e) => setField('delivery_time', e.target.value)} required />
              {errors.delivery_time ? <div className="field-error">{errors.delivery_time}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="sup-rating">Evaluación (0-5)</label>
              <input id="sup-rating" type="number" min={0} max={5} step={0.1} value={form.rating} onChange={(e) => setField('rating', e.target.value)} />
              {errors.rating ? <div className="field-error">{errors.rating}</div> : null}
            </div>
          </div>
        </form>
      </Modal>

      {/* Detalle */}
      <Modal isOpen={viewing !== null} onClose={() => setViewing(null)} title="Detalle del proveedor">
        {viewing ? (
          <dl className="supplier-detail">
            <div><dt>Nombre</dt><dd>{viewing.name}</dd></div>
            <div><dt>Contacto</dt><dd>{viewing.primary_contact}</dd></div>
            <div><dt>Teléfono</dt><dd>{viewing.phone}</dd></div>
            <div><dt>Correo</dt><dd>{viewing.email}</dd></div>
            <div><dt>Dirección</dt><dd>{viewing.address}</dd></div>
            <div><dt>Tiempo de entrega</dt><dd>{viewing.delivery_time} día(s)</dd></div>
            <div><dt>Evaluación</dt><dd>{viewing.rating != null ? viewing.rating.toFixed(2) : '—'}</dd></div>
          </dl>
        ) : null}
      </Modal>
    </AdminLayout>
  );
}
