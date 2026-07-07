"use client";

import { useMemo } from "react";

import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { ViewMode } from "@/components/admin/view-toggle";
import type { AdminProduct, Paginated } from "@/lib/api/admin/products";
import { buildProductColumns } from "../product-columns";
import { ProductCard } from "./ProductCard";

type ProductTableProps = {
  data: Paginated<AdminProduct> | undefined;
  isLoading: boolean;
  isError: boolean;
  view: ViewMode;
  onPageChange: (page: number) => void;
  onEdit: (id: number) => void;
  onView: (id: number) => void;
};

export function ProductTable({ data, isLoading, isError, view, onPageChange, onEdit, onView }: ProductTableProps) {
  const columns = useMemo(() => buildProductColumns(onEdit, onView), [onEdit, onView]);

  if (isLoading) return <Skeleton className="h-96" />;
  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-8 text-center text-sm text-muted-foreground">
          No fue posible cargar los productos.
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <DataTable
        columns={columns}
        data={data.data}
        emptyTitle="Sin productos"
        view={view}
        rowKey={(product) => product.product_id}
        renderCard={(product) => <ProductCard product={product} onEdit={onEdit} onView={onView} />}
      />
      <PaginationControls
        currentPage={data.current_page}
        lastPage={data.last_page}
        total={data.total}
        perPage={data.per_page}
        onPageChange={onPageChange}
      />
    </>
  );
}
