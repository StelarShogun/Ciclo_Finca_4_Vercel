import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import '../../../../css/admin/reports/reports-hub.css';
import '../../../../css/admin/reports/audit-log.css';

type LogRow = {
  id: number;
  created_at: string;
  user: string;
  action_type: string;
  action_label: string;
  module: string;
  module_label: string;
  description: string;
};

type Option = { value: string; label: string };
type Filters = { user: string; action_type: string; module: string; from: string; to: string; dir: string };

type PageProps = {
  logs: LogRow[];
  pagination: Pagination;
  actionTypeOptions: Option[];
  moduleOptions: Option[];
  filters: Filters;
};

export default function AuditLog({ logs, pagination, actionTypeOptions, moduleOptions, filters }: PageProps) {
  const [form, setForm] = useState<Filters>(filters);

  function visit(params: Record<string, string>) {
    router.get('/reports/audit-log', params, { preserveScroll: true, preserveState: true, replace: true });
  }

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    visit({ ...form });
  }

  function clearFilters() {
    const empty: Filters = { user: '', action_type: '', module: '', from: '', to: '', dir: 'desc' };
    setForm(empty);
    visit({});
  }

  function toggleDir() {
    const dir = form.dir === 'asc' ? 'desc' : 'asc';
    setForm({ ...form, dir });
    visit({ ...form, dir });
  }

  return (
    <AdminLayout title="Bitácora de auditoría">
      <Head title="Bitácora de auditoría - Ciclo Finca 4 Admin" />

      <div className="reports-hub audit-log-page">
        <PageHeader title="Bitácora de auditoría" kicker="Reportes">
          <p>Consulta acciones administrativas por usuario, tipo de evento, módulo afectado y fecha.</p>
        </PageHeader>

        <form className="cf4-filters filter-form" onSubmit={submitFilters}>
          <div className="filter-group">
            <label htmlFor="audit-user">Usuario</label>
            <input id="audit-user" type="text" placeholder="Nombre o correo…" value={form.user} onChange={(e) => setForm({ ...form, user: e.target.value })} />
          </div>
          <div className="filter-group">
            <label htmlFor="audit-action">Tipo de acción</label>
            <select id="audit-action" value={form.action_type} onChange={(e) => setForm({ ...form, action_type: e.target.value })}>
              <option value="">Todas</option>
              {actionTypeOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="audit-module">Módulo</label>
            <select id="audit-module" value={form.module} onChange={(e) => setForm({ ...form, module: e.target.value })}>
              <option value="">Todos</option>
              {moduleOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="audit-from">Desde</label>
            <input id="audit-from" type="date" value={form.from} onChange={(e) => setForm({ ...form, from: e.target.value })} />
          </div>
          <div className="filter-group">
            <label htmlFor="audit-to">Hasta</label>
            <input id="audit-to" type="date" value={form.to} onChange={(e) => setForm({ ...form, to: e.target.value })} />
          </div>
          <div className="filter-actions">
            <button type="submit" className="btn btn-primary"><i className="fas fa-search" aria-hidden="true" /> Filtrar</button>
            <button type="button" className="btn btn-secondary" onClick={clearFilters}>Limpiar</button>
          </div>
        </form>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>
                    <button type="button" className="th-sort" onClick={toggleDir}>
                      Fecha y hora <i className={`fas fa-sort-${form.dir === 'asc' ? 'up' : 'down'}`} aria-hidden="true" />
                    </button>
                  </th>
                  <th>Usuario</th>
                  <th>Tipo de acción</th>
                  <th>Módulo</th>
                  <th>Descripción</th>
                </tr>
              </thead>
              <tbody>
                {logs.length === 0 ? (
                  <tr><td colSpan={5} className="empty-cell">No hay registros que coincidan con los filtros.</td></tr>
                ) : (
                  logs.map((log) => (
                    <tr key={log.id}>
                      <td data-label="Fecha y hora">{log.created_at}</td>
                      <td data-label="Usuario">{log.user}</td>
                      <td data-label="Tipo de acción">{log.action_label}</td>
                      <td data-label="Módulo">{log.module_label}</td>
                      <td data-label="Descripción">{log.description}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            <InertiaListPagination pagination={pagination} label="registros" />
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
