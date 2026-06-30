"use client";

import { useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Boxes, Layers, Package, ShoppingCart, Truck, TriangleAlert } from "lucide-react";

import {
  getDashboard,
  SALES_RANGES,
  type DashboardData,
  type RecentSale,
  type SalesRange,
} from "@/lib/api/admin/dashboard";
import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { DataTable } from "@/components/admin/data-table";
import { StatusBadge } from "@/components/admin/status-badge";
import { SalesChart } from "@/components/admin/dashboard/sales-chart";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

const recentColumns: ColumnDef<RecentSale>[] = [
  { accessorKey: "invoice", header: "Factura" },
  { accessorKey: "client", header: "Cliente" },
  {
    accessorKey: "total",
    header: () => <div className="text-right">Total</div>,
    cell: ({ row }) => <div className="text-right">{crc.format(row.original.total)}</div>,
  },
  {
    accessorKey: "dateShort",
    header: "Fecha",
    cell: ({ row }) => <span title={row.original.dateFull}>{row.original.dateShort}</span>,
  },
  {
    accessorKey: "statusShort",
    header: "Estado",
    cell: ({ row }) => (
      <StatusBadge tone="neutral">
        <span title={row.original.statusTitle}>{row.original.statusShort}</span>
      </StatusBadge>
    ),
  },
];

function Kpis({ d }: { d: DashboardData }) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <MetricCard label="Productos" value={d.totalProducts.toLocaleString("es-CR")} icon={Package} />
      <MetricCard label="Ventas hoy" value={crc.format(d.todaySales)} icon={ShoppingCart} trend={d.salesTrend} />
      <MetricCard label="Proveedores" value={d.totalSuppliers.toLocaleString("es-CR")} icon={Truck} />
      <MetricCard label="Categorías" value={d.totalCategories.toLocaleString("es-CR")} icon={Layers} />
      <MetricCard label="Stock bajo" value={d.lowStockProducts.toLocaleString("es-CR")} icon={TriangleAlert} />
      <MetricCard label="Ventas del mes" value={crc.format(d.monthlySales)} icon={Boxes} trend={d.monthlyTrend} />
    </div>
  );
}

export default function DashboardPage() {
  const [range, setRange] = useState<SalesRange>("last7");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");

  // Para 'custom' sólo consultamos cuando hay ambas fechas (vía applied).
  const [applied, setApplied] = useState<{ from: string; to: string } | null>(null);

  const params =
    range === "custom"
      ? applied
        ? { range: "custom", from: applied.from, to: applied.to }
        : { range: "last7" }
      : { range };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["dashboard", params],
    queryFn: () => getDashboard(params),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader
        title="Dashboard"
        description="Resumen de ventas, inventario y actividad reciente."
      />

      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          {Array.from({ length: 6 }).map((_, i) => <Skeleton key={i} className="h-28" />)}
        </div>
      ) : isError || !data ? (
        <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">No fue posible cargar el dashboard.</CardContent></Card>
      ) : (
        <div className="flex flex-col gap-6">
          <Kpis d={data} />

          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
              <CardTitle>Ventas</CardTitle>
              <div className="flex flex-wrap items-center gap-2">
                <Select value={range} onValueChange={(v) => setRange(v as SalesRange)}>
                  <SelectTrigger className="w-44" size="sm">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {SALES_RANGES.map((r) => (
                      <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {range === "custom" && (
                  <div className="flex items-center gap-2">
                    <Input type="date" value={from} max={to || undefined} onChange={(e) => setFrom(e.target.value)} className="w-40" />
                    <span className="text-muted-foreground">–</span>
                    <Input type="date" value={to} min={from || undefined} onChange={(e) => setTo(e.target.value)} className="w-40" />
                    <Button size="sm" disabled={!from || !to} onClick={() => setApplied({ from, to })}>Aplicar</Button>
                  </div>
                )}
              </div>
            </CardHeader>
            <CardContent>
              <SalesChart data={data.salesByDay} />
            </CardContent>
          </Card>

          <div className="grid gap-6 lg:grid-cols-2">
            {/* Productos por categoría */}
            <Card>
              <CardHeader><CardTitle>Productos por categoría</CardTitle></CardHeader>
              <CardContent>
                {data.productsByCategory.length === 0 ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">Sin datos.</p>
                ) : (
                  <ResponsiveContainer width="100%" height={260}>
                    <BarChart data={data.productsByCategory} layout="vertical" margin={{ left: 8 }}>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                      <XAxis type="number" tick={{ fontSize: 12 }} allowDecimals={false} />
                      <YAxis type="category" dataKey="label" tick={{ fontSize: 12 }} width={110} />
                      <Tooltip />
                      <Bar dataKey="total" fill="#235347" radius={[0, 4, 4, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                )}
              </CardContent>
            </Card>

            {/* Stock bajo */}
            <Card>
              <CardHeader><CardTitle>Stock bajo</CardTitle></CardHeader>
              <CardContent className="p-0">
                {data.lowStockList.length === 0 ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">Sin productos con stock bajo.</p>
                ) : (
                  <ul className="divide-y">
                    {data.lowStockList.map((p) => (
                      <li key={p.id} className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                        <div className="min-w-0">
                          <p className="truncate font-medium">{p.name}</p>
                          <p className="text-xs text-muted-foreground">{p.category || "—"}</p>
                        </div>
                        <StatusBadge tone="danger">{p.stock}</StatusBadge>
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            {/* Ventas recientes */}
            <Card>
              <CardHeader><CardTitle>Ventas recientes</CardTitle></CardHeader>
              <CardContent>
                <DataTable columns={recentColumns} data={data.recentSales} emptyTitle="Sin ventas recientes" />
              </CardContent>
            </Card>

            {/* Top productos */}
            <Card>
              <CardHeader><CardTitle>Productos más vendidos</CardTitle></CardHeader>
              <CardContent className="p-0">
                {data.topProducts.length === 0 ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">Sin ventas todavía.</p>
                ) : (
                  <ul className="divide-y">
                    {data.topProducts.map((p, i) => (
                      <li key={i} className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                        <span className="truncate font-medium">{p.name}</span>
                        <span className="shrink-0 text-muted-foreground">{p.units} u · {crc.format(p.revenue)}</span>
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      )}
    </>
  );
}
