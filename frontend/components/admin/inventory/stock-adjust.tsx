"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  addStock,
  getMovements,
  removeStock,
  type InventoryProduct,
} from "@/lib/api/admin/inventory";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { StatusBadge } from "@/components/admin/status-badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

/** Diálogo de ajuste de stock (entrada/salida manual) con historial de movimientos. */
export function StockAdjust({
  product,
  open,
  onClose,
}: {
  product: InventoryProduct | null;
  open: boolean;
  onClose: () => void;
}) {
  const queryClient = useQueryClient();
  const [tab, setTab] = useState("add");
  const [quantity, setQuantity] = useState("1");
  const [reason, setReason] = useState("");

  const id = product?.product_id ?? 0;

  const movements = useQuery({
    queryKey: ["admin-inventory-movements", id],
    queryFn: () => getMovements(id),
    enabled: open && !!product && tab === "history",
  });

  function reset() {
    setQuantity("1");
    setReason("");
  }

  const adjust = useMutation({
    mutationFn: () => {
      const q = Number(quantity);
      return tab === "remove" ? removeStock(id, q, reason.trim()) : addStock(id, q, reason.trim());
    },
    onSuccess: () => {
      toast.success(tab === "remove" ? "Salida registrada" : "Entrada registrada");
      reset();
      queryClient.invalidateQueries({ queryKey: ["admin-inventory"] });
      queryClient.invalidateQueries({ queryKey: ["admin-inventory-movements", id] });
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible ajustar el stock.")),
  });

  const validForm = Number(quantity) >= 1 && reason.trim().length >= 3;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className="fas fa-boxes-stacked text-[#235347] dark:text-[#8EB69B]" aria-hidden />
            {product?.name}
          </DialogTitle>
          <DialogDescription>
            {product?.sku} · stock actual {product?.stock}
          </DialogDescription>
        </DialogHeader>

        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="add">Entrada</TabsTrigger>
            <TabsTrigger value="remove">Salida</TabsTrigger>
            <TabsTrigger value="history">Movimientos</TabsTrigger>
          </TabsList>

          {(["add", "remove"] as const).map((mode) => (
            <TabsContent key={mode} value={mode}>
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  if (validForm) adjust.mutate();
                }}
                className="space-y-4"
              >
                <div className="space-y-1.5">
                  <Label htmlFor="q">Cantidad</Label>
                  <Input id="q" type="number" min={1} value={quantity} onChange={(e) => setQuantity(e.target.value)} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="r">Motivo</Label>
                  <Textarea
                    id="r"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    minLength={3}
                    maxLength={500}
                    placeholder={mode === "add" ? "Ej. Reabastecimiento" : "Ej. Merma / daño"}
                  />
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={onClose}>Cerrar</Button>
                  <Button type="submit" disabled={!validForm || adjust.isPending}>
                    {mode === "add" ? "Registrar entrada" : "Registrar salida"}
                  </Button>
                </DialogFooter>
              </form>
            </TabsContent>
          ))}

          <TabsContent value="history">
            {movements.isLoading ? (
              <Skeleton className="h-48" />
            ) : !movements.data ? (
              <p className="py-6 text-center text-sm text-muted-foreground">Sin datos.</p>
            ) : movements.data.data.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted-foreground">Sin movimientos.</p>
            ) : (
              <div className="max-h-80 space-y-2 overflow-y-auto">
                {movements.data.data.map((m) => (
                  <div key={m.id} className="flex items-center justify-between gap-2 rounded-md border p-2 text-sm">
                    <div className="min-w-0">
                      <div className="flex items-center gap-2">
                        <StatusBadge tone={m.type === "salida" ? "danger" : "success"}>
                          {m.type_label}
                        </StatusBadge>
                        <span className="text-muted-foreground">{m.created_at_human}</span>
                      </div>
                      {m.reason && <p className="truncate text-xs text-muted-foreground">{m.reason}</p>}
                    </div>
                    <div className="text-right text-xs">
                      <p className="font-medium">{m.type === "salida" ? "-" : "+"}{m.quantity}</p>
                      <p className="text-muted-foreground">{m.stock_before} → {m.stock_after}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
