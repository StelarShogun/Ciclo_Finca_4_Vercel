"use client";

import { useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Heart, Minus, Plus, ShoppingCart, Star } from "lucide-react";

import { addToCart } from "@/lib/api/client/cart";
import { toggleFavorite } from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { getProductDetail } from "@/lib/api/client/product";
import { storeMediaUrl } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();
  const [qty, setQty] = useState(1);
  const [slide, setSlide] = useState(0);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["product-detail", id],
    queryFn: () => getProductDetail(id),
    enabled: !!id,
  });

  const fav = useMutation({
    mutationFn: () => toggleFavorite(Number(id)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["product-detail", id] });
      queryClient.invalidateQueries({ queryKey: ["favorites"] });
    },
    onError: (e) => toast.error((isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo actualizar el favorito."),
  });

  function onFavorite() {
    if (me.data?.type !== "client") {
      router.push(`/login?redirect=/product/${id}`);
      return;
    }
    fav.mutate();
  }

  const add = useMutation({
    mutationFn: () => addToCart(Number(id), qty),
    onSuccess: () => {
      toast.success("Agregado al carrito");
      queryClient.invalidateQueries({ queryKey: ["cart"] });
    },
    onError: (e) =>
      toast.error(
        (isAxiosError(e) && (e.response?.data?.message as string)) || "No se pudo agregar al carrito.",
      ),
  });

  if (isLoading) {
    return (
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-2">
        <Skeleton className="aspect-square" />
        <Skeleton className="h-96" />
      </div>
    );
  }
  if (isError || !data) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar el producto.</CardContent></Card>
      </div>
    );
  }

  const p = data.product;
  const slides = p.carouselSlides;
  const current = slides[slide];
  const img = storeMediaUrl(current?.desktopWebp ?? current?.fallback);

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      {/* Migas */}
      <nav className="mb-4 flex flex-wrap items-center gap-1 text-sm text-muted-foreground">
        <Link href="/catalog" className="hover:underline">Catálogo</Link>
        {data.taxonomy.parentCategory && (
          <>
            <span>/</span>
            <Link href={`/catalog?category_id=${data.taxonomy.parentCategory.id}`} className="hover:underline">
              {data.taxonomy.parentCategory.name}
            </Link>
          </>
        )}
        <span>/</span>
        <span className="text-foreground">{p.name}</span>
      </nav>

      <div className="grid gap-8 lg:grid-cols-2">
        {/* Galería */}
        <div>
          <div className="aspect-square overflow-hidden rounded-lg border bg-muted">
            {img && !p.showImagePlaceholder ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={img} alt={p.name} className="h-full w-full object-cover" />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-6xl">🚲</div>
            )}
          </div>
          {slides.length > 1 && (
            <div className="mt-3 flex gap-2 overflow-x-auto">
              {slides.map((s, i) => {
                const t = storeMediaUrl(s.mobileWebp ?? s.fallback);
                return (
                  <button
                    key={i}
                    onClick={() => setSlide(i)}
                    className={`h-16 w-16 shrink-0 overflow-hidden rounded border-2 ${i === slide ? "border-[#235347]" : "border-transparent"}`}
                  >
                    {t && (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={t} alt="" className="h-full w-full object-cover" />
                    )}
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Info + compra */}
        <div className="space-y-4">
          {data.primaryBrand && (
            <span className="text-sm text-muted-foreground">{data.primaryBrand.name}</span>
          )}
          <h1 className="text-2xl font-semibold tracking-tight">{p.name}</h1>
          {data.reviews.totalCount > 0 && (
            <div className="flex items-center gap-1 text-sm text-muted-foreground">
              <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
              {data.reviews.averageStars.toFixed(1)} · {data.reviews.totalCount} reseña(s)
            </div>
          )}
          <p className="text-3xl font-bold text-[#235347]">{p.priceFormatted}</p>
          <p className={`text-sm ${p.isLowStock ? "text-amber-600" : "text-muted-foreground"}`}>{p.stockLabel}</p>

          {p.description && <p className="text-sm leading-relaxed text-foreground/80">{p.description}</p>}

          {p.canBuy ? (
            <div className="flex items-center gap-3 pt-2">
              <div className="flex items-center rounded-md border">
                <Button variant="ghost" size="icon" className="h-9 w-9" onClick={() => setQty((q) => Math.max(1, q - 1))}>
                  <Minus className="h-4 w-4" />
                </Button>
                <span className="w-10 text-center text-sm">{qty}</span>
                <Button variant="ghost" size="icon" className="h-9 w-9" onClick={() => setQty((q) => Math.min(p.stockCurrent, q + 1))}>
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
              <Button
                className="flex-1 bg-[#235347] hover:bg-[#1a3f37]"
                disabled={add.isPending}
                onClick={() => add.mutate()}
              >
                <ShoppingCart className="h-4 w-4" /> Agregar al carrito
              </Button>
              <Button
                variant="outline"
                size="icon"
                className="h-9 w-9 shrink-0"
                title="Favorito"
                disabled={fav.isPending}
                onClick={onFavorite}
              >
                <Heart className={`h-4 w-4 ${p.isFavorite ? "fill-[#235347] text-[#235347]" : ""}`} />
              </Button>
            </div>
          ) : (
            <p className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
              Este producto no está disponible para compra en línea.
            </p>
          )}

          {/* Especificaciones */}
          {data.specs.length > 0 && (
            <div className="pt-4">
              <h2 className="mb-2 font-semibold">Especificaciones</h2>
              <dl className="divide-y rounded-md border">
                {data.specs.map((s, i) => (
                  <div key={i} className="flex justify-between gap-4 px-3 py-2 text-sm">
                    <dt className="text-muted-foreground">{s.dimensionLabel ?? "—"}</dt>
                    <dd className="font-medium">{s.value}</dd>
                  </div>
                ))}
              </dl>
            </div>
          )}
        </div>
      </div>

      {/* Relacionados */}
      {data.relatedProducts.length > 0 && (
        <section className="mt-12">
          <h2 className="mb-4 text-xl font-semibold tracking-tight">También te puede interesar</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {data.relatedProducts.slice(0, 4).map((r) => (
              <ProductCard
                key={r.id}
                product={{
                  id: r.id,
                  name: r.name,
                  description: null,
                  price: r.price,
                  priceFormatted: r.priceFormatted,
                  stockCurrent: 1,
                  stockLabel: "",
                  canBuy: true,
                  isFeatured: false,
                  isNew: false,
                  isFavorite: false,
                  sku: r.sku,
                  url: r.url,
                  category: null,
                  parentCategory: null,
                  brands: [],
                  image: {
                    fallback: (r.image as { fallback?: string } | undefined)?.fallback ?? null,
                    desktopWebp: (r.image as { desktopWebp?: string } | undefined)?.desktopWebp ?? null,
                    mobileWebp: null,
                    usesPlaceholder: !(r.image as { fallback?: string } | undefined)?.fallback,
                    placeholderIconClass: null,
                  },
                  reviews: { avg: 0, count: 0 },
                }}
              />
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
