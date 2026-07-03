"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  cancelSale,
  completeSale,
  getSale,
  invoiceUrl,
  markSaleReady,
  printUrl,
  returnSale,
  type SaleRow,
} from "@/lib/api/admin/sales";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function statusTone(status: string): StatusTone {
  if (status === "completed") return "success";
  if (status === "ready_to_pickup") return "warning";
  if (status === "cancelled" || status === "returned") return "danger";
  return "neutral";
}

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

type ReasonAction = "cancel" | "return";

export function SaleRowActions({ sale }: { sale: SaleRow }) {
  const queryClient = useQueryClient();
  const id = sale.sale_id;
  const [detailOpen, setDetailOpen] = useState(false);
  const [reasonAction, setReasonAction] = useState<ReasonAction | null>(null);
  const [reason, setReason] = useState("");

  const refresh = () => queryClient.invalidateQueries({ queryKey: ["admin-sales"] });

  const detail = useQuery({
    queryKey: ["admin-sale", id],
    queryFn: () => getSale(id),
    enabled: detailOpen,
  });

  function actionMutation(fn: () => Promise<unknown>, ok: string, onDone?: () => void) {
    return {
      mutationFn: fn,
      onSuccess: () => {
        toast.success(ok);
        onDone?.();
        refresh();
      },
      onError: (e: unknown) => toast.error(errMsg(e, "No fue posible completar la acción.")),
    };
  }

  const ready = useMutation(actionMutation(() => markSaleReady(id), "Pedido listo para recoger"));
  const complete = useMutation(actionMutation(() => completeSale(id), "Venta confirmada"));

  const closeReason = () => {
    setReasonAction(null);
    setReason("");
  };

  const reasonMutation = useMutation({
    mutationFn: () => {
      const r = reason.trim();
      return reasonAction === "return" ? returnSale(id, r) : cancelSale(id, r);
    },
    onSuccess: () => {
      toast.success(reasonAction === "return" ? "Venta devuelta" : "Pedido rechazado");
      closeReason();
      refresh();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible completar la acción.")),
  });

  const isPending = sale.status === "pending";
  const isReady = sale.status === "ready_to_pickup";
  const isCompleted = sale.status === "completed";

  const reasonCopy: Record<ReasonAction, { title: string; desc: string; cta: string }> = {
    cancel: {
      title: `Rechazar pedido ${sale.invoice_number}`,
      desc: "Libera el stock reservado. Indicá el motivo del rechazo.",
      cta: "Rechazar",
    },
    return: {
      title: `Devolver venta ${sale.invoice_number}`,
      desc: "Reintegra el stock de una venta confirmada. Indicá el motivo.",
      cta: "Devolver",
    },
  };

  return (
    <>
      <ActionBar>
        <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => setDetailOpen(true)} />
        {isCompleted && (
          <a
            href={invoiceUrl(id)}
            target="_blank"
            rel="noopener noreferrer"
            title="Factura"
            aria-label="Factura"
            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-600 transition-colors hover:bg-sky-100 dark:text-sky-400 dark:hover:bg-sky-950"
          >
            <i className="fas fa-file-invoice" aria-hidden />
          </a>
        )}
        <a
          href={printUrl(id)}
          target="_blank"
          rel="noopener noreferrer"
          title="Imprimir"
          aria-label="Imprimir"
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted"
        >
          <i className="fas fa-print" aria-hidden />
        </a>
        {isPending && (
          <ActionBtn
            icon="fa-box-open"
            label="Marcar listo"
            tone="stock"
            disabled={ready.isPending}
            onClick={() => ready.mutate()}
          />
        )}
        {isReady && (
          <ActionBtn
            icon="fa-circle-check"
            label="Confirmar venta"
            tone="activate"
            disabled={complete.isPending}
            onClick={() => complete.mutate()}
          />
        )}
        {isCompleted && (
          <ActionBtn icon="fa-rotate-left" label="Devolver" tone="delete" onClick={() => setReasonAction("return")} />
        )}
        {(isPending || isReady) && (
          <ActionBtn icon="fa-xmark" label="Rechazar" tone="delete" onClick={() => setReasonAction("cancel")} />
        )}
      </ActionBar>

      {/* Detalle */}
      <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
        <DialogContent className="max-h-[90vh] sm:max-w-[56rem] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-receipt text-brand-medium dark:text-brand-light" aria-hidden />
              Venta {sale.invoice_number}
            </DialogTitle>
            <DialogDescription>{sale.sale_date_label}</DialogDescription>
          </DialogHeader>
          {detail.isLoading ? (
            <Skeleton className="h-64" />
          ) : detail.isError || !detail.data ? (
            <p className="py-8 text-center text-sm text-muted-foreground">No se pudo cargar el detalle.</p>
          ) : (
            <div className="space-y-4 text-sm">
              <div className="flex flex-wrap items-center gap-3">
                <StatusBadge tone={statusTone(detail.data.status)}>{detail.data.status}</StatusBadge>
                <span className="text-muted-foreground">{detail.data.payment_method}</span>
              </div>
              <div>
                <p className="font-medium">
                  {detail.data.client
                    ? `${detail.data.client.name} ${detail.data.client.first_surname}`
                    : detail.data.buyer.name ?? "Mostrador / Sin datos"}
                </p>
                <p className="text-muted-foreground">
                  {detail.data.client?.gmail ?? detail.data.buyer.email ?? "—"}
                </p>
              </div>
              <div className="rounded-md border">
                <table className="w-full text-sm">
                  <thead className="bg-muted/50">
                    <tr>
                      <th className="px-3 py-2 text-left font-medium">Producto</th>
                      <th className="px-3 py-2 text-right font-medium">Cant.</th>
                      <th className="px-3 py-2 text-right font-medium">Precio</th>
                      <th className="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y">
                    {detail.data.sale_items.map((item) => (
                      <tr key={item.id}>
                        <td className="px-3 py-2">{item.product?.name ?? `#${item.product_id}`}</td>
                        <td className="px-3 py-2 text-right">{item.quantity}</td>
                        <td className="px-3 py-2 text-right">{crc.format(Number(item.unit_price))}</td>
                        <td className="px-3 py-2 text-right">{crc.format(Number(item.total))}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="ml-auto w-56 space-y-1">
                <div className="flex justify-between text-muted-foreground">
                  <span>Subtotal</span><span>{crc.format(Number(detail.data.subtotal))}</span>
                </div>
                <div className="flex justify-between text-muted-foreground">
                  <span>IVA</span><span>{crc.format(Number(detail.data.iva))}</span>
                </div>
                {Number(detail.data.discount) > 0 && (
                  <div className="flex justify-between text-muted-foreground">
                    <span>Descuento</span><span>-{crc.format(Number(detail.data.discount))}</span>
                  </div>
                )}
                <div className="flex justify-between border-t pt-1 font-semibold">
                  <span>Total</span><span>{crc.format(Number(detail.data.total))}</span>
                </div>
              </div>
              {detail.data.notes && (
                <p className="text-muted-foreground">Notas: {detail.data.notes}</p>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Acción con motivo (rechazar / devolver / eliminar) */}
      <Dialog open={reasonAction !== null} onOpenChange={(o) => !o && closeReason()}>
        <DialogContent className="sm:max-w-[38rem]">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (reason.trim().length >= 3) reasonMutation.mutate();
            }}
          >
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <i className="fas fa-triangle-exclamation text-[#d32f2f] dark:text-[#F87171]" aria-hidden />
                {reasonAction && reasonCopy[reasonAction].title}
              </DialogTitle>
              <DialogDescription>{reasonAction && reasonCopy[reasonAction].desc}</DialogDescription>
            </DialogHeader>
            <div className="space-y-1.5 py-4">
              <Label htmlFor="reason">Motivo</Label>
              <Textarea
                id="reason"
                autoFocus
                minLength={3}
                maxLength={500}
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder="Mínimo 3 caracteres…"
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={closeReason}>Cancelar</Button>
              <Button type="submit" variant="destructive" disabled={reason.trim().length < 3 || reasonMutation.isPending}>
                {reasonAction && reasonCopy[reasonAction].cta}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
