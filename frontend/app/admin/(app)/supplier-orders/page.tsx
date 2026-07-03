"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import Link from "next/link";
import { FileCode2, Plus, Search } from "lucide-react";

import { getSupplierOrders, type SupplierOrderRow } from "@/lib/api/admin/supplier-orders";
import { PageHeader } from "@/components/admin/page-header";
import { DataTable } from "@/components/admin/data-table";
import { AdminCard } from "@/components/admin/admin-card";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { SupplierOrderDetail } from "@/components/admin/supplier-orders/supplier-order-detail";
import { NewSupplierOrderDialog } from "@/components/admin/supplier-orders/new-supplier-order-dialog";
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
const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function stateTone(state: string): StatusTone {
  if (state === "delivered") return "success";
  if (state === "confirmed" || state === "partial_received") return "warning";
  if (state === "cancelled") return "danger";
  return "neutral";
}

export default function SupplierOrdersPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [state, setState] = useState(ALL);
  const [openId, setOpenId] = useState<number | null>(null);
  const [newOpen, setNewOpen] = useState(false);
  const [view, setView] = useViewMode("supplier-orders");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const filters = { state: state === ALL ? "" : state, search: debounced };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-supplier-orders", page, filters],
    queryFn: () => getSupplierOrders({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const columns: ColumnDef<SupplierOrderRow>[] = [
    {
      accessorKey: "po_full",
      header: "Pedido",
      cell: ({ row }) => (
        <button
          className="font-medium hover:underline"
          onClick={() => setOpenId(row.original.num_order)}
        >
          {row.original.po_full}
        </button>
      ),
    },
    {
      id: "supplier",
      header: "Proveedor",
      cell: ({ row }) => row.original.supplier_name ?? "—",
    },
    { accessorKey: "date_label", header: "Fecha" },
    { accessorKey: "edd_label", header: "Entrega est." },
    {
      accessorKey: "initial_total",
      header: () => <div className="text-right">Total</div>,
      cell: ({ row }) => <div className="text-right">{crc.format(row.original.initial_total)}</div>,
    },
    {
      accessorKey: "state",
      header: "Estado",
      cell: ({ row }) => (
        <StatusBadge tone={stateTone(row.original.state)}>{row.original.state_label}</StatusBadge>
      ),
    },
    {
      id: "actions",
      header: "Acciones",
      cell: ({ row }) => (
        <ActionBar>
          <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => setOpenId(row.original.num_order)} />
        </ActionBar>
      ),
    },
  ];

  return (
    <>
      <PageHeader
        kicker="Abastecimiento"
        icon="fa-truck-ramp-box"
        title="Pedidos a proveedores"
        description={
          data ? `${data.openSupplierOrdersCount} pedido(s) abiertos.` : "Pedidos de reabastecimiento."
        }
        actions={
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="secondary">
              <Link href="/admin/supplier-orders/xml-deviation">
                <FileCode2 className="h-4 w-4" />
                Importar XML
              </Link>
            </Button>
            <Button onClick={() => setNewOpen(true)}>
              <Plus className="h-4 w-4" />
              Nuevo pedido
            </Button>
          </div>
        }
      />
      <NewSupplierOrderDialog open={newOpen} onClose={() => setNewOpen(false)} />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por PO o proveedor…"
            className="pl-8"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select value={state} onValueChange={(v) => { setState(v); setPage(1); }}>
          <SelectTrigger className="w-48" size="sm"><SelectValue placeholder="Estado" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos los estados</SelectItem>
            <SelectItem value="draft">Borrador</SelectItem>
            <SelectItem value="confirmed">Confirmado</SelectItem>
            <SelectItem value="partial_received">Recepción parcial</SelectItem>
            <SelectItem value="delivered">Entregado</SelectItem>
            <SelectItem value="cancelled">Cancelado</SelectItem>
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
            No fue posible cargar los pedidos.
          </CardContent>
        </Card>
      ) : (
        <>
          <DataTable
            columns={columns}
            data={data.orders}
            emptyTitle="Sin pedidos"
            view={view}
            rowKey={(o) => o.num_order}
            renderCard={(o) => (
              <AdminCard
                media={
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-brand-medium dark:text-brand-light">
                    <i className="fas fa-truck-ramp-box" aria-hidden />
                  </div>
                }
                title={o.po_full}
                subtitle={o.supplier_name ?? "—"}
                badge={<StatusBadge tone={stateTone(o.state)}>{o.state_label}</StatusBadge>}
                meta={
                  <>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Fecha</span>
                      <span>{o.date_label}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Entrega est.</span>
                      <span>{o.edd_label}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Total</span>
                      <span className="font-medium">{crc.format(o.initial_total)}</span>
                    </div>
                  </>
                }
                actions={
                  <ActionBar>
                    <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => setOpenId(o.num_order)} />
                  </ActionBar>
                }
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

      <SupplierOrderDetail orderId={openId} onClose={() => setOpenId(null)} />
    </>
  );
}
