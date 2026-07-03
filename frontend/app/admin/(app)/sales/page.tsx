"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { CreditCard, Download, Plus, RotateCcw, Search, ShoppingCart } from "lucide-react";

import { getSales, type SaleRow } from "@/lib/api/admin/sales";
import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { DataTable } from "@/components/admin/data-table";
import { AdminCard } from "@/components/admin/admin-card";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { SaleRowActions } from "@/components/admin/sales/sale-row-actions";
import { NewSaleDialog } from "@/components/admin/sales/new-sale-dialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const ALL = "all";
const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function statusTone(status: string): StatusTone {
  if (status === "completed") return "success";
  if (status === "ready_to_pickup" || status === "pending") return "warning";
  return "danger";
}

const columns: ColumnDef<SaleRow>[] = [
  {
    accessorKey: "invoice_number",
    header: "Factura",
    cell: ({ row }) => <span className="font-medium">{row.original.invoice_number}</span>,
  },
  {
    id: "customer",
    header: "Cliente",
    cell: ({ row }) => (
      <div className="flex flex-col">
        <span>{row.original.customer}</span>
        {row.original.customer_email && (
          <span className="text-xs text-muted-foreground">{row.original.customer_email}</span>
        )}
      </div>
    ),
  },
  {
    accessorKey: "sale_date_label",
    header: "Fecha",
    cell: ({ row }) => <span className="text-sm">{row.original.sale_date_label}</span>,
  },
  {
    accessorKey: "payment_label",
    header: "Pago",
    cell: ({ row }) => <span className="text-sm">{row.original.payment_label}</span>,
  },
  {
    accessorKey: "total",
    header: () => <div className="text-right">Total</div>,
    cell: ({ row }) => <div className="text-right">{crc.format(row.original.total)}</div>,
  },
  {
    accessorKey: "status",
    header: "Estado",
    cell: ({ row }) => (
      <StatusBadge tone={statusTone(row.original.status)}>{row.original.status_label}</StatusBadge>
    ),
  },
  {
    id: "actions",
    header: "",
    cell: ({ row }) => <SaleRowActions sale={row.original} />,
  },
];

export default function SalesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [status, setStatus] = useState("completed");
  const [dateRange, setDateRange] = useState("month");
  const [payment, setPayment] = useState(ALL);
  const [newOpen, setNewOpen] = useState(false);
  const [view, setView] = useViewMode("sales");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const filters = {
    status,
    date_range: dateRange,
    payment_method: payment === ALL ? "" : payment,
    search: debounced,
  };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-sales", page, filters],
    queryFn: () => getSales({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const exportHref = `${API_URL}/sales/export?${new URLSearchParams(
    Object.entries(filters).filter(([, v]) => v !== ""),
  ).toString()}`;

  return (
    <>
      <PageHeader
        kicker="Ventas"
        icon="fa-cash-register"
        title="Ventas"
        description="Historial de ventas y gestión de pedidos."
        actions={
          <div className="flex gap-2">
            <Button asChild variant="outline">
              <a href={exportHref} target="_blank" rel="noopener noreferrer">
                <Download className="h-4 w-4" />
                Exportar
              </a>
            </Button>
            <Button onClick={() => setNewOpen(true)}>
              <Plus className="h-4 w-4" />
              Nueva venta
            </Button>
          </div>
        }
      />
      <NewSaleDialog open={newOpen} onClose={() => setNewOpen(false)} />

      {data && (
        <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <MetricCard
            label="Ventas de hoy"
            value={crc.format(data.kpis.dailySales)}
            icon={ShoppingCart}
            trend={data.kpis.dailySalesTrend}
          />
          <MetricCard
            label="Transacciones de hoy"
            value={String(data.kpis.dailyTransactions)}
            icon={CreditCard}
            trend={data.kpis.dailyTransactionsTrend}
          />
          <MetricCard label="Devoluciones de hoy" value={String(data.kpis.refunds)} icon={RotateCcw} />
        </div>
      )}

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por factura o cliente…"
            className="pl-8"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select value={status} onValueChange={(v) => { setStatus(v); setPage(1); }}>
          <SelectTrigger className="w-44" size="sm"><SelectValue placeholder="Estado" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="completed">Confirmadas</SelectItem>
            <SelectItem value="cancelled">Rechazadas</SelectItem>
            <SelectItem value="returned">Devueltas</SelectItem>
            <SelectItem value={ALL}>Todas</SelectItem>
          </SelectContent>
        </Select>
        <Select value={dateRange} onValueChange={(v) => { setDateRange(v); setPage(1); }}>
          <SelectTrigger className="w-40" size="sm"><SelectValue placeholder="Rango" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="today">Hoy</SelectItem>
            <SelectItem value="week">Esta semana</SelectItem>
            <SelectItem value="month">Este mes</SelectItem>
          </SelectContent>
        </Select>
        <Select value={payment} onValueChange={(v) => { setPayment(v); setPage(1); }}>
          <SelectTrigger className="w-40" size="sm"><SelectValue placeholder="Pago" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos los pagos</SelectItem>
            <SelectItem value="cash">Efectivo</SelectItem>
            <SelectItem value="sinpe">SINPE Móvil</SelectItem>
            <SelectItem value="transfer">Transferencia</SelectItem>
          </SelectContent>
        </Select>
        <div className="ml-auto">
          <ViewToggle view={view} onChange={setView} />
        </div>
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar las ventas.
          </CardContent>
        </Card>
      ) : (
        <>
          <DataTable
            columns={columns}
            data={data.sales}
            emptyTitle="Sin ventas"
            view={view}
            rowKey={(s) => s.sale_id}
            renderCard={(s) => (
              <AdminCard
                media={
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-brand-medium dark:text-brand-light">
                    <i className="fas fa-receipt" aria-hidden />
                  </div>
                }
                title={s.invoice_number}
                subtitle={s.customer}
                badge={<StatusBadge tone={statusTone(s.status)}>{s.status_label}</StatusBadge>}
                meta={
                  <>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Fecha</span>
                      <span>{s.sale_date_label}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Pago</span>
                      <span>{s.payment_label}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Total</span>
                      <span className="font-medium">{crc.format(s.total)}</span>
                    </div>
                  </>
                }
                actions={<SaleRowActions sale={s} />}
              />
            )}
          />
          <PaginationControls
            currentPage={data.pagination.currentPage}
            lastPage={data.pagination.lastPage}
            total={data.pagination.total}
            perPage={data.pagination.perPage}
            onPageChange={setPage}
          />
        </>
      )}
    </>
  );
}
