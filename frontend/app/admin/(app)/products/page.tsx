"use client";

import { useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Star } from "lucide-react";

import { getProducts, type AdminProduct } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function statusTone(status: string): StatusTone {
  return status === "active" ? "success" : "neutral";
}

const columns: ColumnDef<AdminProduct>[] = [
  {
    accessorKey: "name",
    header: "Producto",
    cell: ({ row }) => (
      <div className="flex flex-col">
        <span className="font-medium">{row.original.name}</span>
        <span className="text-xs text-muted-foreground">
          {row.original.sku ?? "Sin SKU"}
        </span>
      </div>
    ),
  },
  {
    id: "category",
    header: "Categoría",
    cell: ({ row }) => row.original.category?.name ?? "—",
  },
  {
    id: "supplier",
    header: "Proveedor",
    cell: ({ row }) => row.original.supplier?.name ?? "—",
  },
  {
    accessorKey: "sale_price",
    header: () => <div className="text-right">Precio</div>,
    cell: ({ row }) => (
      <div className="text-right">{crc.format(Number(row.original.sale_price))}</div>
    ),
  },
  {
    accessorKey: "stock_current",
    header: () => <div className="text-right">Stock</div>,
    cell: ({ row }) => {
      const low = row.original.stock_current <= row.original.stock_minimum;
      return (
        <div className="text-right">
          <StatusBadge tone={low ? "danger" : "neutral"}>
            {row.original.stock_current}
          </StatusBadge>
        </div>
      );
    },
  },
  {
    accessorKey: "status",
    header: "Estado",
    cell: ({ row }) => (
      <StatusBadge tone={statusTone(row.original.status)}>
        {row.original.status === "active" ? "Activo" : "Inactivo"}
      </StatusBadge>
    ),
  },
  {
    accessorKey: "is_featured",
    header: "Destacado",
    cell: ({ row }) =>
      row.original.is_featured ? (
        <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
      ) : (
        <span className="text-muted-foreground">—</span>
      ),
  },
];

export default function ProductsPage() {
  const [page, setPage] = useState(1);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-products", page],
    queryFn: () => getProducts({ page }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        title="Productos"
        description="Catálogo del inventario. Crear/editar y galería llegan en el próximo slice."
      />

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar los productos.
          </CardContent>
        </Card>
      ) : (
        <>
          <DataTable
            columns={columns}
            data={data.data}
            emptyTitle="Sin productos"
          />
          <PaginationControls
            currentPage={data.current_page}
            lastPage={data.last_page}
            total={data.total}
            onPageChange={setPage}
          />
        </>
      )}
    </>
  );
}
