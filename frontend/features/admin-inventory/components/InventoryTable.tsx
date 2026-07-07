"use client";

import { useMemo } from "react";

import { AdminCard, CardThumb } from "@/components/admin/admin-card";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge } from "@/components/admin/status-badge";
import type { ViewMode } from "@/components/admin/view-toggle";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { InventoryIndex, InventoryProduct } from "@/lib/api/admin/inventory";
import { mediaUrl } from "@/lib/api/admin/products";
import { formatCRC } from "@/lib/money";
import { buildInventoryColumns, InventoryActions, inventoryStatusTone } from "../inventory-columns";

export function InventoryTable({
  data,
  isLoading,
  isError,
  view,
  onPageChange,
  onAdjust,
}: {
  data: InventoryIndex | undefined;
  isLoading: boolean;
  isError: boolean;
  view: ViewMode;
  onPageChange: (page: number) => void;
  onAdjust: (product: InventoryProduct) => void;
}) {
  const columns = useMemo(() => buildInventoryColumns(onAdjust), [onAdjust]);

  if (isLoading) return <Skeleton className="h-96" />;
  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-8 text-center text-sm text-muted-foreground">
          No fue posible cargar el inventario.
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <DataTable
        columns={columns}
        data={data.products}
        emptyTitle="Sin productos"
        view={view}
        rowKey={(product) => product.product_id}
        renderCard={(product) => (
          <AdminCard
            media={<CardThumb src={product.uses_placeholder ? null : mediaUrl(product.image_url)} alt={product.name} />}
            title={product.name}
            subtitle={product.sku}
            badge={<StatusBadge tone={inventoryStatusTone(product.status_class)}>{product.availability_label}</StatusBadge>}
            meta={
              <>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Categoría</span>
                  <span>{product.category_name}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Stock</span>
                  <span>
                    {product.stock} <span className="text-xs text-muted-foreground">/ mín {product.stock_minimum}</span>
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Precio</span>
                  <span className="font-medium">{formatCRC(product.price)}</span>
                </div>
              </>
            }
            actions={<InventoryActions product={product} onAdjust={onAdjust} />}
          />
        )}
      />
      <PaginationControls
        currentPage={data.pagination.currentPage}
        lastPage={data.pagination.lastPage}
        total={data.pagination.total}
        perPage={data.pagination.perPage}
        onPageChange={onPageChange}
      />
    </>
  );
}
