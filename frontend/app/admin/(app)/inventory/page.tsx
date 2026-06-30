"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Boxes, PackageX, Search, TriangleAlert } from "lucide-react";

import { getInventory, type InventoryProduct } from "@/lib/api/admin/inventory";
import { mediaUrl } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { StockAdjust } from "@/components/admin/inventory/stock-adjust";
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

function statusClassToTone(cls: string): StatusTone {
  if (cls === "success") return "success";
  if (cls === "warning") return "warning";
  if (cls === "danger") return "danger";
  return "neutral";
}

export default function InventoryPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [stockStatus, setStockStatus] = useState(ALL);
  const [adjust, setAdjust] = useState<InventoryProduct | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const filters = { search: debounced, stock_status: stockStatus === ALL ? "" : stockStatus };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-inventory", page, filters],
    queryFn: () => getInventory({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const columns: ColumnDef<InventoryProduct>[] = [
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
      cell: ({ row }) => <div className="text-right">{crc.format(Number(row.original.price))}</div>,
    },
    {
      id: "availability",
      header: "Disponibilidad",
      cell: ({ row }) => (
        <StatusBadge tone={statusClassToTone(row.original.status_class)}>
          {row.original.availability_label}
        </StatusBadge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <Button size="sm" variant="outline" onClick={() => setAdjust(row.original)}>
          Ajustar
        </Button>
      ),
    },
  ];

  return (
    <>
      <PageHeader title="Inventario" description="Stock y ajustes manuales con movimientos." />

      {data && (
        <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <MetricCard label="Productos" value={String(data.inventorySummary.total)} icon={Boxes} />
          <MetricCard label="Activos" value={String(data.inventorySummary.active)} icon={Boxes} />
          <MetricCard label="Stock bajo" value={String(data.inventorySummary.lowStock)} icon={TriangleAlert} />
          <MetricCard label="Agotados" value={String(data.inventorySummary.outOfStock)} icon={PackageX} />
        </div>
      )}

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre o SKU…"
            className="pl-8"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select value={stockStatus} onValueChange={(v) => { setStockStatus(v); setPage(1); }}>
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
            No fue posible cargar el inventario.
          </CardContent>
        </Card>
      ) : (
        <>
          <DataTable columns={columns} data={data.products} emptyTitle="Sin productos" />
          <PaginationControls
            currentPage={data.pagination.currentPage}
            lastPage={data.pagination.lastPage}
            total={data.pagination.total}
            onPageChange={setPage}
          />
        </>
      )}

      <StockAdjust product={adjust} open={adjust !== null} onClose={() => setAdjust(null)} />
    </>
  );
}
