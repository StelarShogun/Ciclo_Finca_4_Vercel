"use client";

import Link from "next/link";
import type { ColumnDef } from "@tanstack/react-table";

import { FeaturedStar } from "@/components/admin/products/featured-star";
import { ProductRowActions } from "@/components/admin/products/product-row-actions";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { type AdminProduct, mediaUrl } from "@/lib/api/admin/products";
import { formatCRC } from "@/lib/money";

export function productStatusTone(status: string): StatusTone {
  return status === "active" ? "success" : "neutral";
}

export function productStatusLabel(status: string): string {
  return status === "active" ? "Activo" : "Inactivo";
}

export function buildProductColumns(
  onEdit: (id: number) => void,
  onView: (id: number) => void,
): ColumnDef<AdminProduct>[] {
  return [
    {
      accessorKey: "name",
      header: "Producto",
      cell: ({ row }) => {
        const img = mediaUrl(row.original.image_url);
        return (
          <div className="flex items-center gap-3">
            <div className="relative h-11 w-11 shrink-0">
              <FeaturedStar productId={row.original.product_id} isFeatured={row.original.is_featured} />
              <div className="h-full w-full overflow-hidden rounded-md border bg-muted">
                {img && !row.original.uses_placeholder ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={img} alt="" className="h-full w-full object-cover" />
                ) : (
                  <div className="flex h-full w-full items-center justify-center text-base">🚲</div>
                )}
              </div>
            </div>
            <div className="flex flex-col">
              <Link href={`/admin/products/${row.original.product_id}`} className="font-medium hover:underline">
                {row.original.name}
              </Link>
              <span className="text-xs text-muted-foreground">{row.original.sku ?? "Sin SKU"}</span>
            </div>
          </div>
        );
      },
    },
    { id: "category", header: "Categoría", cell: ({ row }) => row.original.category?.name ?? "—" },
    { id: "supplier", header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "—" },
    {
      accessorKey: "sale_price",
      header: () => <div className="text-right">Precio</div>,
      cell: ({ row }) => <div className="text-right">{formatCRC(row.original.sale_price)}</div>,
    },
    {
      accessorKey: "stock_current",
      header: () => <div className="text-right">Stock</div>,
      cell: ({ row }) => {
        const low = row.original.stock_current <= row.original.stock_minimum;
        return (
          <div className="text-right">
            <StatusBadge tone={low ? "danger" : "neutral"}>{row.original.stock_current}</StatusBadge>
          </div>
        );
      },
    },
    {
      accessorKey: "status",
      header: "Estado",
      cell: ({ row }) => (
        <StatusBadge tone={productStatusTone(row.original.status)}>
          {productStatusLabel(row.original.status)}
        </StatusBadge>
      ),
    },
    {
      id: "actions",
      header: "Acciones",
      cell: ({ row }) => <ProductRowActions product={row.original} onEdit={onEdit} onView={onView} />,
    },
  ];
}
