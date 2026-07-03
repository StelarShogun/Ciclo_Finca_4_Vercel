"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";

import { getProduct, mediaUrl, type ProductFormValues } from "@/lib/api/admin/products";
import { ProductForm } from "@/components/admin/products/product-form";
import { Skeleton } from "@/components/ui/skeleton";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";

function toDefaults(p: Record<string, unknown>): Partial<ProductFormValues> {
  const num = (v: unknown) => (v == null ? undefined : Number(v));
  const category = p.category as Record<string, unknown> | null | undefined;
  const parent = category?.parent as Record<string, unknown> | null | undefined;
  return {
    parent_category_id: num(p.parent_category_id) ?? num(parent?.category_id),
    category_id: num(p.category_id),
    brand_id: num(p.brand_id),
    supplier_id: num(p.supplier_id),
    name: (p.name as string) ?? "",
    description: (p.description as string) ?? "",
    purchase_price: num(p.purchase_price),
    sale_price: num(p.sale_price),
    stock_current: num(p.stock_current),
    stock_minimum: num(p.stock_minimum),
    status: (p.status as string) ?? "active",
    is_featured: Boolean(p.is_featured),
  };
}

/** Modal de creación/edición de producto (reemplaza las páginas /new y /edit). */
export function ProductFormDialog({
  open,
  productId,
  onClose,
}: {
  open: boolean;
  productId?: number | null;
  onClose: () => void;
}) {
  const queryClient = useQueryClient();
  const isEdit = productId != null;

  const { data, isLoading } = useQuery({
    queryKey: ["admin-product", productId],
    queryFn: () => getProduct(productId as number),
    enabled: open && isEdit,
  });

  function done() {
    queryClient.invalidateQueries({ queryKey: ["admin-products"] });
    queryClient.invalidateQueries({ queryKey: ["admin-inventory"] });
    onClose();
  }

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-h-[92vh] sm:max-w-[56rem] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className={`fas ${isEdit ? "fa-pen-to-square" : "fa-box"} text-brand-medium dark:text-brand-light`} aria-hidden />
            {isEdit ? "Editar producto" : "Nuevo producto"}
          </DialogTitle>
          <DialogDescription>Datos básicos, precios y stock. Galería, variantes y clasificaciones se editan desde el detalle.</DialogDescription>
        </DialogHeader>
        {isEdit && isLoading ? (
          <Skeleton className="h-80" />
        ) : isEdit && !data ? (
          <p className="py-8 text-center text-sm text-muted-foreground">No fue posible cargar el producto.</p>
        ) : (
          <ProductForm
            productId={isEdit ? (productId as number) : undefined}
            defaultValues={isEdit && data ? toDefaults(data) : undefined}
            currentImageUrl={isEdit && data ? mediaUrl((data.media_main as string | null) ?? null) : null}
            onSuccess={done}
          />
        )}
      </DialogContent>
    </Dialog>
  );
}
