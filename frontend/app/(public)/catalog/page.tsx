"use client";

import { Suspense, useState } from "react";
import { useSearchParams } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";

import { getCatalog } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

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
  const [sortField, sortDir] = sort.split(":");

  const { data, isLoading, isError } = useQuery({
    queryKey: ["catalog", search, categoryId, page, sort],
    queryFn: () =>
      getCatalog({
        search,
        category_id: categoryId ? Number(categoryId) : undefined,
        sort: sortField,
        direction: sortDir,
        page,
      }),
    placeholderData: keepPreviousData,
  });

  return (
    <div className="mx-auto max-w-7xl px-4 py-8">
      <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {search ? `Resultados para "${search}"` : "Catálogo"}
          </h1>
          {data && (
            <p className="text-sm text-muted-foreground">
              {data.summary.totalProducts} producto(s)
              {data.selectedCategory ? ` en ${data.selectedCategory.name}` : ""}
            </p>
          )}
        </div>
        <Select value={sort} onValueChange={(v) => { setSort(v); setPage(1); }}>
          <SelectTrigger className="w-56"><SelectValue /></SelectTrigger>
          <SelectContent>
            {SORTS.map((s) => (
              <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="aspect-[3/4]" />
          ))}
        </div>
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-12 text-center text-sm text-muted-foreground">
            No fue posible cargar el catálogo.
          </CardContent>
        </Card>
      ) : data.products.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center text-sm text-muted-foreground">
            No se encontraron productos.
          </CardContent>
        </Card>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {data.products.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>

          {data.pagination.lastPage > 1 && (
            <div className="mt-8 flex items-center justify-center gap-3">
              <Button
                variant="outline"
                disabled={data.pagination.currentPage <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Anterior
              </Button>
              <span className="text-sm text-muted-foreground">
                Página {data.pagination.currentPage} de {data.pagination.lastPage}
              </span>
              <Button
                variant="outline"
                disabled={data.pagination.currentPage >= data.pagination.lastPage}
                onClick={() => setPage((p) => p + 1)}
              >
                Siguiente
              </Button>
            </div>
          )}
        </>
      )}
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
