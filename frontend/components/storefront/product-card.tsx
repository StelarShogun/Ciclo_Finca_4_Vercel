import Link from "next/link";
import { Star } from "lucide-react";

import { storeMediaUrl, type CatalogProduct } from "@/lib/api/client/catalog";
import { Card } from "@/components/ui/card";

export function ProductCard({ product }: { product: CatalogProduct }) {
  const img = storeMediaUrl(product.image.desktopWebp ?? product.image.fallback);

  return (
    <Card className="group flex flex-col overflow-hidden p-0 transition-shadow hover:shadow-md">
      <Link href={`/product/${product.id}`} className="block">
        <div className="relative aspect-square overflow-hidden bg-muted">
          {img && !product.image.usesPlaceholder ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={img}
              alt={product.name}
              className="h-full w-full object-cover transition-transform group-hover:scale-105"
              loading="lazy"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-muted-foreground">
              <span className="text-4xl">🚲</span>
            </div>
          )}
          <div className="absolute left-2 top-2 flex flex-col gap-1">
            {product.isNew && (
              <span className="rounded bg-[#235347] px-1.5 py-0.5 text-[10px] font-semibold text-white">Nuevo</span>
            )}
            {product.isFeatured && (
              <span className="rounded bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">Destacado</span>
            )}
          </div>
        </div>
      </Link>
      <div className="flex flex-1 flex-col gap-1 p-3">
        {product.category && (
          <span className="text-xs text-muted-foreground">{product.category.name}</span>
        )}
        <Link href={`/product/${product.id}`} className="line-clamp-2 text-sm font-medium hover:underline">
          {product.name}
        </Link>
        {product.reviews.count > 0 && (
          <span className="flex items-center gap-1 text-xs text-muted-foreground">
            <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
            {product.reviews.avg.toFixed(1)} ({product.reviews.count})
          </span>
        )}
        <div className="mt-auto flex items-center justify-between pt-2">
          <span className="font-semibold text-[#235347]">{product.priceFormatted}</span>
          <span className="text-xs text-muted-foreground">{product.stockLabel}</span>
        </div>
      </div>
    </Card>
  );
}
