"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  closePartialSupplierOrder,
  getSupplierOrder,
  receiveSupplierOrder,
  updateSupplierOrderState,
  type SupplierOrderDetail as Detail,
} from "@/lib/api/admin/supplier-orders";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
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

function stateTone(state: string): StatusTone {
  if (state === "delivered") return "success";
  if (state === "confirmed" || state === "partial_received") return "warning";
  if (state === "cancelled") return "danger";
  return "neutral";
}

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

type Mode = "view" | "receive" | "cancel" | "close_partial";

export function SupplierOrderDetail({
  orderId,
  onClose,
}: {
  orderId: number | null;
  onClose: () => void;
}) {
  const queryClient = useQueryClient();
  const [mode, setMode] = useState<Mode>("view");
  const [received, setReceived] = useState<Record<number, number>>({});
  const [reason, setReason] = useState("");

  const open = orderId !== null;

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-supplier-order", orderId],
    queryFn: () => getSupplierOrder(orderId as number),
    enabled: open,
  });

  function startReceive(d: Detail) {
    setReceived(Object.fromEntries(d.items.map((it) => [it.id, it.received_quantity ?? it.quantity])));
    setMode("receive");
  }

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ["admin-supplier-orders"] });
    queryClient.invalidateQueries({ queryKey: ["admin-supplier-order", orderId] });
  };

  function reset() {
    setMode("view");
    setReason("");
    setReceived({});
  }

  function close() {
    reset();
    onClose();
  }

  const transition = useMutation({
    mutationFn: (state: string) => updateSupplierOrderState(orderId as number, state),
    onSuccess: (_d, state) => {
      toast.success(state === "cancelled" ? "Pedido cancelado" : "Pedido confirmado");
      invalidate();
      reset();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible cambiar el estado.")),
  });

  const receive = useMutation({
    mutationFn: () =>
      receiveSupplierOrder(
        orderId as number,
        (data?.items ?? []).map((it) => ({
          order_item_id: it.id,
          received_quantity: received[it.id] ?? 0,
        })),
      ),
    onSuccess: () => {
      toast.success("Recepción registrada");
      invalidate();
      reset();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible registrar la recepción.")),
  });

  const closePartial = useMutation({
    mutationFn: () => closePartialSupplierOrder(orderId as number, reason.trim()),
    onSuccess: () => {
      toast.success("Pedido cerrado con faltantes");
      invalidate();
      reset();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible cerrar el pedido.")),
  });

  function setQty(itemId: number, value: number, max: number) {
    setReceived((s) => ({ ...s, [itemId]: Math.max(0, Math.min(max, value || 0)) }));
  }

  function renderActions(d: Detail) {
    const isDraftish = d.state === "draft" || d.state === "pending";
    const canReceive = d.state === "confirmed" || d.state === "partial_received";
    const canClosePartial = d.state === "partial_received";
    const canCancel = !["delivered", "cancelled"].includes(d.state);

    return (
      <div className="flex flex-wrap gap-2">
        {isDraftish && (
          <Button size="sm" onClick={() => transition.mutate("confirmed")} disabled={transition.isPending}>
            Confirmar
          </Button>
        )}
        {canReceive && (
          <Button size="sm" onClick={() => startReceive(d)}>
            Registrar recepción
          </Button>
        )}
        {canClosePartial && (
          <Button size="sm" variant="outline" onClick={() => setMode("close_partial")}>
            Cerrar con faltantes
          </Button>
        )}
        {canCancel && (
          <Button size="sm" variant="destructive" onClick={() => setMode("cancel")}>
            Cancelar pedido
          </Button>
        )}
      </div>
    );
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && close()}>
      <DialogContent className="max-w-2xl">
        {isLoading ? (
          <Skeleton className="h-72" />
        ) : isError || !data ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No se pudo cargar el pedido.</p>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-3">
                {data.po_number}
                <StatusBadge tone={stateTone(data.state)}>{data.state_label}</StatusBadge>
              </DialogTitle>
              <DialogDescription>
                {data.supplier_name} · {data.date_label}
                {data.estimated_delivery_date ? ` · entrega est. ${data.estimated_delivery_date}` : ""}
              </DialogDescription>
            </DialogHeader>

            {/* Vista */}
            {mode === "view" && (
              <div className="space-y-4">
                <div className="rounded-md border">
                  <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                      <tr>
                        <th className="px-3 py-2 text-left font-medium">Producto</th>
                        <th className="px-3 py-2 text-right font-medium">Pedido</th>
                        <th className="px-3 py-2 text-right font-medium">Recibido</th>
                        <th className="px-3 py-2 text-right font-medium">Precio</th>
                        <th className="px-3 py-2 text-right font-medium">Total</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y">
                      {data.items.map((it) => (
                        <tr key={it.id}>
                          <td className="px-3 py-2">{it.name}</td>
                          <td className="px-3 py-2 text-right">{it.quantity}</td>
                          <td className="px-3 py-2 text-right">
                            {it.received_quantity ?? "—"}
                          </td>
                          <td className="px-3 py-2 text-right">{crc.format(it.unit_price)}</td>
                          <td className="px-3 py-2 text-right">{crc.format(it.total)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <div className="flex justify-between text-sm font-semibold">
                  <span>Total</span>
                  <span>{crc.format(data.total)}</span>
                </div>

                {data.timeline.length > 0 && (
                  <div className="space-y-1 text-xs text-muted-foreground">
                    <p className="font-medium text-foreground">Historial</p>
                    {data.timeline.map((t, i) => (
                      <p key={i}>
                        {t.changed_at} · {t.state_label} · {t.user_name}
                        {t.reason ? ` — ${t.reason}` : ""}
                      </p>
                    ))}
                  </div>
                )}

                {renderActions(data)}
              </div>
            )}

            {/* Recepción */}
            {mode === "receive" && (
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  receive.mutate();
                }}
                className="space-y-4"
              >
                <p className="text-sm text-muted-foreground">
                  Indicá la cantidad recibida por producto. El stock entra al confirmar.
                </p>
                <div className="space-y-2">
                  {data.items.map((it) => (
                    <div key={it.id} className="flex items-center justify-between gap-3">
                      <span className="text-sm">{it.name}</span>
                      <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        de {it.quantity}
                        <Input
                          type="number"
                          min={0}
                          max={it.quantity}
                          value={received[it.id] ?? 0}
                          onChange={(e) => setQty(it.id, Number(e.target.value), it.quantity)}
                          className="h-8 w-20 text-right"
                        />
                      </div>
                    </div>
                  ))}
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={reset}>Volver</Button>
                  <Button type="submit" disabled={receive.isPending}>Registrar recepción</Button>
                </DialogFooter>
              </form>
            )}

            {/* Cancelar / cerrar con faltantes */}
            {(mode === "cancel" || mode === "close_partial") && (
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  if (mode === "cancel") transition.mutate("cancelled");
                  else if (reason.trim().length >= 4) closePartial.mutate();
                }}
                className="space-y-4"
              >
                <div className="space-y-1.5">
                  <Label htmlFor="so-reason">
                    {mode === "cancel" ? "Motivo de cancelación (opcional)" : "Motivo del cierre con faltantes"}
                  </Label>
                  <Textarea
                    id="so-reason"
                    autoFocus
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    maxLength={500}
                    placeholder={mode === "close_partial" ? "Mínimo 4 caracteres…" : ""}
                  />
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={reset}>Volver</Button>
                  <Button
                    type="submit"
                    variant="destructive"
                    disabled={
                      (mode === "close_partial" && reason.trim().length < 4) ||
                      transition.isPending ||
                      closePartial.isPending
                    }
                  >
                    {mode === "cancel" ? "Cancelar pedido" : "Cerrar con faltantes"}
                  </Button>
                </DialogFooter>
              </form>
            )}
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}
