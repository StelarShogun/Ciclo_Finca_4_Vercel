"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Plus, Search } from "lucide-react";

import { getProducts, mediaUrl, type AdminProduct } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { ProductRowActions } from "@/components/admin/products/product-row-actions";
import { ProductFormDialog } from "@/components/admin/products/product-form-dialog";
import { ViewProductModal } from "@/components/admin/products/view-product-modal";
import { FeaturedStar } from "@/components/admin/products/featured-star";
import { AdminCard } from "@/components/admin/admin-card";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
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

function buildColumns(onEdit: (id: number) => void, onView: (id: number) => void): ColumnDef<AdminProduct>[] {
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
    id: "actions",
    header: "Acciones",
    cell: ({ row }) => <ProductRowActions product={row.original} onEdit={onEdit} onView={onView} />,
  },
  ];
}

/** Tarjeta de producto para la vista de tarjetas. */
function ProductCard({
  product,
  onEdit,
  onView,
}: {
  product: AdminProduct;
  onEdit: (id: number) => void;
  onView: (id: number) => void;
}) {
  const img = mediaUrl(product.image_url);
  const low = product.stock_current <= product.stock_minimum;
  return (
    <AdminCard
      media={
        <div className="relative h-14 w-14 shrink-0">
          <FeaturedStar productId={product.product_id} isFeatured={product.is_featured} />
          <div className="h-full w-full overflow-hidden rounded-lg border bg-muted">
            {img && !product.uses_placeholder ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={img} alt="" className="h-full w-full object-cover" />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-xl">🚲</div>
            )}
          </div>
        </div>
      }
      title={product.name}
      subtitle={product.sku ?? "Sin SKU"}
      badge={
        <StatusBadge tone={statusTone(product.status)}>
          {product.status === "active" ? "Activo" : "Inactivo"}
        </StatusBadge>
      }
      meta={
        <>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Categoría</span>
            <span>{product.category?.name ?? "—"}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Precio</span>
            <span className="font-medium">{crc.format(Number(product.sale_price))}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Stock</span>
            <StatusBadge tone={low ? "danger" : "neutral"}>{product.stock_current}</StatusBadge>
          </div>
        </>
      }
      actions={<ProductRowActions product={product} onEdit={onEdit} onView={onView} />}
    />
  );
}

export default function ProductsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [status, setStatus] = useState(ALL);
  const [stockStatus, setStockStatus] = useState(ALL);
  const [formOpen, setFormOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [viewId, setViewId] = useState<number | null>(null);
  const [view, setView] = useViewMode("products");

  const openCreate = () => { setEditId(null); setFormOpen(true); };
  const openEdit = (id: number) => { setEditId(id); setFormOpen(true); };
  const openView = (id: number) => setViewId(id);
  const columns = useMemo(() => buildColumns(openEdit, openView), []);

  // Debounce de la búsqueda para no pegar a la API en cada tecla.
  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  // Cualquier cambio de filtro vuelve a la página 1.
  // eslint-disable-next-line react-hooks/set-state-in-effect
  useEffect(() => setPage(1), [debouncedSearch, status, stockStatus]);

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
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nuevo producto
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
        <div className="ml-auto">
          <ViewToggle view={view} onChange={setView} />
        </div>
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
            view={view}
            rowKey={(p) => p.product_id}
            renderCard={(p) => <ProductCard product={p} onEdit={openEdit} onView={openView} />}
          />
          <PaginationControls
            currentPage={data.current_page}
            lastPage={data.last_page}
            total={data.total}
            onPageChange={setPage}
          />
        </>
      )}

      <ProductFormDialog open={formOpen} productId={editId} onClose={() => setFormOpen(false)} />
      <ViewProductModal productId={viewId} open={viewId !== null} onClose={() => setViewId(null)} onEdit={openEdit} />
    </>
  );
}
