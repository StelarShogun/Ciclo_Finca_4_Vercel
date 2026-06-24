import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import Chart from 'chart.js/auto';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { Badge } from '@/shared/components/ui/Badge';
import { PageHeader } from '@/shared/components/ui/PageHeader';

type RecentSale = {
  id: number;
  invoice: string;
  client: string;
  total: number;
  dateShort: string;
  dateFull: string;
  statusClass: string;
  statusShort: string;
  statusTitle: string;
};
type LowStockRow = { id: number; name: string; sku: string; category: string; stock: number };
type SalesByDay = { date: string; total: number };
type CategoryRow = { label: string; total: number };
type TopProduct = { name: string; units: number; revenue: number };

type DashboardProps = {
  totalProducts: number;
  totalSuppliers: number;
  totalCategories: number;
  todaySales: number;
  lowStockProducts: number;
  salesTrend: number;
  monthlySales: number;
  monthlyTrend: number;
  recentSales?: RecentSale[];
  lowStockList?: LowStockRow[];
  salesByDay?: SalesByDay[];
  productsByCategory?: CategoryRow[];
  topProducts?: TopProduct[];
  error?: string | null;
};

const currency = new Intl.NumberFormat('es-CR', {
  currency: 'CRC',
  maximumFractionDigits: 0,
  style: 'currency',
});

