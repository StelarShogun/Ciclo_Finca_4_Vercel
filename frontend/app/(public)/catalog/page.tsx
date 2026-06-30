"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { SlidersHorizontal, X } from "lucide-react";

import { getCatalog } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
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
import { cn } from "@/lib/utils";

const ALL = "all";
const SORTS = [
  { value: "created_at:desc", label: "Más recientes" },
  { value: "sale_price:asc", label: "Precio: menor a mayor" },
  { value: "sale_price:desc", label: "Precio: mayor a menor" },
  { value: "name:asc", label: "Nombre A-Z" },
];

function CatalogInner() {
  const params = useSearchParams();
  const search = params.get("search") ?? "";
  const categoryId = params.get("category_id");

  const [page, setPage] = useState(1);
  const [sort, setSort] = useState("created_at:desc");
  const [brand, setBrand] = useState(ALL);
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [appliedPrice, setAppliedPrice] = useState<{ min: string; max: string }>({ min: "", max: "" });
  const [sortField, sortDir] = sort.split(":");

  // Cualquier cambio de filtro vuelve a la página 1.
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => setPage(1), [search, categoryId, sort, brand, appliedPrice]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["catalog", search, categoryId, page, sort, brand, appliedPrice],
    queryFn: () =>
      getCatalog({
        search,
        category_id: categoryId ? Number(categoryId) : undefined,
        brand_id: brand === ALL ? undefined : Number(brand),
        min_price: appliedPrice.min || undefined,
        max_price: appliedPrice.max || undefined,
        sort: sortField,
        direction: sortDir,
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

      <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
        {/* Sidebar */}
        <aside className="space-y-6">
          <Card>
            <CardContent className="p-4">
              <h2 className="mb-3 text-sm font-semibold">Categorías</h2>
              <ul className="space-y-0.5 text-sm">
                <li>
                  <Link href="/catalog" className={cn("block rounded px-2 py-1.5 hover:bg-accent", !categoryId && "bg-accent font-medium text-[#235347] dark:text-[#8EB69B]")}>
                    Todos los productos
                  </Link>
                </li>
                {(data?.categories ?? []).map((c) => {
                  const childActive = c.children.some((ch) => String(ch.id) === categoryId);
                  const parentActive = String(c.id) === categoryId;
                  const expanded = parentActive || childActive;
                  return (
                    <li key={c.id}>
                      <Link
                        href={`/catalog?category_id=${c.id}`}
                        className={cn("block rounded px-2 py-1.5 hover:bg-accent", parentActive && "bg-accent font-medium text-[#235347] dark:text-[#8EB69B]")}
                      >
                        {c.name}
                      </Link>
                      {c.children.length > 0 && expanded && (
                        <ul className="mb-1 ml-3 mt-0.5 space-y-0.5 border-l pl-2">
                          {c.children.map((ch) => (
                            <li key={ch.id}>
                              <Link
                                href={`/catalog?category_id=${ch.id}`}
                                className={cn("block rounded px-2 py-1 text-[13px] text-muted-foreground hover:bg-accent hover:text-foreground", String(ch.id) === categoryId && "font-medium text-[#235347] dark:text-[#8EB69B]")}
                              >
                                {ch.name}
                              </Link>
                            </li>
                          ))}
                        </ul>
                      )}
                    </li>
                  );
                })}
              </ul>
            </CardContent>
          </Card>

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
        <div>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div className="flex flex-wrap items-center gap-2">
              {hasFilters && (
                <Button asChild size="sm" variant="ghost" className="text-muted-foreground">
                  <Link href="/catalog"><X className="h-4 w-4" /> Limpiar filtros</Link>
                </Button>
              )}
              {data?.selectedCategory && (
                <span className="rounded-full bg-accent px-3 py-1 text-xs text-[#235347] dark:text-[#8EB69B]">{data.selectedCategory.name}</span>
              )}
            </div>
            <Select value={sort} onValueChange={setSort}>
              <SelectTrigger className="w-56" size="sm"><SelectValue /></SelectTrigger>
              <SelectContent>{SORTS.map((s) => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
            </Select>
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
