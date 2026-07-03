"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { toggleProductFeatured } from "@/lib/api/admin/products";
import { cn } from "@/lib/utils";

/**
 * Estrella de "destacado en tienda" superpuesta a la miniatura del producto.
 * Reemplaza la columna Destacado: el estado vive sobre la imagen y se alterna
 * con un clic, como en la tarjeta del admin viejo.
 */
export function FeaturedStar({
  productId,
  isFeatured,
  queryKey = ["admin-products"],
}: {
  productId: number;
  isFeatured: boolean;
  queryKey?: unknown[];
}) {
  const qc = useQueryClient();
  const m = useMutation({
    mutationFn: () => toggleProductFeatured(productId),
    onSuccess: () => {
      toast.success(isFeatured ? "Quitado de destacados" : "Marcado como destacado");
      qc.invalidateQueries({ queryKey });
    },
    onError: (e) =>
      toast.error(
        (isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo actualizar el destacado.",
      ),
  });

  return (
    <button
      type="button"
      onClick={(e) => {
        e.stopPropagation();
        m.mutate();
      }}
      disabled={m.isPending}
      title={isFeatured ? "Destacado en tienda" : "Marcar como destacado"}
      aria-pressed={isFeatured}
      className={cn(
        "absolute -right-1.5 -top-1.5 z-10 flex h-6 w-6 items-center justify-center rounded-full border shadow-sm transition-colors",
        isFeatured
          ? "border-amber-400 bg-amber-400 text-white"
          : "border-border bg-background text-muted-foreground hover:text-amber-500",
      )}
    >
      <i className={cn(isFeatured ? "fas" : "far", "fa-star text-[11px]")} aria-hidden />
    </button>
  );
}
