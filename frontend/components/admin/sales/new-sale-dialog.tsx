"use client";

import { useEffect, useMemo, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Plus, Search, Trash2 } from "lucide-react";

import { getProducts } from "@/lib/api/admin/products";
import { createSale, type NewSaleItem } from "@/lib/api/admin/sales";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

const IVA = 13;
const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

type Line = { product_id: number; name: string; sku: string | null; unit_price: number; quantity: number; stock: number };

function errMsg(e: unknown, fallback: string) {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

export function NewSaleDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const queryClient = useQueryClient();
  const [lines, setLines] = useState<Line[]>([]);
  const [showPicker, setShowPicker] = useState(false);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [buyerName, setBuyerName] = useState("");
  const [buyerEmail, setBuyerEmail] = useState("");
  const [payment, setPayment] = useState("cash");
  const [paymentRef, setPaymentRef] = useState("");
  const [discount, setDiscount] = useState("0");
  const [notes, setNotes] = useState("");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 300);
    return () => clearTimeout(t);
  }, [search]);

  const results = useQuery({
    queryKey: ["sale-product-search", debounced],
    queryFn: () => getProducts({ search: debounced, status: "active", per_page: 10 }),
    enabled: showPicker && debounced.length > 1,
    placeholderData: keepPreviousData,
  });

  const subtotal = useMemo(() => lines.reduce((s, l) => s + l.unit_price * l.quantity, 0), [lines]);
  const discountNum = Math.max(0, Number(discount) || 0);
  const taxable = Math.max(0, subtotal - discountNum);
  const iva = (taxable * IVA) / 100;
  const total = taxable + iva;
  const existingIds = new Set(lines.map((l) => l.product_id));

  function reset() {
    setLines([]); setBuyerName(""); setBuyerEmail(""); setPayment("cash"); setPaymentRef(""); setDiscount("0"); setNotes(""); setSearch("");
  }

  function addLine(p: { product_id: number; name: string; sku: string | null; sale_price: string; stock_current: number }) {
    setLines((prev) => [...prev, { product_id: p.product_id, name: p.name, sku: p.sku, unit_price: Number(p.sale_price), quantity: 1, stock: p.stock_current }]);
    setShowPicker(false); setSearch("");
  }
  function setQty(id: number, qty: number) {
    setLines((prev) => prev.map((l) => (l.product_id === id ? { ...l, quantity: Math.max(1, Math.min(l.stock, qty || 1)) } : l)));
  }
  function removeLine(id: number) {
    setLines((prev) => prev.filter((l) => l.product_id !== id));
  }

  const save = useMutation({
    mutationFn: () => {
      const items: NewSaleItem[] = lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity, precio_unitario: l.unit_price, total: l.unit_price * l.quantity }));
      return createSale({
        buyer_name: buyerName.trim() || undefined,
        buyer_email: buyerEmail.trim() || undefined,
        payment_method: payment,
        payment_reference: paymentRef.trim() || undefined,
        discount: discountNum,
        iva_percentage: IVA,
        notes: notes.trim() || undefined,
        items,
      });
    },
    onSuccess: () => {
      toast.success("Venta creada");
      reset();
      queryClient.invalidateQueries({ queryKey: ["admin-sales"] });
      onClose();
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible crear la venta.")),
  });

  const canSave = lines.length > 0 && discountNum <= subtotal && !save.isPending;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className="fas fa-cash-register text-[#235347] dark:text-[#8EB69B]" aria-hidden />
            Nueva venta
          </DialogTitle>
          <DialogDescription>Registrá una venta de mostrador.</DialogDescription>
        </DialogHeader>

        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
          <div className="space-y-3">
            {/* Buscador de productos */}
            {showPicker ? (
              <div className="rounded-md border p-3">
                <div className="relative">
                  <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                  <Input autoFocus placeholder="Buscar producto…" className="pl-8" value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <div className="mt-2 max-h-56 overflow-y-auto">
                  {debounced.length <= 1 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">Escribí para buscar.</p>
                  ) : results.isLoading ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">Buscando…</p>
                  ) : (
                    <ul className="divide-y">
                      {(results.data?.data ?? []).filter((p) => !existingIds.has(p.product_id) && p.stock_current > 0).map((p) => (
                        <li key={p.product_id} className="flex items-center justify-between gap-2 py-2">
                          <div className="min-w-0"><p className="truncate text-sm font-medium">{p.name}</p><p className="text-xs text-muted-foreground">{crc.format(Number(p.sale_price))} · stock {p.stock_current}</p></div>
                          <Button size="sm" onClick={() => addLine(p)}>Agregar</Button>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
                <Button size="sm" variant="ghost" className="mt-2" onClick={() => setShowPicker(false)}>Cerrar buscador</Button>
              </div>
            ) : (
              <Button size="sm" variant="outline" onClick={() => setShowPicker(true)}><Plus className="h-4 w-4" /> Agregar producto</Button>
            )}

            {lines.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted-foreground">Agregá al menos un producto.</p>
            ) : (
              <Table>
                <TableHeader><TableRow><TableHead>Producto</TableHead><TableHead className="w-24 text-right">Precio</TableHead><TableHead className="w-20 text-center">Cant.</TableHead><TableHead className="w-24 text-right">Total</TableHead><TableHead className="w-8" /></TableRow></TableHeader>
                <TableBody>
                  {lines.map((l) => (
                    <TableRow key={l.product_id}>
                      <TableCell><p className="font-medium">{l.name}</p><p className="text-xs text-muted-foreground">{l.sku ?? "Sin SKU"}</p></TableCell>
                      <TableCell className="text-right">{crc.format(l.unit_price)}</TableCell>
                      <TableCell><Input type="number" min={1} max={l.stock} value={l.quantity} onChange={(e) => setQty(l.product_id, Number(e.target.value))} className="h-8 text-center" /></TableCell>
                      <TableCell className="text-right">{crc.format(l.unit_price * l.quantity)}</TableCell>
                      <TableCell><Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" onClick={() => removeLine(l.product_id)}><Trash2 className="h-4 w-4" /></Button></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </div>

          <div className="space-y-4">
            <div className="space-y-1.5"><Label htmlFor="bn">Nombre (opcional)</Label><Input id="bn" value={buyerName} onChange={(e) => setBuyerName(e.target.value)} placeholder="Mostrador" /></div>
            <div className="space-y-1.5"><Label htmlFor="be">Email (opcional)</Label><Input id="be" type="email" value={buyerEmail} onChange={(e) => setBuyerEmail(e.target.value)} /></div>
            <div className="space-y-1.5">
              <Label>Método de pago</Label>
              <Select value={payment} onValueChange={setPayment}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="cash">Efectivo</SelectItem><SelectItem value="sinpe">SINPE Móvil</SelectItem><SelectItem value="transfer">Transferencia</SelectItem></SelectContent></Select>
            </div>
            {payment !== "cash" && <div className="space-y-1.5"><Label htmlFor="pr">Referencia</Label><Input id="pr" value={paymentRef} onChange={(e) => setPaymentRef(e.target.value)} /></div>}
            <div className="space-y-1.5"><Label htmlFor="nt">Notas (opcional)</Label><Textarea id="nt" value={notes} onChange={(e) => setNotes(e.target.value)} maxLength={500} /></div>
            <div className="space-y-2 rounded-md border p-3 text-sm">
              <div className="flex justify-between text-muted-foreground"><span>Subtotal</span><span>{crc.format(subtotal)}</span></div>
              <div className="flex items-center justify-between gap-2"><Label htmlFor="dc" className="text-muted-foreground">Descuento</Label><Input id="dc" type="number" min={0} value={discount} onChange={(e) => setDiscount(e.target.value)} className="h-8 w-24 text-right" /></div>
              <div className="flex justify-between text-muted-foreground"><span>IVA ({IVA}%)</span><span>{crc.format(iva)}</span></div>
              <div className="flex justify-between border-t pt-2 text-base font-semibold"><span>Total</span><span>{crc.format(total)}</span></div>
            </div>
            <Button className="w-full bg-[#235347] hover:bg-[#1a3f37]" disabled={!canSave} onClick={() => save.mutate()}>Registrar venta</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
