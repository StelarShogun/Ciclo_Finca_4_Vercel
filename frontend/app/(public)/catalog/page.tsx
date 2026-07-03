"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { PackageSearch, SlidersHorizontal, X } from "lucide-react";

import { getCatalog } from "@/lib/api/client/catalog";
import { CatalogSpotlight } from "@/components/storefront/catalog-spotlight";
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
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";

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

  const setPage = (p: number) => {
    setParams({ page: p <= 1 ? null : String(p) }, { resetPage: false });
    // Fiel al catálogo viejo: al cambiar de página se vuelve arriba.
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["catalog", search, categoryId, page, sortField, direction, perPage, brand, appliedMin, appliedMax],
    queryFn: () =>
      getCatalog({
        search,
        category_id: categoryId ?? undefined,
        brand_id: brand === ALL ? undefined : brand,
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
  // Como en el catálogo viejo: destacados solo en la primera página y sin filtros.
  const showSpotlight = page === 1 && !hasFilters && (data?.catalogSpotlight?.length ?? 0) > 0;

  // Chips por filtro activo con quitar individual (CatalogActiveFilters viejo).
  const brandName = data?.brands.find((b) => String(b.id) === brand)?.name;
  const chips: { label: string; onRemove: () => void }[] = [];
  if (search) chips.push({ label: `Búsqueda: “${search}”`, onRemove: () => setParams({ search: null }) });
  if (data?.selectedCategory) chips.push({ label: data.selectedCategory.name, onRemove: () => setParams({ category_id: null }) });
  if (brand !== ALL) chips.push({ label: `Marca: ${brandName ?? brand}`, onRemove: () => setParams({ brand_id: null }) });
  if (appliedMin) chips.push({ label: `Desde ₡${appliedMin}`, onRemove: () => setParams({ min_price: null }) });
  if (appliedMax) chips.push({ label: `Hasta ₡${appliedMax}`, onRemove: () => setParams({ max_price: null }) });

  // Card de filtros compartida entre el aside (desktop) y el drawer (móvil).
  const filtersCard = (
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
        <Button className="w-full bg-brand-medium hover:bg-brand-medium-dark" onClick={() => setParams({ min_price: minPrice, max_price: maxPrice })}>
          Ver resultados
        </Button>
      </CardContent>
    </Card>
  );

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <header className="mb-6">
        <p className="text-xs font-medium uppercase tracking-wide text-brand-medium dark:text-brand-light">Ciclo Finca 4</p>
        <h1 className="text-2xl font-semibold tracking-tight">{search ? `Resultados para “${search}”` : "Catálogo de productos"}</h1>
        {data && (
          <p className="text-sm text-muted-foreground">
            {data.summary.totalProducts} productos · {data.summary.totalCategories} categorías
          </p>
        )}
      </header>

      <div className="flex flex-col gap-6 lg:flex-row">
        <CategoryRail categories={data?.categories ?? []} activeCategoryId={categoryId} />

        {/* Filtros: aside en desktop, drawer en móvil (CatalogMobileControls viejo) */}
        <aside className="hidden w-60 shrink-0 space-y-6 self-start lg:sticky lg:top-20 lg:block lg:max-h-[calc(100dvh-4rem-2rem)] lg:overflow-y-auto">
          {filtersCard}
        </aside>

        {/* Main */}
        <div className="min-w-0 flex-1">
          {showSpotlight && <CatalogSpotlight items={data!.catalogSpotlight} />}

          {/* Botón de filtros en móvil */}
          <div className="mb-3 lg:hidden">
            <Sheet>
              <SheetTrigger asChild>
                <Button variant="outline" size="sm" className="w-full">
                  <SlidersHorizontal className="h-4 w-4" /> Filtros
                  {chips.length > 0 && (
                    <span className="ml-1 rounded-full bg-brand-medium px-1.5 text-[11px] text-white">{chips.length}</span>
                  )}
                </Button>
              </SheetTrigger>
              <SheetContent side="left" className="w-[85vw] max-w-sm overflow-y-auto p-4">
                <SheetHeader className="p-0 pb-3">
                  <SheetTitle>Refinar búsqueda</SheetTitle>
                </SheetHeader>
                {filtersCard}
              </SheetContent>
            </Sheet>
          </div>

          {/* Chips de filtros activos, con quitar individual (como el viejo) */}
          {chips.length > 0 && (
            <div className="mb-2 flex flex-wrap items-center gap-2">
              {chips.map((chip) => (
                <button
                  key={chip.label}
                  type="button"
                  onClick={chip.onRemove}
                  className="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-xs font-medium text-brand-medium transition hover:bg-accent/70 dark:text-brand-light"
                  aria-label={`Quitar filtro: ${chip.label}`}
                >
                  {chip.label}
                  <X className="h-3 w-3" />
                </button>
              ))}
              <Button asChild size="sm" variant="ghost" className="h-7 text-muted-foreground">
                <Link href="/catalog">Limpiar todo</Link>
              </Button>
            </div>
          )}
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
            <Card>
              <CardContent className="flex flex-col items-center gap-3 py-14 text-center">
                <span className="grid h-14 w-14 place-items-center rounded-full bg-accent text-brand-medium dark:text-brand-light">
                  <PackageSearch className="h-7 w-7" />
                </span>
                <div>
                  <p className="font-semibold">No se encontraron productos</p>
                  <p className="text-sm text-muted-foreground">
                    {hasFilters ? "Probá ajustar o quitar algunos filtros." : "Pronto agregaremos más productos al catálogo."}
                  </p>
                </div>
                {hasFilters && (
                  <Button asChild variant="outline" size="sm">
                    <Link href="/catalog"><X className="h-4 w-4" /> Limpiar filtros</Link>
                  </Button>
                )}
              </CardContent>
            </Card>
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