const CATEGORY_COLORS = [
  '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336',
  '#00BCD4', '#795548', '#607D8B', '#E91E63', '#009688',
];

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
  recentSales = [],
  lowStockList = [],
  salesByDay = [],
  productsByCategory = [],
  topProducts = [],
}: DashboardProps) {
  const salesCanvas = useRef<HTMLCanvasElement>(null);
  const categoryCanvas = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    if (!salesCanvas.current || salesByDay.length === 0) {
      return;
    }
    const chart = new Chart(salesCanvas.current, {
      type: 'bar',
      data: {
        labels: salesByDay.map((d) => d.date),
        datasets: [
          {
            label: 'Ventas',
            data: salesByDay.map((d) => d.total),
            backgroundColor: 'rgba(76, 175, 80, 0.65)',
            borderColor: '#4CAF50',
            borderWidth: 1,
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (ctx) => currency.format(Number(ctx.parsed.y)) } },
        },
        scales: {
          y: { beginAtZero: true, ticks: { callback: (v) => currency.format(Number(v)) } },
        },
      },
    });
    return () => chart.destroy();
  }, [salesByDay]);

  useEffect(() => {
    if (!categoryCanvas.current || productsByCategory.length === 0) {
      return;
    }
    const chart = new Chart(categoryCanvas.current, {
      type: 'doughnut',
      data: {
        labels: productsByCategory.map((c) => c.label),
        datasets: [
          {
            data: productsByCategory.map((c) => c.total),
            backgroundColor: productsByCategory.map((_, i) => CATEGORY_COLORS[i % CATEGORY_COLORS.length]),
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: { legend: { display: false } },
      },
    });
    return () => chart.destroy();
  }, [productsByCategory]);

  const kpis = [
    { label: 'Total Productos', value: totalProducts.toLocaleString('es-CR'), icon: 'fa-box' },
    { label: 'Ventas Hoy', value: currency.format(todaySales), icon: 'fa-cash-register', change: `${Math.abs(salesTrend)}%`, down: salesTrend < 0 },
    { label: 'Proveedores', value: totalSuppliers.toLocaleString('es-CR'), icon: 'fa-truck' },
    { label: 'Categorías', value: totalCategories.toLocaleString('es-CR'), icon: 'fa-layer-group' },
    { label: 'Stock bajo', value: lowStockProducts.toLocaleString('es-CR'), icon: 'fa-triangle-exclamation' },
    { label: 'Ventas del mes', value: currency.format(monthlySales), icon: 'fa-calendar', change: `${Math.abs(monthlyTrend)}%`, down: monthlyTrend < 0 },
  ];

  const categoryTotal = productsByCategory.reduce((sum, c) => sum + c.total, 0);

  return (
    <AdminLayout title="Panel de control">
      <Head title="Dashboard - Ciclo Finca 4 Admin" />

      <PageHeader title="Panel de control" kicker="Administración" icon="fa-chart-line">
        <p>Resumen general de ventas, inventario y actividad reciente de la tienda.</p>
      </PageHeader>

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
                {kpi.change ? <Badge tone={kpi.down ? 'danger' : 'success'}>{kpi.down ? '↓' : '↑'} {kpi.change}</Badge> : null}
              </div>
            </div>
          ))}
        </section>

        <section className="charts-section" aria-label="Gráficos">
          <div className="chart-container chart-container--sales">
            <div className="chart-header">
              <h3>Ventas de los últimos 7 días</h3>
            </div>
            <div className="chart-wrapper chart-wrapper--sales">
              {salesByDay.length === 0 ? (
                <p className="text-muted" style={{ textAlign: 'center', padding: '2rem' }}>Sin ventas registradas en el período.</p>
              ) : (
                <canvas ref={salesCanvas} />
              )}
            </div>
          </div>

          <div className="chart-container chart-container--category">
            <div className="chart-header">
              <h3>Productos por categoría</h3>
            </div>
            <div className="category-chart-body">
              <div className="chart-wrapper chart-wrapper--category-donut">
                {productsByCategory.length === 0 ? (
                  <p className="text-muted" style={{ textAlign: 'center', padding: '2rem' }}>Sin datos de categorías.</p>
                ) : (
                  <canvas ref={categoryCanvas} />
                )}
              </div>
              {productsByCategory.length > 0 ? (
                <ul className="category-chart-legend">
                  {productsByCategory.map((cat, i) => (
                    <li className="category-chart-legend-item" key={cat.label}>
                      <span className="category-chart-legend-swatch" style={{ background: CATEGORY_COLORS[i % CATEGORY_COLORS.length] }} />
                      <span className="category-chart-legend-name">{cat.label}</span>
                      <span className="category-chart-legend-value">
                        {cat.total}{categoryTotal > 0 ? ` (${Math.round((cat.total / categoryTotal) * 100)}%)` : ''}
                      </span>
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </div>
        </section>

        <section className="tables-section" aria-label="Detalle">
          <div className="table-container">
            <div className="table-header">
              <h3>Ventas recientes</h3>
              <Link href="/sales" className="chart-btn">Ver todas</Link>
            </div>
            <div className="table-content table-content--scroll">
              <table className="admin-table dashboard-table">
                <thead>
                  <tr>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {recentSales.length === 0 ? (
                    <tr><td colSpan={5} className="text-muted" style={{ textAlign: 'center' }}>No hay ventas recientes.</td></tr>
                  ) : (
                    recentSales.map((sale) => (
                      <tr key={sale.id}>
                        <td className="dashboard-table__col-invoice"><span className="dashboard-table__cell-truncate" title={sale.invoice}>{sale.invoice}</span></td>
                        <td className="dashboard-table__col-client"><span className="dashboard-table__cell-truncate" title={sale.client}>{sale.client}</span></td>
                        <td className="dashboard-table__col-total">{currency.format(sale.total)}</td>
                        <td className="dashboard-table__col-date" title={sale.dateFull}>{sale.dateShort}</td>
                        <td className="dashboard-table__col-status">
                          <span className={`status-badge ${sale.statusClass}`} title={sale.statusTitle}>{sale.statusShort}</span>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          <div className="table-container">
            <div className="table-header">
              <h3>Productos con stock bajo</h3>
              <Link href="/inventory" className="chart-btn">Ir a inventario</Link>
            </div>
            <div className="table-content table-content--scroll">
              <table className="admin-table dashboard-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                  </tr>
                </thead>
                <tbody>
                  {lowStockList.length === 0 ? (
                    <tr><td colSpan={3} className="text-muted" style={{ textAlign: 'center' }}>Sin productos con stock bajo.</td></tr>
                  ) : (
                    lowStockList.map((row) => (
                      <tr key={row.id}>
                        <td>
                          <div className="dashboard-table__cell-truncate" title={row.name}>{row.name}</div>
                          <div className="text-muted" style={{ fontSize: '0.78rem' }}>{row.sku}</div>
                        </td>
                        <td>{row.category}</td>
                        <td><span className="status-badge status-badge--danger">{row.stock}</span></td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </section>

        {topProducts.length > 0 ? (
          <section className="tables-section" aria-label="Top productos">
            <div className="table-container">
              <div className="table-header">
                <h3>Productos más vendidos (30 días)</h3>
              </div>
              <div className="table-content table-content--scroll">
                <table className="admin-table dashboard-table">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Unidades</th>
                      <th>Ingresos</th>
                    </tr>
                  </thead>
                  <tbody>
                    {topProducts.map((p) => (
                      <tr key={p.name}>
                        <td><span className="dashboard-table__cell-truncate" title={p.name}>{p.name}</span></td>
                        <td>{p.units}</td>
                        <td>{currency.format(p.revenue)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        ) : null}
      </div>
    </AdminLayout>
  );
}
