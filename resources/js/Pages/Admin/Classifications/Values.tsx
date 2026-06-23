import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Modal } from '@/shared/components/ui/Modal';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';

type ValueRow = { id: number; value: string; trashed: boolean };

type PageProps = {
  dimension: {
    id: number;
    label: string;
    category_id: number;
    category_name: string | null;
    parent_name: string | null;
  };
  values: ValueRow[];
};

export default function Values({ dimension, values }: PageProps) {
  useFlashToasts();
  const { confirm } = useConfirmDialog();

  const createForm = useForm({ value: '' });
  const editForm = useForm({ value: '' });
  const [editing, setEditing] = useState<ValueRow | null>(null);

  function submitCreate(event: FormEvent) {
    event.preventDefault();
    createForm.post(`/classifications/dimensions/${dimension.id}/values`, {
      preserveScroll: true,
      onSuccess: () => createForm.reset('value'),
    });
  }

  function openEdit(row: ValueRow) {
    setEditing(row);
    editForm.setData('value', row.value);
    editForm.clearErrors();
  }

  function submitEdit(event: FormEvent) {
    event.preventDefault();
    if (!editing) {
      return;
    }
    editForm.put(`/classifications/values/${editing.id}`, {
      preserveScroll: true,
      onSuccess: () => setEditing(null),
    });
  }

  async function deactivate(row: ValueRow) {
    const ok = await confirm({
      title: '¿Desactivar valor?',
      text: 'Se desactivará este valor.',
      icon: 'warning',
      confirmText: 'Sí, desactivar',
      cancelText: 'Cancelar',
    });
    if (ok) {
      router.delete(`/classifications/values/${row.id}`, { preserveScroll: true });
    }
  }

  function restore(row: ValueRow) {
    router.post(`/classifications/values/${row.id}/restore`, {}, { preserveScroll: true });
  }

  return (
    <AdminLayout title={`Valores: ${dimension.label}`}>
      <Head title={`Valores: ${dimension.label}`} />

      <div className="classifications-values">
        <nav className="reports-breadcrumb" aria-label="Migas de pan">
          <Link href="/classifications/catalog">Opciones por tipo</Link>
          <span> / </span>
          <Link href={`/classifications/catalog/${dimension.category_id}`}>{dimension.category_name ?? 'Tipo'}</Link>
          <span> / {dimension.label}</span>
        </nav>

        <PageHeader title={`Valores de ${dimension.label}`} kicker="Clasificación" />

        <div className="form-card">
          <h2 className="card-title">Nuevo valor</h2>
          <form onSubmit={submitCreate} className="form-inline">
            <div className="form-group">
              <label htmlFor="value">Valor *</label>
              <input
                id="value"
                type="text"
                value={createForm.data.value}
                onChange={(event) => createForm.setData('value', event.target.value)}
                placeholder="Ej: Rojo, M, 29…"
                required
              />
              {createForm.errors.value ? <div className="field-error">{createForm.errors.value}</div> : null}
            </div>
            <button type="submit" className="btn btn-primary" disabled={createForm.processing}>
              {createForm.processing ? 'Guardando…' : 'Añadir valor'}
            </button>
          </form>
        </div>

        <div className="form-card">
          <h2 className="card-title">Valores cargados</h2>
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Valor</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {values.length === 0 ? (
                  <tr>
                    <td colSpan={2} className="empty-cell">
                      Todavía no hay valores para este atributo.
                    </td>
                  </tr>
                ) : (
                  values.map((row) => (
                    <tr key={row.id} style={row.trashed ? { opacity: 0.5 } : undefined}>
                      <td>{row.value}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          {row.trashed ? (
                            <button type="button" className="btn btn-primary" onClick={() => restore(row)}>
                              Activar
                            </button>
                          ) : (
                            <>
                              <button type="button" className="btn btn-secondary" onClick={() => openEdit(row)}>
                                Editar
                              </button>
                              <button type="button" className="btn btn-secondary" onClick={() => deactivate(row)}>
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
        title="Editar valor"
        footer={
          <>
            <button type="button" className="btn btn-secondary" onClick={() => setEditing(null)}>
              Cancelar
            </button>
            <button type="submit" form="form-edit-value" className="btn btn-primary" disabled={editForm.processing}>
              {editForm.processing ? 'Guardando…' : 'Guardar'}
            </button>
          </>
        }
      >
        <form id="form-edit-value" onSubmit={submitEdit}>
          <div className="form-group">
            <label htmlFor="edit-value">Valor *</label>
            <input
              id="edit-value"
              type="text"
              value={editForm.data.value}
              onChange={(event) => editForm.setData('value', event.target.value)}
              required
            />
            {editForm.errors.value ? <div className="field-error">{editForm.errors.value}</div> : null}
          </div>
        </form>
      </Modal>
    </AdminLayout>
  );
}
