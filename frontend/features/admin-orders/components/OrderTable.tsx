"use client";

import { useMemo } from "react";
import type { ColumnDef } from "@tanstack/react-table";

import { AdminCard } from "@/components/admin/admin-card";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import type { ViewMode } from "@/components/admin/view-toggle";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { OrderRow, OrdersIndex } from "@/lib/api/admin/orders";
import { formatCRC } from "@/lib/money";
import { OrderActions } from "./OrderActions";

function orderTone(status: string): StatusTone {
  if (status === "completed") return "success";
  if (status === "ready_to_pickup" || status === "pending") return "warning";
  return "danger";
}

type OrderHandlers = {
  onView: (id: number) => void;
  onMarkReady: (id: number) => void;
  onComplete: (id: number) => void;
  onCancel: (id: number) => void;
};

function buildOrderColumns(handlers: OrderHandlers): ColumnDef<OrderRow>[] {
  return [
    { accessorKey: "reference", header: "Pedido", cell: ({ row }) => <span className="font-medium">{row.original.reference}</span> },
    {
      id: "customer",
      header: "Cliente",
      cell: ({ row }) => (
        <div className="flex flex-col">
          <span>{row.original.customer}</span>
          {row.original.customer_email && <span className="text-xs text-muted-foreground">{row.original.customer_email}</span>}
        </div>
      ),
    },
    { accessorKey: "order_placed_label", header: "Fecha" },
    {
      accessorKey: "total",
      header: () => <div className="text-right">Total</div>,
      cell: ({ row }) => <div className="text-right">{formatCRC(row.original.total)}</div>,
    },
    {
      accessorKey: "status",
      header: "Estado",
      cell: ({ row }) => <StatusBadge tone={orderTone(row.original.status)}>{row.original.status_label}</StatusBadge>,
    },
    { id: "actions", header: "Acciones", cell: ({ row }) => <OrderActions order={row.original} {...handlers} /> },
  ];
}

type OrderTableProps = OrderHandlers & {
  data: OrdersIndex | undefined;
  isLoading: boolean;
  isError: boolean;
  view: ViewMode;
  onPageChange: (page: number) => void;
};

export function OrderTable({ data, isLoading, isError, view, onPageChange, ...handlers }: OrderTableProps) {
  const columns = useMemo(() => buildOrderColumns(handlers), [handlers]);

  if (isLoading) return <Skeleton className="h-96" />;
  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-8 text-center text-sm text-muted-foreground">
          No fue posible cargar los encargos.
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <DataTable
        columns={columns}
        data={data.orders}
        emptyTitle="Sin encargos"
        view={view}
        rowKey={(order) => order.sale_id}
        renderCard={(order) => (
          <AdminCard
            media={
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-brand-medium dark:text-brand-light">
                <i className="fas fa-clipboard-list" aria-hidden />
              </div>
            }
            title={order.reference}
            subtitle={order.customer}
            badge={<StatusBadge tone={orderTone(order.status)}>{order.status_label}</StatusBadge>}
            meta={
              <>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Fecha</span>
                  <span>{order.order_placed_label}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Total</span>
                  <span className="font-medium">{formatCRC(order.total)}</span>
                </div>
              </>
            }
            actions={<OrderActions order={order} {...handlers} />}
          />
        )}
      />
      <PaginationControls
        currentPage={data.pagination.currentPage}
        lastPage={data.pagination.lastPage}
        total={data.pagination.total}
        onPageChange={onPageChange}
      />
    </>
  );
}
