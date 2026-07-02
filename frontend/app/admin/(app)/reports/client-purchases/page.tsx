"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Search } from "lucide-react";

import { getClientPurchases, type ClientPurchaseRow } from "@/lib/api/admin/reports";
import { getClientHistory } from "@/lib/api/admin/clients";
import { getSale, type SaleDetail } from "@/lib/api/admin/sales";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const money = new Intl.NumberFormat("es-CR", { maximumFractionDigits: 0 });
const crc = (n: number) => `₡${money.format(Math.round(Number(n) || 0))}`;

export default function ClientPurchasesReport() {
  const [period, setPeriod] = useState("30d");
  const [sort, setSort] = useState("total_purchased");
  const [dir, setDir] = useState("desc");
  const [q, setQ] = useState("");
  const [debounced, setDebounced] = useState("");
  const [page, setPage] = useState(1);
  const [historyFor, setHistoryFor] = useState<ClientPurchaseRow | null>(null);
  const [saleDetail, setSaleDetail] = useState<SaleDetail | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(q), 400);
    return () => clearTimeout(t);
  }, [q]);

  const { data, isLoading } = useQuery({
    queryKey: ["report-client-purchases", period, sort, dir, debounced, page],
    queryFn: () => getClientPurchases({ period, sort, dir, q: debounced, page }),
    placeholderData: keepPreviousData,
  });

  const history = useQuery({
    queryKey: ["report-client-history", historyFor?.client_id],
    queryFn: () => getClientHistory(historyFor!.client_id),
    enabled: !!historyFor,
  });

  const openSale = useMutation({
    mutationFn: (id: number) => getSale(id),
    onSuccess: (d) => setSaleDetail(d),
    onError: (e) =>
      toast.error(
        (isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo cargar la venta.",
      ),
  });

  function sortBy(column: string) {
    if (sort === column) setDir(dir === "asc" ? "desc" : "asc");
    else {
      setSort(column);
      setDir("desc");
    }
    setPage(1);
  }

  const sortIcon = (column: string) =>
    sort === column ? <i className={`fas fa-sort-${dir === "asc" ? "up" : "down"} ml-1`} aria-hidden /> : null;

  const rows = data?.rows ?? [];
  const pag = data?.pagination;
  const historyTotal = (history.data?.orders ?? []).reduce((a, o) => a + Number(o.total), 0);

  return (
    <>
      <ReportHeader
        title="Compras por cliente"
        icon="fa-users"
        description="Consulta el total comprado, la cantidad de órdenes y el ticket promedio. También puedes buscar por nombre, apellido o correo."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="flex gap-1.5" role="group" aria-label="Periodo">
          {(["7d", "30d", "90d"] as const).map((p) => (
            <Button
              key={p}
              size="sm"
              variant={period === p ? "default" : "outline"}
              onClick={() => { setPeriod(p); setPage(1); }}
            >
              {p === "7d" ? "7 días" : p === "30d" ? "30 días" : "90 días"}
            </Button>
          ))}
        </div>
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            type="search"
            placeholder="Buscar por nombre, apellido o correo…"
            className="pl-8"
            value={q}
            onChange={(e) => { setQ(e.target.value); setPage(1); }}
          />
        </div>
      </div>

      {isLoading && !data ? (
        <Skeleton className="h-96" />
      ) : (
        <>
          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Cliente</TableHead>
                    <TableHead>Correo</TableHead>
                    <TableHead className="text-right">
                      <button type="button" className="font-medium hover:underline" onClick={() => sortBy("total_purchased")}>
                        Total comprado{sortIcon("total_purchased")}
                      </button>
                    </TableHead>
                    <TableHead className="text-right">
                      <button type="button" className="font-medium hover:underline" onClick={() => sortBy("orders_count")}>
                        Órdenes{sortIcon("orders_count")}
                      </button>
                    </TableHead>
                    <TableHead className="text-right">
                      <button type="button" className="font-medium hover:underline" onClick={() => sortBy("avg_ticket")}>
                        Ticket promedio{sortIcon("avg_ticket")}
                      </button>
                    </TableHead>
                    <TableHead className="w-20 text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="py-8 text-center text-sm text-muted-foreground">
                        No hay compras de clientes en el periodo seleccionado.
                      </TableCell>
                    </TableRow>
                  ) : rows.map((r) => (
                    <TableRow key={r.client_id}>
                      <TableCell className="font-medium">{r.display_name}</TableCell>
                      <TableCell className="text-muted-foreground">{r.gmail}</TableCell>
                      <TableCell className="text-right">{crc(r.total_purchased)}</TableCell>
                      <TableCell className="text-right">{r.orders_count}</TableCell>
                      <TableCell className="text-right">{crc(r.avg_ticket)}</TableCell>
                      <TableCell>
                        <div className="flex justify-end">
                          <ActionBar>
                            <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => setHistoryFor(r)} />
                          </ActionBar>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {pag && pag.last_page > 1 && (
            <div className="mt-3 flex items-center justify-between gap-3">
              <Button size="sm" variant="outline" disabled={pag.page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                <i className="fas fa-chevron-left" aria-hidden /> Anterior
              </Button>
              <span className="text-sm text-muted-foreground">
                Página {pag.page} de {pag.last_page} · {pag.total} clientes
              </span>
              <Button size="sm" variant="outline" disabled={pag.page >= pag.last_page} onClick={() => setPage((p) => Math.min(pag.last_page, p + 1))}>
                Siguiente <i className="fas fa-chevron-right" aria-hidden />
              </Button>
            </div>
          )}
        </>
      )}

      {/* Detalle: compras completadas del cliente */}
      <Dialog open={!!historyFor} onOpenChange={(o) => !o && setHistoryFor(null)}>
        <DialogContent className="max-h-[90vh] sm:max-w-[38rem] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-user text-[#235347] dark:text-[#8EB69B]" aria-hidden />
              {history.data?.displayName ?? historyFor?.display_name ?? "Compras del cliente"}
            </DialogTitle>
            <DialogDescription>{history.data?.gmail ?? historyFor?.gmail}</DialogDescription>
          </DialogHeader>
          {history.isLoading ? (
            <Skeleton className="h-48" />
          ) : !history.data || history.data.orders.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">
              Este cliente no tiene ventas completadas.
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Factura</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                  <TableHead className="w-16 text-right">Acción</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {history.data.orders.map((o) => (
                  <TableRow key={o.sale_id}>
                    <TableCell><code className="text-xs">{o.invoice_number}</code></TableCell>
                    <TableCell>{o.sale_date}</TableCell>
                    <TableCell className="text-right">{crc(Number(o.total))}</TableCell>
                    <TableCell>
                      <div className="flex justify-end">
                        <ActionBar>
                          <ActionBtn icon="fa-eye" label="Ver venta" tone="view" disabled={openSale.isPending} onClick={() => openSale.mutate(o.sale_id)} />
                        </ActionBar>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
              <TableFooter>
                <TableRow>
                  <TableCell colSpan={2} className="font-semibold">Total</TableCell>
                  <TableCell className="text-right font-semibold">{crc(historyTotal)}</TableCell>
                  <TableCell />
                </TableRow>
              </TableFooter>
            </Table>
          )}
        </DialogContent>
      </Dialog>

      {/* Detalle de la venta */}
      <Dialog open={!!saleDetail} onOpenChange={(o) => !o && setSaleDetail(null)}>
        <DialogContent className="max-h-[90vh] sm:max-w-[56rem] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-receipt text-[#235347] dark:text-[#8EB69B]" aria-hidden />
              Venta {saleDetail?.invoice_number ?? ""}
            </DialogTitle>
            <DialogDescription>{saleDetail?.sale_date_label}</DialogDescription>
          </DialogHeader>
          {saleDetail && (
            <div className="space-y-3 text-sm">
              <div className="rounded-md border divide-y">
                {saleDetail.sale_items.map((it) => (
                  <div key={it.id} className="flex justify-between px-3 py-2">
                    <span>{it.product?.name ?? `#${it.product_id}`} × {it.quantity}</span>
                    <span>{crc(Number(it.total))}</span>
                  </div>
                ))}
              </div>
              <div className="flex justify-between font-semibold">
                <span>Total</span>
                <span>{crc(Number(saleDetail.total))}</span>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
