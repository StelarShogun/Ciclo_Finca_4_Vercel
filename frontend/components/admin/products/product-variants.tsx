"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Plus, Search, X } from "lucide-react";

import {
  addVariant,
  getProducts,
  removeVariant,
  type ProductVariant,
} from "@/lib/api/admin/products";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { StatusBadge } from "@/components/admin/status-badge";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export function ProductVariants({
  productId,
  variants,
}: {
  productId: number;
  variants: ProductVariant[];
}) {
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 300);
    return () => clearTimeout(t);
  }, [search]);

  const refresh = () =>
    queryClient.invalidateQueries({ queryKey: ["admin-product-detail", String(productId)] });

  const results = useQuery({
    queryKey: ["variant-search", debounced],
    queryFn: () => getProducts({ search: debounced, per_page: 10 }),
    enabled: open && debounced.length > 1,
    placeholderData: keepPreviousData,
  });

  const add = useMutation({
    mutationFn: (variantProductId: number) => addVariant(productId, variantProductId),
    onSuccess: () => {
      toast.success("Variante agregada");
      setOpen(false);
      setSearch("");
      refresh();
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo agregar la variante.")),
  });

  const remove = useMutation({
    mutationFn: (variantId: number) => removeVariant(productId, variantId),
    onSuccess: () => {
      toast.success("Variante eliminada");
      refresh();
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo eliminar la variante.")),
  });

  const existingIds = new Set([productId, ...variants.map((v) => v.product_id)]);

  return (
    <Card className="mt-6">
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle>Variantes ({variants.length})</CardTitle>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button size="sm" variant="outline">
              <Plus className="h-4 w-4" /> Agregar
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[38rem]">
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <i className="fas fa-layer-group text-brand-medium dark:text-brand-light" aria-hidden />
                Agregar variante
              </DialogTitle>
            </DialogHeader>
            <div className="relative">
              <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                autoFocus
                placeholder="Buscar producto a enlazar…"
                className="pl-8"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
            <div className="max-h-72 overflow-y-auto">
              {debounced.length <= 1 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">Escribí para buscar.</p>
              ) : results.isLoading ? (
                <p className="py-6 text-center text-sm text-muted-foreground">Buscando…</p>
              ) : (
                <ul className="divide-y">
                  {(results.data?.data ?? [])
                    .filter((p) => !existingIds.has(p.product_id))
                    .map((p) => (
                      <li key={p.product_id} className="flex items-center justify-between gap-2 py-2">
                        <div className="min-w-0">
                          <p className="truncate text-sm font-medium">{p.name}</p>
                          <p className="text-xs text-muted-foreground">{p.sku ?? "Sin SKU"}</p>
                        </div>
                        <Button
                          size="sm"
                          disabled={add.isPending}
                          onClick={() => add.mutate(p.product_id)}
                        >
                          Enlazar
                        </Button>
                      </li>
                    ))}
                </ul>
              )}
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {variants.length === 0 ? (
          <p className="py-4 text-center text-sm text-muted-foreground">Sin variantes.</p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nombre</TableHead>
                <TableHead>SKU</TableHead>
                <TableHead className="text-right">Precio</TableHead>
                <TableHead className="text-right">Stock</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {variants.map((v) => (
                <TableRow key={v.product_id}>
                  <TableCell>{v.name}</TableCell>
                  <TableCell>{v.sku}</TableCell>
                  <TableCell className="text-right">{crc.format(Number(v.sale_price))}</TableCell>
                  <TableCell className="text-right">{v.stock_current}</TableCell>
                  <TableCell>
                    <StatusBadge tone={v.status === "active" ? "success" : "neutral"}>
                      {v.status}
                    </StatusBadge>
                  </TableCell>
                  <TableCell className="text-right">
                    <Button
                      size="icon"
                      variant="ghost"
                      className="h-8 w-8"
                      title="Quitar variante"
                      disabled={remove.isPending}
                      onClick={() => remove.mutate(v.product_id)}
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}
