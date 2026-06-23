import { Head, Link } from '@inertiajs/react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Badge } from '@/shared/components/ui/Badge';
import { PageHeader } from '@/shared/components/ui/PageHeader';

type DashboardProps = {
  totalProducts: number;
  totalSuppliers: number;
  totalCategories: number;
  todaySales: number;
  lowStockProducts: number;
  salesTrend: number;
  monthlySales: number;
  monthlyTrend: number;
  error?: string | null;
};

const currency = new Intl.NumberFormat('es-CR', {
  currency: 'CRC',
  maximumFractionDigits: 0,
  style: 'currency',
});

export default function Index({
  error = null,
  lowStockProducts,
  monthlySales,
  monthlyTrend,
  salesTrend,
  todaySales,
  totalCategories,
  totalProducts,
  totalSuppliers,
}: DashboardProps) {
  const kpis = [
    { label: 'Total Productos', value: totalProducts.toLocaleString('es-CR'), icon: 'fa-box' },
    { label: 'Ventas Hoy', value: currency.format(todaySales), icon: 'fa-cash-register', change: `${Math.abs(salesTrend)}%` },
    { label: 'Proveedores', value: totalSuppliers.toLocaleString('es-CR'), icon: 'fa-truck' },
    { label: 'Categorías', value: totalCategories.toLocaleString('es-CR'), icon: 'fa-layer-group' },
    { label: 'Stock bajo', value: lowStockProducts.toLocaleString('es-CR'), icon: 'fa-triangle-exclamation' },
    { label: 'Ventas del mes', value: currency.format(monthlySales), icon: 'fa-calendar', change: `${Math.abs(monthlyTrend)}%` },
  ];

  return (
    <AdminLayout title="Panel de control">
      <Head title="Dashboard - Ciclo Finca 4 Admin" />

      <PageHeader title="Panel de control" kicker="Administración" />

      <div className="dashboard-page">
        {error ? (
          <div className="alert alert-warning alert-inline-error">
            <i className="fas fa-exclamation-triangle" aria-hidden="true" /> {error}
          </div>
        ) : null}

        <section className="kpis-section" aria-label="Indicadores principales">
          {kpis.map((kpi) => (
            <div className="kpi-card" key={kpi.label}>
              <div className="kpi-icon">
                <i className={`fas ${kpi.icon}`} aria-hidden="true" />
              </div>
              <div className="kpi-content">
                <h3>{kpi.label}</h3>
                <div className="kpi-value">{kpi.value}</div>
                {kpi.change ? <Badge tone={kpi.change.startsWith('-') ? 'danger' : 'success'}>{kpi.change}</Badge> : null}
              </div>
            </div>
          ))}
        </section>

        <section className="dashboard-section">
          <div className="section-header">
            <h2>Migración Inertia piloto</h2>
            <p>Esta vista confirma el layout admin React sin reemplazar todavía los módulos operativos.</p>
          </div>
          <div className="quick-actions-grid">
            <Link href="/inventory" className="quick-action-card">
              <i className="fas fa-boxes-stacked" aria-hidden="true" />
              <span>Ir a inventario legacy</span>
            </Link>
            <Link href="/reports" className="quick-action-card">
              <i className="fas fa-chart-pie" aria-hidden="true" />
              <span>Ir a reportes legacy</span>
            </Link>
          </div>
        </section>
      </div>
    </AdminLayout>
  );
}
