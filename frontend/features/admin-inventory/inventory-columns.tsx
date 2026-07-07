"use client";

import type { ColumnDef } from "@tanstack/react-table";

import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import type { InventoryProduct } from "@/lib/api/admin/inventory";
import { mediaUrl } from "@/lib/api/admin/products";
import { formatCRC } from "@/lib/money";

export function inventoryStatusTone(statusClass: string): StatusTone {
  if (statusClass === "success") return "success";
  if (statusClass === "warning") return "warning";
  if (statusClass === "danger") return "danger";
  return "neutral";
}

export function InventoryActions({
  product,
  onAdjust,
}: {
  product: InventoryProduct;
  onAdjust: (product: InventoryProduct) => void;
}) {
  return (
    <ActionBar>
      <ActionBtn icon="fa-plus" label="Agregar stock" tone="activate" onClick={() => onAdjust(product)} />
      <ActionBtn icon="fa-sliders" label="Ajustar stock" tone="stock" onClick={() => onAdjust(product)} />
    </ActionBar>
  );
}

export function buildInventoryColumns(onAdjust: (product: InventoryProduct) => void): ColumnDef<InventoryProduct>[] {
  return [
    {
      accessorKey: "name",
      header: "Producto",
      cell: ({ row }) => {
        const img = mediaUrl(row.original.image_url);
        return (
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 shrink-0 overflow-hidden rounded-md border bg-muted">
              {img && !row.original.uses_placeholder ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={img} alt="" className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-base">🚲</div>
              )}
            </div>
            <div className="flex flex-col">
              <span className="font-medium">{row.original.name}</span>
              <span className="text-xs text-muted-foreground">{row.original.sku}</span>
            </div>
          </div>
        );
      },
    },
    { id: "category", header: "Categoría", cell: ({ row }) => row.original.category_name },
    {
      accessorKey: "stock",
      header: () => <div className="text-right">Stock</div>,
      cell: ({ row }) => (
        <div className="text-right">
          {row.original.stock} <span className="text-xs text-muted-foreground">/ mín {row.original.stock_minimum}</span>
        </div>
      ),
    },
    {
      accessorKey: "price",
      header: () => <div className="text-right">Precio</div>,
      cell: ({ row }) => <div className="text-right">{formatCRC(row.original.price)}</div>,
    },
    {
      id: "availability",
      header: "Disponibilidad",
      cell: ({ row }) => (
        <StatusBadge tone={inventoryStatusTone(row.original.status_class)}>{row.original.availability_label}</StatusBadge>
      ),
    },
    { id: "actions", header: "Acciones", cell: ({ row }) => <InventoryActions product={row.original} onAdjust={onAdjust} /> },
  ];
}
