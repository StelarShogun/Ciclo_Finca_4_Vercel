import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Modal } from '@/shared/components/ui/Modal';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';

type Attribute = {
  id: number;
  label: string;
  slug: string;
  values_count: number;
  trashed: boolean;
};

type PageProps = {
  category: { category_id: number; name: string; parent_name: string | null };
  attributes: Attribute[];
};

export default function Show({ attributes, category }: PageProps) {
  useFlashToasts();
  const { confirm } = useConfirmDialog();

  const createForm = useForm({ label: '' });
  const editForm = useForm({ label: '' });
  const [editing, setEditing] = useState<Attribute | null>(null);

  function submitCreate(event: FormEvent) {
    event.preventDefault();
    createForm.post(`/classifications/catalog/${category.category_id}/dimensions`, {
      preserveScroll: true,
      onSuccess: () => createForm.reset('label'),
    });
  }

  function openEdit(attribute: Attribute) {
    setEditing(attribute);
    editForm.setData('label', attribute.label);
    editForm.clearErrors();
  }

  function submitEdit(event: FormEvent) {
    event.preventDefault();
    if (!editing) {
      return;
    }
    editForm.put(`/classifications/dimensions/${editing.id}`, {
      preserveScroll: true,
      onSuccess: () => setEditing(null),
    });
  }

  async function deactivate(attribute: Attribute) {
    const ok = await confirm({
      title: '¿Desactivar atributo?',
      text: 'Se desactivará este atributo. Los productos que ya tenían un valor siguen igual.',
      icon: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar',
    });
    if (ok) {
      router.delete(`/classifications/dimensions/${attribute.id}`, { preserveScroll: true });
    }
  }

  function restore(attribute: Attribute) {
    router.post(`/classifications/dimensions/${attribute.id}/restore`, {}, { preserveScroll: true });
  }

  return (
    <AdminLayout title={`Atributos: ${category.name}`}>
      <Head title={`Atributos: ${category.name}`} />

      <div className="classifications-catalog-show">
        <nav className="reports-breadcrumb" aria-label="Migas de pan">
          <Link href="/classifications/catalog">Opciones por tipo</Link>
          <span> / {category.parent_name ? `${category.parent_name} → ` : ''}{category.name}</span>
        </nav>

        <PageHeader title={`Atributos de ${category.name}`} kicker="Clasificación" />

        <div className="form-card">
          <h2 className="card-title">Nuevo atributo</h2>
          <form onSubmit={submitCreate} className="form-inline">
            <div className="form-group">
              <label htmlFor="label">Nombre del atributo *</label>
              <input
                id="label"
                type="text"
                value={createForm.data.label}
                onChange={(event) => createForm.setData('label', event.target.value)}
                placeholder="Ej: Color, Talla…"
                required
              />
              {createForm.errors.label ? <div className="field-error">{createForm.errors.label}</div> : null}
            </div>
            <button type="submit" className="btn btn-primary" disabled={createForm.processing}>
              {createForm.processing ? 'Guardando…' : 'Añadir atributo'}
            </button>
          </form>
        </div>

        <div className="form-card">
          <h2 className="card-title">Atributos cargados</h2>
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Atributo</th>
                  <th>Valores</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {attributes.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="empty-cell">
                      Todavía no hay atributos para este tipo.
                    </td>
                  </tr>
                ) : (
                  attributes.map((attribute) => (
                    <tr key={attribute.id} style={attribute.trashed ? { opacity: 0.5 } : undefined}>
                      <td>{attribute.label}</td>
                      <td>{attribute.values_count}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <Link href={`/classifications/dimensions/${attribute.id}/values`} className="btn btn-secondary">
                            Valores
                          </Link>
                          {attribute.trashed ? (
                            <button type="button" className="btn btn-primary" onClick={() => restore(attribute)}>
                              Activar
                            </button>
                          ) : (
                            <>
                              <button type="button" className="btn btn-secondary" onClick={() => openEdit(attribute)}>
                                Editar
                              </button>
                              <button type="button" className="btn btn-secondary" onClick={() => deactivate(attribute)}>
                                Desactivar
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Modal
        isOpen={editing !== null}
        onClose={() => setEditing(null)}
        title="Editar atributo"
        footer={
          <>
            <button type="button" className="btn btn-secondary" onClick={() => setEditing(null)}>
              Cancelar
            </button>
            <button type="submit" form="form-edit-dimension" className="btn btn-primary" disabled={editForm.processing}>
              {editForm.processing ? 'Guardando…' : 'Guardar'}
            </button>
          </>
        }
      >
        <form id="form-edit-dimension" onSubmit={submitEdit}>
          <div className="form-group">
            <label htmlFor="edit-label">Nombre del atributo *</label>
            <input
              id="edit-label"
              type="text"
              value={editForm.data.label}
              onChange={(event) => editForm.setData('label', event.target.value)}
              required
            />
            {editForm.errors.label ? <div className="field-error">{editForm.errors.label}</div> : null}
          </div>
        </form>
      </Modal>
    </AdminLayout>
  );
}
