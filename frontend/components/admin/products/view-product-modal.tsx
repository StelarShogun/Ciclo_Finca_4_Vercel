"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { Pencil } from "lucide-react";

import { getProductDetail, mediaUrl } from "@/lib/api/admin/products";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

function statusTone(s: string): StatusTone {
  return s === "active" ? "success" : s === "out_of_stock" ? "warning" : "neutral";
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4 py-2 text-sm">
      <span className="text-muted-foreground">{label}</span>
      <span className="text-right font-medium">{value}</span>
    </div>
  );
}

/** Vista rápida de solo lectura del producto (como el ViewProductModal del Inertia). */
export function ViewProductModal({
  productId,
  open,
  onClose,
  onEdit,
}: {
  productId: number | null;
  open: boolean;
  onClose: () => void;
  onEdit?: (id: number) => void;
}) {
  const { data, isLoading } = useQuery({
    queryKey: ["admin-product-detail", productId],
    queryFn: () => getProductDetail(productId as number),
    enabled: open && productId != null,
  });

  const img = mediaUrl(data?.media_main ?? null);

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-h-[90vh] sm:max-w-[56rem] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className="fas fa-eye text-[#235347] dark:text-[#8EB69B]" aria-hidden /> {data?.name ?? "Producto"}
          </DialogTitle>
          <DialogDescription>{data?.sku ? `SKU ${data.sku}` : "Detalle del producto"}</DialogDescription>
        </DialogHeader>

        {isLoading || !data ? (
          <Skeleton className="h-64" />
        ) : (
          <div className="space-y-4">
            <div className="mx-auto h-40 w-40 overflow-hidden rounded-lg border bg-muted">
              {img && !data.uses_placeholder_image ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={img} alt="" className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-muted-foreground">
                  <i className={`${data.placeholder_icon_class ?? "fas fa-box"} text-4xl`} aria-hidden />
                </div>
              )}
            </div>
            <div className="divide-y">
              <Row label="Estado" value={<StatusBadge tone={statusTone(data.status)}>{data.status}</StatusBadge>} />
              <Row label="Categoría" value={data.category?.name ?? "—"} />
              <Row label="Proveedor" value={data.supplier?.name ?? "—"} />
              <Row label="Marca" value={data.brands?.[0]?.name ?? "—"} />
              <Row label="Precio de venta" value={crc.format(Number(data.sale_price))} />
              <Row label="Precio de compra" value={crc.format(Number(data.purchase_price))} />
              <Row label="Stock" value={`${data.stock_current} / mín ${data.stock_minimum}`} />
              {data.description && (
                <div className="py-2 text-sm">
                  <p className="mb-1 text-muted-foreground">Descripción</p>
                  <p>{data.description}</p>
                </div>
              )}
            </div>
            <div className="flex justify-end gap-2">
              <Button asChild variant="outline"><Link href={`/admin/products/${data.product_id}`} onClick={onClose}>Ficha completa</Link></Button>
              {onEdit && (
                <Button className="bg-[#235347] hover:bg-[#1a3f37]" onClick={() => { onClose(); onEdit(data.product_id); }}>
                  <Pencil className="h-4 w-4" /> Editar
                </Button>
              )}
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
