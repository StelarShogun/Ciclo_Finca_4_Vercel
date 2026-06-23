import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/users/clients.css';

type Client = {
  user_id: number;
  name: string;
  first_surname: string | null;
  second_surname: string | null;
  gmail: string;
  created_at: string | null;
  updated_at: string | null;
  active: boolean;
};

type Filters = { search: string; status: string; created_date: string; updated_date: string };

type PageProps = {
  clients: Client[];
  pagination: Pagination;
  filters: Filters;
  sort: string;
  dir: string;
};

const COLUMNS: Array<{ key: string; label: string }> = [
  { key: 'name', label: 'Nombre' },
  { key: 'first_surname', label: 'Primer apellido' },
  { key: 'second_surname', label: 'Segundo apellido' },
  { key: 'gmail', label: 'Correo' },
  { key: 'created_at', label: 'Registrado' },
  { key: 'updated_at', label: 'Actualizado' },
  { key: 'active', label: 'Estado' },
];

export default function Index({ clients, dir, filters, pagination, sort }: PageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { showToast } = useToast();
  const { confirm } = useConfirmDialog();

  const [form, setForm] = useState<Filters>(filters);

  function visit(params: Record<string, string>) {
    router.get('/clientes', params, { preserveScroll: true, preserveState: true, replace: true });
  }

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    visit({ ...form, sort, dir });
  }

  function clearFilters() {
    const empty = { search: '', status: '', created_date: '', updated_date: '' };
    setForm(empty);
    visit({ sort, dir });
  }

  function sortBy(column: string) {
    const nextDir = sort === column && dir === 'asc' ? 'desc' : 'asc';
    visit({ ...form, sort: column, dir: nextDir });
  }

  async function toggleBan(client: Client) {
    const banning = client.active;
    if (banning) {
      const ok = await confirm({
        title: '¿Bloquear usuario?',
        text: `Se bloqueará a ${client.name} ${client.first_surname ?? ''}.`,
        icon: 'warning',
        confirmText: 'Sí, bloquear',
        cancelText: 'Cancelar',
      });
      if (!ok) {
        return;
      }
    }

    const url = `/clientes/${client.user_id}/${banning ? 'ban' : 'unban'}`;
    try {
      const response = await fetch(url, {
        method: 'PATCH',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        showToast({ variant: 'error', title: 'Error', message: 'No se pudo actualizar el estado del usuario.' });
        return;
      }
      showToast({ variant: 'success', title: banning ? 'Usuario bloqueado' : 'Usuario activado' });
      router.reload({ only: ['clients', 'pagination'] });
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión.' });
    }
  }

  return (
    <AdminLayout title="Usuarios">
      <Head title="Usuarios - Ciclo Finca 4 Admin" />

      <div className="clients-page">
        <PageHeader title="Usuarios" kicker="Clientes">
          <p>Gestioná las cuentas de clientes registradas en la tienda.</p>
        </PageHeader>

        <form className="cf4-filters" onSubmit={submitFilters}>
          <div className="filter-group">
            <label htmlFor="client-search">Buscar</label>
            <input
              id="client-search"
              type="text"
              placeholder="Nombre, apellido o correo…"
              value={form.search}
              onChange={(e) => setForm({ ...form, search: e.target.value })}
            />
          </div>
          <div className="filter-group">
            <label htmlFor="client-status">Estado</label>
            <select id="client-status" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
              <option value="">Todos los estados</option>
              <option value="active">Activo</option>
              <option value="banned">Baneado</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="client-created">Registrado</label>
            <input
              id="client-created"
              type="date"
              value={form.created_date}
              onChange={(e) => setForm({ ...form, created_date: e.target.value })}
            />
          </div>
          <div className="filter-group">
            <label htmlFor="client-updated">Actualizado</label>
            <input
              id="client-updated"
              type="date"
              value={form.updated_date}
              onChange={(e) => setForm({ ...form, updated_date: e.target.value })}
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

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table clients-table">
              <thead>
                <tr>
                  {COLUMNS.map((column) => (
                    <th key={column.key} scope="col">
                      <button type="button" className={`th-sort ${sort === column.key ? 'is-active' : ''}`} onClick={() => sortBy(column.key)}>
                        {column.label}
                        {sort === column.key ? (
                          <i className={`fas fa-sort-${dir === 'asc' ? 'up' : 'down'}`} aria-hidden="true" />
                        ) : null}
                      </button>
                    </th>
                  ))}
                  <th scope="col" className="admin-table__col--actions">
                    Acción
                  </th>
                </tr>
              </thead>
              <tbody>
                {clients.length === 0 ? (
                  <tr>
                    <td colSpan={COLUMNS.length + 1} className="empty-cell">
                      No hay usuarios que coincidan con los filtros.
                    </td>
                  </tr>
                ) : (
                  clients.map((client) => (
                    <tr key={client.user_id} id={`client-row-${client.user_id}`}>
                      <td>{client.name}</td>
                      <td>{client.first_surname}</td>
                      <td>{client.second_surname ?? '—'}</td>
                      <td>{client.gmail}</td>
                      <td>{client.created_at ?? '—'}</td>
                      <td>{client.updated_at ?? '—'}</td>
                      <td>
                        <span className={`status-badge ${client.active ? 'status-active' : 'status-banned'}`}>
                          {client.active ? 'Activo' : 'Baneado'}
                        </span>
                      </td>
                      <td className="admin-table__col--actions">
                        <button
                          type="button"
                          className={`btn ${client.active ? 'btn-danger-soft' : 'btn-primary'}`}
                          onClick={() => toggleBan(client)}
                        >
                          {client.active ? 'Bloquear' : 'Activar'}
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <InertiaListPagination pagination={pagination} label="usuarios" />
        </div>
      </div>
    </AdminLayout>
  );
}
