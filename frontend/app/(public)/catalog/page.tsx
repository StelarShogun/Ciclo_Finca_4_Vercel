"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { SlidersHorizontal, X } from "lucide-react";

import { getCatalog } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
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
  const search = params.get("search") ?? "";
  const categoryId = params.get("category_id");

  const [page, setPage] = useState(1);
  const [sortField, setSortField] = useState("created_at");
  const [direction, setDirection] = useState("desc");
  const [perPage, setPerPage] = useState("10");
  const [brand, setBrand] = useState(ALL);
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [appliedPrice, setAppliedPrice] = useState<{ min: string; max: string }>({ min: "", max: "" });

  // Cualquier cambio de filtro vuelve a la página 1.
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => setPage(1), [search, categoryId, sortField, direction, perPage, brand, appliedPrice]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["catalog", search, categoryId, page, sortField, direction, perPage, brand, appliedPrice],
    queryFn: () =>
      getCatalog({
        search,
        category_id: categoryId ? Number(categoryId) : undefined,
        brand_id: brand === ALL ? undefined : Number(brand),
        min_price: appliedPrice.min || undefined,
        max_price: appliedPrice.max || undefined,
        sort: sortField,
        direction,
        per_page: Number(perPage),
        page,
      }),
    placeholderData: keepPreviousData,
  });

  const hasFilters = !!categoryId || brand !== ALL || !!appliedPrice.min || !!appliedPrice.max || !!search;

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
        <aside className="w-full shrink-0 space-y-6 lg:w-60">
          <Card>
            <CardContent className="space-y-4 p-4">
              <h2 className="flex items-center gap-2 text-sm font-semibold"><SlidersHorizontal className="h-4 w-4" /> Refinar búsqueda</h2>
              <div className="space-y-1.5">
                <Label>Marca</Label>
                <Select value={brand} onValueChange={setBrand}>
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
              <Button className="w-full bg-[#235347] hover:bg-[#1a3f37]" onClick={() => setAppliedPrice({ min: minPrice, max: maxPrice })}>
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
                <Select value={sortField} onValueChange={setSortField}>
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
                <Select value={direction} onValueChange={setDirection}>
                  <SelectTrigger className="w-36" size="sm"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="desc">Descendente</SelectItem>
                    <SelectItem value="asc">Ascendente</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1">
                <Label className="text-xs text-muted-foreground">Por página</Label>
                <Select value={perPage} onValueChange={setPerPage}>
                  <SelectTrigger className="w-24" size="sm"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {["10", "25", "50", "100"].map((n) => <SelectItem key={n} value={n}>{n}</SelectItem>)}
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
              {data.pagination.lastPage > 1 && (
                <div className="mt-8 flex items-center justify-center gap-3">
                  <Button variant="outline" disabled={data.pagination.currentPage <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>Anterior</Button>
                  <span className="text-sm text-muted-foreground">Página {data.pagination.currentPage} de {data.pagination.lastPage}</span>
                  <Button variant="outline" disabled={data.pagination.currentPage >= data.pagination.lastPage} onClick={() => setPage((p) => p + 1)}>Siguiente</Button>
                </div>
              )}
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
