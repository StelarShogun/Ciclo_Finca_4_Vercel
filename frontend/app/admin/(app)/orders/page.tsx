"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { PackageCheck, Search } from "lucide-react";

import { getOrders, type OrderRow } from "@/lib/api/admin/orders";
import { cancelSale, completeSale, getSale, invoiceUrl, markSaleReady, type SaleDetail } from "@/lib/api/admin/sales";
import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { DataTable } from "@/components/admin/data-table";
import { AdminCard } from "@/components/admin/admin-card";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";

const ALL = "all";
const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

function tone(s: string): StatusTone {
  if (s === "completed") return "success";
  if (s === "ready_to_pickup" || s === "pending") return "warning";
  return "danger";
}
function errMsg(e: unknown, f: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || f;
}

export default function OrdersPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [status, setStatus] = useState(ALL);
  const [detail, setDetail] = useState<SaleDetail | null>(null);
  const [cancelId, setCancelId] = useState<number | null>(null);
  const [reason, setReason] = useState("");
  const [view, setView] = useViewMode("orders");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const filters = { status: status === ALL ? "" : status, search: debounced };
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-orders", page, filters],
    queryFn: () => getOrders({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const refresh = () => queryClient.invalidateQueries({ queryKey: ["admin-orders"] });

  const ready = useMutation({ mutationFn: (id: number) => markSaleReady(id), onSuccess: () => { toast.success("Marcado listo"); refresh(); }, onError: (e) => toast.error(errMsg(e, "No se pudo.")) });
  const complete = useMutation({ mutationFn: (id: number) => completeSale(id), onSuccess: () => { toast.success("Encargo confirmado"); refresh(); }, onError: (e) => toast.error(errMsg(e, "No se pudo.")) });
  const cancel = useMutation({
    mutationFn: () => cancelSale(cancelId as number, reason.trim()),
    onSuccess: () => { toast.success("Encargo rechazado"); setCancelId(null); setReason(""); refresh(); },
    onError: (e) => toast.error(errMsg(e, "No se pudo rechazar.")),
  });
  const openDetail = useMutation({ mutationFn: (id: number) => getSale(id), onSuccess: (d) => setDetail(d), onError: (e) => toast.error(errMsg(e, "No se pudo cargar.")) });

  const columns: ColumnDef<OrderRow>[] = [
    { accessorKey: "reference", header: "Pedido", cell: ({ row }) => <span className="font-medium">{row.original.reference}</span> },
    { id: "customer", header: "Cliente", cell: ({ row }) => (
      <div className="flex flex-col"><span>{row.original.customer}</span>{row.original.customer_email && <span className="text-xs text-muted-foreground">{row.original.customer_email}</span>}</div>
    ) },
    { accessorKey: "order_placed_label", header: "Fecha" },
    { accessorKey: "total", header: () => <div className="text-right">Total</div>, cell: ({ row }) => <div className="text-right">{crc.format(row.original.total)}</div> },
    { accessorKey: "status", header: "Estado", cell: ({ row }) => <StatusBadge tone={tone(row.original.status)}>{row.original.status_label}</StatusBadge> },
    { id: "actions", header: "Acciones", cell: ({ row }) => renderActions(row.original) },
  ];

  function renderActions(o: OrderRow) {
    return (
      <ActionBar>
        <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => openDetail.mutate(o.sale_id)} />
        {o.status === "pending" && (
          <ActionBtn icon="fa-box-open" label="Marcar listo" tone="stock" onClick={() => ready.mutate(o.sale_id)} />
        )}
        {o.status === "ready_to_pickup" && (
          <ActionBtn icon="fa-circle-check" label="Confirmar" tone="activate" onClick={() => complete.mutate(o.sale_id)} />
        )}
        {(o.status === "pending" || o.status === "ready_to_pickup") && (
          <ActionBtn icon="fa-xmark" label="Rechazar" tone="delete" onClick={() => setCancelId(o.sale_id)} />
        )}
        {o.status === "completed" && (
          <a
            href={invoiceUrl(o.sale_id)}
            target="_blank"
            rel="noopener noreferrer"
            title="Ver factura"
            aria-label="Ver factura"
            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-600 transition-colors hover:bg-sky-100 dark:text-sky-400 dark:hover:bg-sky-950"
          >
            <i className="fas fa-file-invoice" aria-hidden />
          </a>
        )}
      </ActionBar>
    );
  }

  return (
    <>
      <PageHeader kicker="Ventas" icon="fa-clipboard-check" title="Encargos" description="Pedidos del carrito web: listos para recoger y confirmación." />

      {data && (
        <div className="mb-6 grid gap-4 sm:grid-cols-3">
          <MetricCard label="Pendientes" value={String(data.pendingWebOrdersCount)} icon={PackageCheck} />
        </div>
      )}

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Buscar por referencia o cliente…" className="pl-8" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} />
        </div>
        <Select value={status} onValueChange={(v) => { setStatus(v); setPage(1); }}>
          <SelectTrigger className="w-48" size="sm"><SelectValue placeholder="Estado" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos</SelectItem>
            <SelectItem value="pending">Pendiente</SelectItem>
            <SelectItem value="ready_to_pickup">Listo para recoger</SelectItem>
            <SelectItem value="completed">Confirmado</SelectItem>
            <SelectItem value="cancelled">Rechazado</SelectItem>
          </SelectContent>
        </Select>
        <div className="ml-auto">
          <ViewToggle view={view} onChange={setView} />
        </div>
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">No fue posible cargar los encargos.</CardContent></Card>
      ) : (
        <>
          <DataTable
            columns={columns}
            data={data.orders}
            emptyTitle="Sin encargos"
            view={view}
            rowKey={(o) => o.sale_id}
            renderCard={(o) => (
              <AdminCard
                media={
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-[#235347] dark:text-[#8EB69B]">
                    <i className="fas fa-clipboard-list" aria-hidden />
                  </div>
                }
                title={o.reference}
                subtitle={o.customer}
                badge={<StatusBadge tone={tone(o.status)}>{o.status_label}</StatusBadge>}
                meta={
                  <>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Fecha</span>
                      <span>{o.order_placed_label}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Total</span>
                      <span className="font-medium">{crc.format(o.total)}</span>
                    </div>
                  </>
                }
                actions={renderActions(o)}
              />
            )}
          />
          <PaginationControls currentPage={data.pagination.currentPage} lastPage={data.pagination.lastPage} total={data.pagination.total} onPageChange={setPage} />
        </>
      )}

      {/* Detalle */}
      <Dialog open={!!detail} onOpenChange={(o) => !o && setDetail(null)}>
        <DialogContent className="max-h-[90vh] sm:max-w-[56rem] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-clipboard-list text-[#235347] dark:text-[#8EB69B]" aria-hidden />
              Encargo {detail?.invoice_number ?? ""}
            </DialogTitle>
            <DialogDescription>{detail?.sale_date_label}</DialogDescription>
          </DialogHeader>
          {detail && (
            <div className="space-y-3 text-sm">
              <p className="font-medium">{detail.client ? `${detail.client.name} ${detail.client.first_surname}` : detail.buyer.name ?? "Mostrador"}</p>
              <div className="rounded-md border divide-y">
                {detail.sale_items.map((it) => (
                  <div key={it.id} className="flex justify-between px-3 py-2">
                    <span>{it.product?.name ?? `#${it.product_id}`} × {it.quantity}</span>
                    <span>{crc.format(Number(it.total))}</span>
                  </div>
                ))}
              </div>
              <div className="flex justify-between font-semibold"><span>Total</span><span>{crc.format(Number(detail.total))}</span></div>
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Rechazar con motivo */}
      <Dialog open={cancelId !== null} onOpenChange={(o) => !o && setCancelId(null)}>
        <DialogContent className="sm:max-w-[38rem]">
          <form onSubmit={(e) => { e.preventDefault(); if (reason.trim().length >= 3) cancel.mutate(); }}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <i className="fas fa-triangle-exclamation text-[#d32f2f] dark:text-[#F87171]" aria-hidden />
                Rechazar encargo
              </DialogTitle>
              <DialogDescription>Libera el stock reservado. Indicá el motivo.</DialogDescription>
            </DialogHeader>
            <div className="space-y-1.5 py-4">
              <Label htmlFor="reason">Motivo</Label>
              <Textarea id="reason" autoFocus minLength={3} maxLength={500} value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Mínimo 3 caracteres…" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCancelId(null)}>Cancelar</Button>
              <Button type="submit" variant="destructive" disabled={reason.trim().length < 3 || cancel.isPending}>Rechazar</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
