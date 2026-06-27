"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Pencil, Plus, Search, Star } from "lucide-react";

import { getProducts, type AdminProduct } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const ALL = "all";

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
        <Link
          href={`/admin/products/${row.original.product_id}`}
          className="font-medium hover:underline"
        >
          {row.original.name}
        </Link>
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
  {
    id: "actions",
    header: "",
    cell: ({ row }) => (
      <Button asChild variant="ghost" size="sm">
        <Link href={`/admin/products/${row.original.product_id}/edit`}>
          <Pencil className="h-4 w-4" />
          Editar
        </Link>
      </Button>
    ),
  },
];

export default function ProductsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [status, setStatus] = useState(ALL);
  const [stockStatus, setStockStatus] = useState(ALL);

  // Debounce de la búsqueda para no pegar a la API en cada tecla.
  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  // Cualquier cambio de filtro vuelve a la página 1.
  useEffect(() => {
    setPage(1);
  }, [debouncedSearch, status, stockStatus]);

  const filters = {
    search: debouncedSearch,
    status: status === ALL ? "" : status,
    stock_status: stockStatus === ALL ? "" : stockStatus,
  };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-products", page, filters],
    queryFn: () => getProducts({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        title="Productos"
        description="Catálogo del inventario."
        actions={
          <Button asChild>
            <Link href="/admin/products/new">
              <Plus className="h-4 w-4" />
              Nuevo producto
            </Link>
          </Button>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre o descripción…"
            className="pl-8"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <Select value={status} onValueChange={setStatus}>
          <SelectTrigger className="w-44" size="sm"><SelectValue placeholder="Estado" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos los estados</SelectItem>
            <SelectItem value="active">Activo</SelectItem>
            <SelectItem value="inactive">Inactivo</SelectItem>
            <SelectItem value="out_of_stock">Sin stock</SelectItem>
            <SelectItem value="discontinued">Descontinuado</SelectItem>
          </SelectContent>
        </Select>
        <Select value={stockStatus} onValueChange={setStockStatus}>
          <SelectTrigger className="w-44" size="sm"><SelectValue placeholder="Stock" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todo el stock</SelectItem>
            <SelectItem value="in-stock">En stock</SelectItem>
            <SelectItem value="low">Stock bajo</SelectItem>
            <SelectItem value="out">Sin stock</SelectItem>
          </SelectContent>
        </Select>
      </div>

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
