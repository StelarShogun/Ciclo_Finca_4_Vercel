"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Heart, ShoppingCart, Star } from "lucide-react";

import { storeMediaUrl, type CatalogProduct } from "@/lib/api/client/catalog";
import { addToCart } from "@/lib/api/client/cart";
import { toggleFavorite } from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { cn } from "@/lib/utils";

function stockTone(label: string, canBuy: boolean): string {
  const l = label.toLowerCase();
  if (!canBuy || l.includes("agotado") || l.includes("no disponible")) return "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300";
  if (l.includes("última") || l.includes("ultima") || l.includes("pocas")) return "bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300";
  return "bg-accent text-[#235347] dark:text-[#8EB69B]";
}

export function ProductCard({ product }: { product: CatalogProduct }) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { data: me } = useMe();
  const img = storeMediaUrl(product.image.desktopWebp ?? product.image.fallback);

  const fav = useMutation({
    mutationFn: () => toggleFavorite(product.id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["catalog"] });
      queryClient.invalidateQueries({ queryKey: ["favorites"] });
    },
    onError: (e) => toast.error((isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo actualizar el favorito."),
  });

  const add = useMutation({
    mutationFn: () => addToCart(product.id, 1),
    onSuccess: () => {
      toast.success("Agregado al carrito");
      queryClient.invalidateQueries({ queryKey: ["cart"] });
    },
    onError: (e) => toast.error((isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo agregar."),
  });

  function onFavorite(e: React.MouseEvent) {
    e.preventDefault();
    if (me?.type !== "client") {
      router.push(`/login?redirect=/product/${product.id}`);
      return;
    }
    fav.mutate();
  }

  return (
    <Card className="group flex flex-col overflow-hidden p-0 transition-shadow hover:shadow-md">
      <div className="relative">
        <Link href={`/product/${product.id}`} className="block aspect-square overflow-hidden bg-muted">
          {img && !product.image.usesPlaceholder ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={img} alt={product.name} className="h-full w-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-muted-foreground">
              <i className={`${product.image.placeholderIconClass ?? "fas fa-box"} text-4xl`} aria-hidden />
            </div>
          )}
        </Link>
        {/* Badges */}
        <div className="absolute left-2 top-2 flex flex-col gap-1">
          {product.isNew && <span className="rounded bg-[#235347] px-1.5 py-0.5 text-[10px] font-semibold text-white">Nuevo</span>}
          {product.isFeatured && <span className="rounded bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">Destacado</span>}
        </div>
        {/* Favorito */}
        <button
          onClick={onFavorite}
          disabled={fav.isPending}
          aria-label="Favorito"
          className="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-[#235347] shadow-sm transition hover:bg-white dark:bg-[#071F1F]/90 dark:text-[#8EB69B] dark:hover:bg-[#071F1F]"
        >
          <Heart className={cn("h-4 w-4", product.isFavorite && "fill-current text-[#12B36A] dark:text-[#2ED27E]")} />
        </button>
      </div>

      <div className="flex flex-1 flex-col gap-1 p-3">
        {product.category && <span className="text-xs text-muted-foreground">{product.category.name}</span>}
        <Link href={`/product/${product.id}`} className="line-clamp-2 text-sm font-medium hover:underline">{product.name}</Link>
        {product.brands.length > 0 && (
          <span className="text-xs text-muted-foreground">Marca: {product.brands.map((b) => b.name).join(", ")}</span>
        )}
        {product.description && (
          <p className="line-clamp-2 text-xs text-muted-foreground">{product.description}</p>
        )}
        <div className="flex items-center gap-2">
          <span className={cn("rounded px-1.5 py-0.5 text-[10px] font-medium", stockTone(product.stockLabel, product.canBuy))}>
            {product.stockLabel}
          </span>
          {product.reviews.count > 0 && (
            <span className="flex items-center gap-0.5 text-xs text-muted-foreground">
              <Star className="h-3 w-3 fill-amber-400 text-amber-400" />{product.reviews.avg.toFixed(1)}
            </span>
          )}
        </div>
        <div className="mt-auto flex items-center justify-between gap-2 pt-2">
          <span className="font-semibold text-[#235347] dark:text-[#8EB69B]">{product.priceFormatted}</span>
          <Button
            size="icon"
            className="h-8 w-8 bg-[#12B36A] hover:bg-[#0E9558]"
            disabled={!product.canBuy || add.isPending}
            title={product.canBuy ? "Agregar al carrito" : "No disponible"}
            onClick={() => add.mutate()}
          >
            <ShoppingCart className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </Card>
  );
}
