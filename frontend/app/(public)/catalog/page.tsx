"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { SlidersHorizontal, X } from "lucide-react";

import { getCatalog } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
import { ListPagination } from "@/components/shared/list-pagination";
import { CategoryRail } from "@/components/storefront/category-rail";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const ALL = "all";

function CatalogInner() {
  const params = useSearchParams();
  const router = useRouter();
  const pathname = usePathname();

  // Todo el estado vive en la URL (como Inertia): recargar restaura la vista.
  const search = params.get("search") ?? "";
  const categoryId = params.get("category_id");
  const page = Math.max(1, Number(params.get("page")) || 1);
  const sortField = params.get("sort") ?? "created_at";
  const direction = params.get("direction") ?? "desc";
  const perPage = params.get("per_page") ?? "10";
  const brand = params.get("brand_id") ?? ALL;
  const appliedMin = params.get("min_price") ?? "";
  const appliedMax = params.get("max_price") ?? "";

  // Borradores de precio: se aplican con "Ver resultados".
  const [minPrice, setMinPrice] = useState(appliedMin);
  const [maxPrice, setMaxPrice] = useState(appliedMax);
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => { setMinPrice(appliedMin); setMaxPrice(appliedMax); }, [appliedMin, appliedMax]);

  function setParams(patch: Record<string, string | null>, opts: { resetPage?: boolean } = {}) {
    const next = new URLSearchParams(params);
    for (const [key, value] of Object.entries(patch)) {
      if (value == null || value === "" || (key === "brand_id" && value === ALL)) next.delete(key);
      else next.set(key, value);
    }
    if (opts.resetPage !== false) next.delete("page");
    const qs = next.toString();
    router.replace(qs ? `${pathname}?${qs}` : pathname, { scroll: false });
  }

  const setPage = (p: number) => setParams({ page: p <= 1 ? null : String(p) }, { resetPage: false });

  const { data, isLoading, isError } = useQuery({
    queryKey: ["catalog", search, categoryId, page, sortField, direction, perPage, brand, appliedMin, appliedMax],
    queryFn: () =>
      getCatalog({
        search,
        category_id: categoryId ? Number(categoryId) : undefined,
        brand_id: brand === ALL ? undefined : Number(brand),
        min_price: appliedMin || undefined,
        max_price: appliedMax || undefined,
        sort: sortField,
        direction,
        per_page: Number(perPage),
        page,
      }),
    placeholderData: keepPreviousData,
  });

  const hasFilters = !!categoryId || brand !== ALL || !!appliedMin || !!appliedMax || !!search;

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <header className="mb-6">
        <p className="text-xs font-medium uppercase tracking-wide text-[#235347] dark:text-[#8EB69B]">Ciclo Finca 4</p>
        <h1 className="text-2xl font-semibold tracking-tight">{search ? `Resultados para “${search}”` : "Catálogo de productos"}</h1>
        {data && (
          <p className="text-sm text-muted-foreground">
            {data.summary.totalProducts} productos · {data.summary.totalCategories} categorías
          </p>
        )}
      </header>

      <div className="flex flex-col gap-6 lg:flex-row">
        <CategoryRail categories={data?.categories ?? []} activeCategoryId={categoryId} />

        {/* Filtros */}
        <aside className="w-full shrink-0 space-y-6 self-start lg:sticky lg:top-20 lg:max-h-[calc(100dvh-4rem-2rem)] lg:w-60 lg:overflow-y-auto">
          <Card>
            <CardContent className="space-y-4 p-4">
              <h2 className="flex items-center gap-2 text-sm font-semibold"><SlidersHorizontal className="h-4 w-4" /> Refinar búsqueda</h2>
              <div className="space-y-1.5">
                <Label>Marca</Label>
                <Select value={brand} onValueChange={(v) => setParams({ brand_id: v })}>
                  <SelectTrigger><SelectValue placeholder="Todas las marcas" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value={ALL}>Todas las marcas</SelectItem>
                    {(data?.brands ?? []).map((b) => (
                      <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label>Precio (₡)</Label>
                <div className="flex items-center gap-2">
                  <Input type="number" min={0} placeholder="Mín" value={minPrice} onChange={(e) => setMinPrice(e.target.value)} className="h-9" />
                  <span className="text-muted-foreground">–</span>
                  <Input type="number" min={0} placeholder="Máx" value={maxPrice} onChange={(e) => setMaxPrice(e.target.value)} className="h-9" />
                </div>
              </div>
              <Button className="w-full bg-[#235347] hover:bg-[#1a3f37]" onClick={() => setParams({ min_price: minPrice, max_price: maxPrice })}>
                Ver resultados
              </Button>
            </CardContent>
          </Card>
        </aside>

        {/* Main */}
        <div className="min-w-0 flex-1">
          <div className="mb-2 flex flex-wrap items-center gap-2">
            {hasFilters && (
              <Button asChild size="sm" variant="ghost" className="text-muted-foreground">
                <Link href="/catalog"><X className="h-4 w-4" /> Limpiar filtros</Link>
              </Button>
            )}
            {data?.selectedCategory && (
              <span className="rounded-full bg-accent px-3 py-1 text-xs text-[#235347] dark:text-[#8EB69B]">{data.selectedCategory.name}</span>
            )}
          </div>
          <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
            <p className="text-sm text-muted-foreground">
              {data ? `Mostrando ${data.products.length} de ${data.pagination.total} productos` : ""}
            </p>
            <div className="flex flex-wrap items-end gap-3">
              <div className="space-y-1">
                <Label className="text-xs text-muted-foreground">Ordenar por</Label>
                <Select value={sortField} onValueChange={(v) => setParams({ sort: v })}>
                  <SelectTrigger className="w-40" size="sm"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="created_at">Más recientes</SelectItem>
                    <SelectItem value="sale_price">Precio</SelectItem>
                    <SelectItem value="name">Nombre</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1">
                <Label className="text-xs text-muted-foreground">Dirección</Label>
                <Select value={direction} onValueChange={(v) => setParams({ direction: v })}>
                  <SelectTrigger className="w-36" size="sm"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="desc">Descendente</SelectItem>
                    <SelectItem value="asc">Ascendente</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1">
                <Label className="text-xs text-muted-foreground">Por página</Label>
                <Select value={perPage} onValueChange={(v) => setParams({ per_page: v })}>
                  <SelectTrigger className="w-24" size="sm"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {["10", "25", "50"].map((n) => <SelectItem key={n} value={n}>{n}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          {isLoading ? (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
              {Array.from({ length: 9 }).map((_, i) => <Skeleton key={i} className="aspect-[3/4]" />)}
            </div>
          ) : isError || !data ? (
            <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar el catálogo.</CardContent></Card>
          ) : data.products.length === 0 ? (
            <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No se encontraron productos.</CardContent></Card>
          ) : (
            <>
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                {data.products.map((p) => <ProductCard key={p.id} product={p} />)}
              </div>
              <div className="mt-8">
                <ListPagination pagination={data.pagination} onPageChange={setPage} label="catálogo" />
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}

export default function CatalogPage() {
  return (
    <Suspense fallback={<div className="mx-auto max-w-7xl px-4 py-8"><Skeleton className="h-96" /></div>}>
      <CatalogInner />
    </Suspense>
  );
}
