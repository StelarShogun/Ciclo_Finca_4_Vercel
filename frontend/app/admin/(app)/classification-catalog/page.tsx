"use client";

import { useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";

import { getCatalog, type CatalogSubcategory } from "@/lib/api/admin/classification-catalog";
import { PageHeader } from "@/components/admin/page-header";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge } from "@/components/admin/status-badge";
import { CatalogManager } from "@/components/admin/classification-catalog/catalog-manager";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

export default function ClassificationCatalogPage() {
  const [page, setPage] = useState(1);
  const [managing, setManaging] = useState<CatalogSubcategory | null>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-classification-catalog", page],
    queryFn: () => getCatalog(page),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        title="Opciones por tipo"
        description="Atributos (ej. Color, Talla) y sus valores por tipo de producto."
      />

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar el catálogo.
          </CardContent>
        </Card>
      ) : (
        <>
          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tipo de producto</TableHead>
                    <TableHead>Categoría padre</TableHead>
                    <TableHead className="text-center">Atributos</TableHead>
                    <TableHead className="w-32 text-right">Gestionar</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.subcategories.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">
                        No hay subcategorías concretas. Creá una en Categorías.
                      </TableCell>
                    </TableRow>
                  ) : (
                    data.subcategories.map((s) => (
                      <TableRow key={s.category_id}>
                        <TableCell className="font-medium">{s.name}</TableCell>
                        <TableCell className="text-muted-foreground">{s.parent_name ?? "—"}</TableCell>
                        <TableCell className="text-center">
                          <StatusBadge tone={s.dimensions_count > 0 ? "success" : "neutral"}>
                            {s.dimensions_count}
                          </StatusBadge>
                        </TableCell>
                        <TableCell className="text-right">
                          <Button size="sm" variant="outline" onClick={() => setManaging(s)}>
                            Gestionar
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
          <PaginationControls
            currentPage={data.pagination.currentPage}
            lastPage={data.pagination.lastPage}
            total={data.pagination.total}
            onPageChange={setPage}
          />
        </>
      )}

      <CatalogManager
        category={managing}
        open={managing !== null}
        onClose={() => setManaging(null)}
      />
    </>
  );
}
