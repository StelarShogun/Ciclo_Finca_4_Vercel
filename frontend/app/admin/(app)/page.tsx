import { LayoutDashboard, Package, ShoppingCart, Users } from "lucide-react";

import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { EmptyState } from "@/components/admin/empty-state";

export default function DashboardPage() {
  return (
    <>
      <PageHeader
        title="Dashboard"
        description="Resumen general. Los KPIs y gráficos reales llegan en el Bloque 4."
      />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard label="Productos" value="—" icon={Package} />
        <MetricCard label="Ventas hoy" value="—" icon={ShoppingCart} trend={0} />
        <MetricCard label="Clientes" value="—" icon={Users} />
        <MetricCard label="Pedidos" value="—" icon={LayoutDashboard} />
      </div>
      <div className="mt-6">
        <EmptyState
          title="Shell del panel listo"
          description="Sidebar, header, guard de sesión y componentes base funcionando. El contenido real de cada módulo se construye en los próximos bloques."
        />
      </div>
    </>
  );
}
