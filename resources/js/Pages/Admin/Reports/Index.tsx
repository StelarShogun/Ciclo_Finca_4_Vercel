import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { Breadcrumbs } from '@/shared/components/ui/Breadcrumbs';

import '../../../../css/admin/reports/reports-hub.css';

type Card = { href: string; icon: string; title: string; description: string };

const CARDS: Card[] = [
  { href: '/reports/exportaciones', icon: 'fa-file-export', title: 'Exportar datos', description: 'Descargas centralizadas: PDF, Excel y XML de inventario y ventas, más proveedores, marcas, pedidos a proveedores, usuarios y encargos.' },
  { href: '/reports/desempeno-ventas', icon: 'fa-chart-line', title: 'Desempeño de ventas', description: 'Filtra por día, semana, mes, año o un rango personalizado. Consulta totales e ingresos y la variación frente al periodo anterior equivalente.' },
  { href: '/reports/client-purchases', icon: 'fa-user-clock', title: 'Compras por cliente', description: 'Historial de ventas completadas por usuario: totales, cantidad de órdenes y ticket promedio; detalle por periodo.' },
  { href: '/reports/productos-vendidos?period=30d&sort=revenue&dir=desc', icon: 'fa-chart-bar', title: 'Productos más vendidos', description: 'Consulta cuánto se vendió de cada producto y cuánto ingresó. Busca por nombre o código y descubre cuáles son los favoritos.' },
  { href: '/reports/catalogo-busquedas?period=30d', icon: 'fa-search', title: 'Productos más buscados', description: 'Ranking según apariciones en el catálogo cuando los clientes buscan por texto (últimos 7, 30 o 90 días).' },
  { href: '/inventory/movements', icon: 'fa-clock-rotate-left', title: 'Movimientos de inventario', description: 'Consulta el historial completo de entradas, salidas y devoluciones por producto. Filtra por tipo, origen y rango de fechas.' },
  { href: '/reports/audit-log', icon: 'fa-user-shield', title: 'Bitácora de auditoría', description: 'Consulta acciones administrativas por usuario, tipo de evento, módulo afectado y fecha para detectar irregularidades.' },
  { href: '/sales/reports/by-category', icon: 'fa-chart-pie', title: 'Ventas por categoría', description: 'Analiza el rendimiento de ventas agrupado por categoría de producto. Identifica cuáles categorías generan más ingresos.' },
];

export default function Index() {
  return (
    <AdminLayout title="Reportes">
      <Head title="Reportes - Ciclo Finca 4 Admin" />

      <div className="reports-hub">
        <PageHeader title="Centro de reportes" kicker="Reportes" icon="fa-file-alt" breadcrumb={<Breadcrumbs items={[{ label: 'Inicio', href: '/dashboard' }, { label: 'Reportes' }]} />}>
          <p>Consulta reportes analíticos de ventas, inventario, clientes, búsquedas y actividad administrativa.</p>
        </PageHeader>

        <div className="reports-cards">
          {CARDS.map((card) => (
            <a href={card.href} className="report-card" key={card.href}>
              <div className="report-card-icon"><i className={`fas ${card.icon}`} aria-hidden="true" /></div>
              <div className="report-card-body">
                <h2>{card.title}</h2>
                <p>{card.description}</p>
              </div>
              <span className="report-card-arrow"><i className="fas fa-arrow-right" aria-hidden="true" /></span>
            </a>
          ))}
        </div>
      </div>
    </AdminLayout>
  );
}
