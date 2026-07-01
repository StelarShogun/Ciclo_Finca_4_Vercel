"use client";

import { useEffect, useMemo, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Plus, Search, Trash2 } from "lucide-react";

import { createSupplierOrder, getSupplierOrders, searchSupplierProducts } from "@/lib/api/admin/supplier-orders";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

type Line = { product_id: number; name: string; sku: string; unit_price: number; quantity: number };

function errMsg(e: unknown, fallback: string) {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

export function NewSupplierOrderDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const queryClient = useQueryClient();
  const [supplierId, setSupplierId] = useState("");
  const [lines, setLines] = useState<Line[]>([]);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 300);
    return () => clearTimeout(t);
  }, [search]);

  const suppliers = useQuery({ queryKey: ["admin-supplier-orders", 1, {}], queryFn: () => getSupplierOrders({ page: 1 }) });
  const results = useQuery({
    queryKey: ["supplier-product-search", supplierId, debounced],
    queryFn: () => searchSupplierProducts(Number(supplierId), debounced),
    enabled: !!supplierId,
    placeholderData: keepPreviousData,
  });

  const total = useMemo(() => lines.reduce((s, l) => s + l.unit_price * l.quantity, 0), [lines]);
  const existingIds = new Set(lines.map((l) => l.product_id));

  function changeSupplier(v: string) { setSupplierId(v); setLines([]); setSearch(""); }
  function addLine(p: { product_id: number; name: string; sku: string; unit_price: number }) { setLines((prev) => [...prev, { ...p, quantity: 1 }]); }
  function setQty(id: number, qty: number) { setLines((prev) => prev.map((l) => (l.product_id === id ? { ...l, quantity: Math.max(1, qty || 1) } : l))); }
  function removeLine(id: number) { setLines((prev) => prev.filter((l) => l.product_id !== id)); }

  const save = useMutation({
    mutationFn: () => createSupplierOrder(Number(supplierId), lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity }))),
    onSuccess: () => {
      toast.success("Pedido creado");
      setSupplierId(""); setLines([]); setSearch("");
      queryClient.invalidateQueries({ queryKey: ["admin-supplier-orders"] });
      onClose();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible crear el pedido.")),
  });

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className="fas fa-truck-ramp-box text-[#235347] dark:text-[#8EB69B]" aria-hidden />
            Nuevo pedido a proveedor
          </DialogTitle>
          <DialogDescription>Pedido de reabastecimiento en borrador.</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label>Proveedor</Label>
            <Select value={supplierId} onValueChange={changeSupplier}>
              <SelectTrigger><SelectValue placeholder="Seleccioná un proveedor" /></SelectTrigger>
              <SelectContent>
                {(suppliers.data?.suppliers ?? []).map((s) => <SelectItem key={s.supplier_id} value={String(s.supplier_id)}>{s.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>

          {supplierId && (
            <div className="rounded-md border p-3">
              <div className="relative">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input placeholder="Buscar producto del proveedor…" className="pl-8" value={search} onChange={(e) => setSearch(e.target.value)} />
              </div>
              <ul className="mt-2 max-h-48 divide-y overflow-y-auto">
                {(results.data ?? []).filter((p) => !existingIds.has(p.product_id)).map((p) => (
                  <li key={p.product_id} className="flex items-center justify-between gap-2 py-2">
                    <div className="min-w-0"><p className="truncate text-sm font-medium">{p.name}</p><p className="text-xs text-muted-foreground">{p.sku} · {crc.format(p.unit_price)}</p></div>
                    <Button size="sm" variant="outline" onClick={() => addLine(p)}><Plus className="h-4 w-4" /> Agregar</Button>
                  </li>
                ))}
                {results.data && results.data.length === 0 && <li className="py-3 text-center text-sm text-muted-foreground">Sin productos para este proveedor.</li>}
              </ul>
            </div>
          )}

          {lines.length === 0 ? (
            <p className="py-4 text-center text-sm text-muted-foreground">Agregá productos al pedido.</p>
          ) : (
            <Table>
              <TableHeader><TableRow><TableHead>Producto</TableHead><TableHead className="w-24 text-right">Precio</TableHead><TableHead className="w-20 text-center">Cant.</TableHead><TableHead className="w-24 text-right">Total</TableHead><TableHead className="w-8" /></TableRow></TableHeader>
              <TableBody>
                {lines.map((l) => (
                  <TableRow key={l.product_id}>
                    <TableCell>{l.name}</TableCell>
                    <TableCell className="text-right">{crc.format(l.unit_price)}</TableCell>
                    <TableCell><Input type="number" min={1} value={l.quantity} onChange={(e) => setQty(l.product_id, Number(e.target.value))} className="h-8 text-center" /></TableCell>
                    <TableCell className="text-right">{crc.format(l.unit_price * l.quantity)}</TableCell>
                    <TableCell><Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" onClick={() => removeLine(l.product_id)}><Trash2 className="h-4 w-4" /></Button></TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}

          <div className="flex items-center justify-between border-t pt-3 text-base font-semibold">
            <span>Total</span><span>{crc.format(total)}</span>
          </div>
          <Button className="w-full bg-[#235347] hover:bg-[#1a3f37]" disabled={!supplierId || lines.length === 0 || save.isPending} onClick={() => save.mutate()}>
            Crear pedido
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
