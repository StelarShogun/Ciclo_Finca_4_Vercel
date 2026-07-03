"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Star } from "lucide-react";

import { addToCart } from "@/lib/api/client/cart";
import { toggleFavorite } from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { getProductDetail, saveReview } from "@/lib/api/client/product";
import { ProductCard } from "@/components/storefront/product-card";
import { ProductGallery } from "@/components/storefront/product/product-gallery";
import { PurchasePanel } from "@/components/storefront/product/purchase-panel";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();
  const [qty, setQty] = useState(1);
  const [activeTab, setActiveTab] = useState<string | null>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["product-detail", id],
    queryFn: () => getProductDetail(id),
    enabled: !!id,
  });

  // Título del documento como la página vieja (Head de Inertia).
  useEffect(() => {
    if (data) document.title = `${data.product.name} - Ciclo Finca 4`;
  }, [data]);

  const fav = useMutation({
    mutationFn: () => toggleFavorite(String(id)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["product-detail", id] });
      queryClient.invalidateQueries({ queryKey: ["favorites"] });
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo actualizar el favorito.")),
  });

  const review = useMutation({
    mutationFn: (stars: number) => saveReview(id, stars),
    onSuccess: () => {
      toast.success("¡Gracias por tu reseña!");
      queryClient.invalidateQueries({ queryKey: ["product-detail", id] });
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo guardar la reseña.")),
  });

  const add = useMutation({
    mutationFn: () => addToCart(String(id), qty),
    onSuccess: () => {
      toast.success("Agregado al carrito");
      queryClient.invalidateQueries({ queryKey: ["cart"] });
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo agregar al carrito.")),
  });

  function requireClient(action: () => void) {
    if (me.data?.type !== "client") {
      router.push(`/login?redirect=/product/${id}`);
      return;
    }
    action();
  }

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
  const tabs: Array<{ id: string; label: string; count?: number }> = [
    ...(data.tabs.hasDescription ? [{ id: "description", label: "Descripción" }] : []),
    ...(data.tabs.hasSpecs ? [{ id: "specs", label: "Especificaciones" }] : []),
    { id: "reviews", label: "Reseñas", count: data.reviews.totalCount },
    ...(data.tabs.hasRelated ? [{ id: "related", label: "Relacionados" }] : []),
  ];
  const currentTab = activeTab ?? data.tabs.defaultTab;

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      {/* Migas de pan completas, como la vieja */}
      <nav className="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground" aria-label="Ruta de navegación">
        <Link href="/" className="hover:text-foreground hover:underline">Inicio</Link>
        <span aria-hidden>/</span>
        <Link href="/catalog" className="hover:text-foreground hover:underline">Catálogo</Link>
        {data.taxonomy.parentCategory && (
          <>
            <span aria-hidden>/</span>
            <Link href={`/catalog?category_id=${data.taxonomy.parentCategory.id}`} className="hover:text-foreground hover:underline">
              {data.taxonomy.parentCategory.name}
            </Link>
          </>
        )}
        {data.taxonomy.subcategory && (
          <>
            <span aria-hidden>/</span>
            <Link href={`/catalog?category_id=${data.taxonomy.subcategory.id}`} className="hover:text-foreground hover:underline">
              {data.taxonomy.subcategory.name}
            </Link>
          </>
        )}
        <span aria-hidden>/</span>
        <span aria-current="page" className="text-foreground">{p.name}</span>
      </nav>

      {/* Hero: galería + panel de compra */}
      <div className="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
        <ProductGallery product={p} />
        <PurchasePanel
          detail={data}
          quantity={qty}
          isBusy={add.isPending}
          isFavoritePending={fav.isPending}
          onQuantityChange={(q) => setQty(Math.max(1, Math.min(p.stockCurrent, q)))}
          onAddToCart={() => requireClient(() => add.mutate())}
          onToggleFavorite={() => requireClient(() => fav.mutate())}
        />
      </div>

      {/* Tabs fieles a la vieja */}
      <div className="mt-10">
        <div role="tablist" aria-label="Información del producto" className="flex flex-wrap gap-1 border-b">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              role="tab"
              aria-selected={currentTab === tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={cn(
                "-mb-px inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition",
                currentTab === tab.id
                  ? "border-[#235347] text-[#235347] dark:border-[#8EB69B] dark:text-[#8EB69B]"
                  : "border-transparent text-muted-foreground hover:text-foreground",
              )}
            >
              {tab.label}
              {typeof tab.count === "number" && (
                <span className="rounded-full bg-accent px-1.5 text-xs text-[#235347] dark:text-[#8EB69B]">{tab.count}</span>
              )}
            </button>
          ))}
        </div>

        <div className="pt-5">
          {/* Descripción */}
          {currentTab === "description" && (
            <article className="rounded-2xl border bg-card p-5">
              <h2 className="mb-3 text-lg font-bold">Descripción del producto</h2>
              {p.description ? (
                <div className="whitespace-pre-wrap text-sm leading-relaxed text-foreground/85">{p.description}</div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Este producto aún no tiene una descripción detallada. Consultá con nuestro equipo o revisá las especificaciones técnicas.
                </p>
              )}
            </article>
          )}

          {/* Especificaciones */}
          {currentTab === "specs" && (
            <article className="rounded-2xl border bg-card p-5">
              <h2 className="mb-3 text-lg font-bold">Características técnicas</h2>
              {data.specs.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {data.specs.map((s, i) => (
                    <span key={i} className="inline-flex items-baseline gap-1.5 rounded-full border bg-background px-3 py-1.5 text-sm">
                      {s.dimensionLabel && <span className="text-xs text-muted-foreground">{s.dimensionLabel}</span>}
                      <span className="font-medium">{s.value}</span>
                    </span>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">Pronto publicaremos las características técnicas de este producto.</p>
              )}
            </article>
          )}

          {/* Reseñas */}
          {currentTab === "reviews" && (
            <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
              <div className="space-y-4">
                <div className="rounded-2xl border bg-card p-5 text-center">
                  <p className="text-4xl font-bold">{data.reviews.averageStars.toFixed(1)}</p>
                  <div className="mt-1 flex justify-center gap-0.5">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star key={i} className={cn("h-4 w-4", i < Math.round(data.reviews.averageStars) ? "fill-amber-400 text-amber-400" : "text-muted-foreground")} />
                    ))}
                  </div>
                  <p className="mt-1 text-xs text-muted-foreground">{data.reviews.totalCount} reseña{data.reviews.totalCount === 1 ? "" : "s"}</p>
                  <div className="mt-4 space-y-1.5">
                    {[5, 4, 3, 2, 1].map((n) => {
                      const count = Number(data.reviews.starDistribution?.[String(n)] ?? 0);
                      const pct = data.reviews.totalCount > 0 ? (count / data.reviews.totalCount) * 100 : 0;
                      return (
                        <div key={n} className="flex items-center gap-2 text-xs">
                          <span className="w-3">{n}</span>
                          <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                          <div className="h-2 flex-1 overflow-hidden rounded bg-muted">
                            <div className="h-full bg-amber-400" style={{ width: `${pct}%` }} />
                          </div>
                          <span className="w-6 text-right text-muted-foreground">{count}</span>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {data.reviews.clientCanReview && (
                  <div className="rounded-2xl border bg-card p-5">
                    <p className="mb-2 text-sm font-semibold">Tu calificación</p>
                    <div className="flex gap-1">
                      {[1, 2, 3, 4, 5].map((n) => (
                        <button key={n} disabled={review.isPending} onClick={() => review.mutate(n)} aria-label={`${n} estrellas`}>
                          <Star className={cn("h-7 w-7 transition", data.reviews.clientReviewStars && n <= data.reviews.clientReviewStars ? "fill-amber-400 text-amber-400" : "text-muted-foreground hover:text-amber-400")} />
                        </button>
                      ))}
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">Solo clientes con compra verificada pueden calificar.</p>
                  </div>
                )}
              </div>

              <div className="space-y-3">
                {data.reviews.items.length === 0 ? (
                  <div className="rounded-2xl border bg-card py-10 text-center text-sm text-muted-foreground">
                    Sin reseñas todavía. ¡Sé la primera persona en calificar este producto!
                  </div>
                ) : (
                  data.reviews.items.map((r) => (
                    <div key={r.id} className="rounded-2xl border bg-card p-4">
                      <div className="flex items-center justify-between">
                        <span className="text-sm font-semibold">{r.author}{r.mine && " (tú)"}</span>
                        <span className="text-xs text-muted-foreground">{r.publishedAt}</span>
                      </div>
                      <div className="mt-1 flex items-center gap-1">
                        {Array.from({ length: 5 }).map((_, i) => (
                          <Star key={i} className={cn("h-3.5 w-3.5", i < r.stars ? "fill-amber-400 text-amber-400" : "text-muted-foreground")} />
                        ))}
                        {r.verified && (
                          <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-accent px-2 py-0.5 text-[10px] font-medium text-[#235347] dark:text-[#8EB69B]">
                            <i className="fas fa-check-circle" aria-hidden /> Compra verificada
                          </span>
                        )}
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}

          {/* Relacionados */}
          {currentTab === "related" && (
            <div>
              <h2 className="mb-4 text-lg font-bold">Productos relacionados</h2>
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {data.relatedProducts.slice(0, 8).map((r) => (
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
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
