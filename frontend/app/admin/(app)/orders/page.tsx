"use client";

import { PackageCheck } from "lucide-react";

import { MetricCard } from "@/components/admin/metric-card";
import { PageHeader } from "@/components/admin/page-header";
import { CancelOrderDialog } from "@/features/admin-orders/components/CancelOrderDialog";
import { OrderDetailsDialog } from "@/features/admin-orders/components/OrderDetailsDialog";
import { OrderTable } from "@/features/admin-orders/components/OrderTable";
import { OrderToolbar } from "@/features/admin-orders/components/OrderToolbar";
import { useAdminOrdersPage } from "@/features/admin-orders/hooks/useAdminOrdersPage";

export default function OrdersPage() {
  const page = useAdminOrdersPage();
  const { data, isLoading, isError } = page.ordersQuery;

  return (
    <>
      <PageHeader
        kicker="Ventas"
        icon="fa-clipboard-check"
        title="Encargos"
        description="Pedidos del carrito web: listos para recoger y confirmación."
      />

      {data && (
        <div className="mb-6 grid gap-4 sm:grid-cols-3">
          <MetricCard label="Pendientes" value={String(data.pendingWebOrdersCount)} icon={PackageCheck} />
        </div>
      )}

      <OrderToolbar
        search={page.search}
        status={page.status}
        view={page.view}
        onSearchChange={page.setSearch}
        onStatusChange={page.setStatus}
        onViewChange={page.setView}
      />

      <OrderTable
        data={data}
        isLoading={isLoading}
        isError={isError}
        view={page.view}
        onPageChange={page.setPage}
        onView={page.openDetail.mutate}
        onMarkReady={page.ready.mutate}
        onComplete={page.complete.mutate}
        onCancel={page.setCancelId}
      />

      <OrderDetailsDialog detail={page.detail} onClose={() => page.setDetail(null)} />
      <CancelOrderDialog
        open={page.cancelId !== null}
        reason={page.reason}
        isPending={page.cancel.isPending}
        onReasonChange={page.setReason}
        onClose={() => page.setCancelId(null)}
        onSubmit={() => page.cancel.mutate()}
      />
    </>
  );
}
