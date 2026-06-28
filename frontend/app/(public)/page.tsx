"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { ArrowRight } from "lucide-react";

import { getHome, type HomeProduct } from "@/lib/api/client/home";
import { ProductCard } from "@/components/storefront/product-card";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

/** Mapea el producto del home (category string) al shape de ProductCard. */
function toCardProduct(p: HomeProduct) {
  return {
    ...p,
    isFeatured: false,
    isNew: false,
    isFavorite: false,
    category: p.category ? { id: 0, name: p.category } : null,
    parentCategory: null,
    brands: [],
    image: { ...p.image },
  };
}

export default function HomePage() {
  const me = useMe();
  const { data, isLoading } = useQuery({ queryKey: ["home"], queryFn: getHome });
  const isGuest = me.data?.type !== "client";

  return (
    <div>
      {/* Hero */}
      <section className="bg-[#051F20] text-[#DAF1DE]">
        <div className="mx-auto max-w-7xl px-4 py-16 sm:py-24">
          {data ? (
            <div className="max-w-2xl">
              <h1 className="text-3xl font-bold tracking-tight sm:text-5xl">
                {data.hero.title}{" "}
                <span className="text-[#8fcaa9]">{data.hero.emphasis}</span>
              </h1>
              <p className="mt-4 text-lg text-[#DAF1DE]/90">{data.hero.subtitle}</p>
              <p className="mt-2 text-sm text-[#DAF1DE]/70">{data.hero.description}</p>
              <div className="mt-8 flex flex-wrap gap-3">
                <Button asChild size="lg" className="bg-[#235347] hover:bg-[#1a3f37]">
                  <Link href="/catalog">Ver catálogo <ArrowRight className="h-4 w-4" /></Link>
                </Button>
                {isGuest && data.showGuestRegisterCta && (
                  <Button asChild size="lg" variant="outline" className="border-[#DAF1DE]/40 bg-transparent text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
                    <Link href="/register">Crear cuenta</Link>
                  </Button>
                )}
              </div>
            </div>
          ) : (
            <Skeleton className="h-40 max-w-2xl bg-white/10" />
          )}
        </div>
      </section>

      <div className="mx-auto max-w-7xl px-4 py-12">
        {/* Categorías */}
        {data && data.categories.length > 0 && (
          <section className="mb-12">
            <h2 className="mb-4 text-xl font-semibold tracking-tight">Categorías</h2>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {data.categories.map((c) => (
                <Link key={c.id} href={`/catalog?category_id=${c.id}`}>
                  <Card className="transition-shadow hover:shadow-md">
                    <CardContent className="flex flex-col gap-1 p-4">
                      <span className="font-medium text-[#235347]">{c.name}</span>
                      {c.description && (
                        <span className="line-clamp-2 text-xs text-muted-foreground">{c.description}</span>
                      )}
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>
          </section>
        )}

        {/* Destacados */}
        <section>
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-xl font-semibold tracking-tight">Destacados</h2>
            <Link href="/catalog" className="text-sm font-medium text-[#235347] hover:underline">Ver todo</Link>
          </div>
          {isLoading ? (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="aspect-[3/4]" />)}
            </div>
          ) : !data || data.featuredProducts.length === 0 ? (
            <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">Pronto tendremos destacados.</CardContent></Card>
          ) : (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {data.featuredProducts.map((p) => (
                <ProductCard key={p.id} product={toCardProduct(p)} />
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
