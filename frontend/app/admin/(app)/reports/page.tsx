"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { CreditCard, Download, ShoppingCart } from "lucide-react";

import {
  getCategorySales,
  getProductSales,
  getSalesPerformance,
  productSalesExcelUrl,
  productSalesPdfUrl,
} from "@/lib/api/admin/reports";
import { PageHeader } from "@/components/admin/page-header";
import { MetricCard } from "@/components/admin/metric-card";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function PeriodSelect({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className="w-40" size="sm"><SelectValue /></SelectTrigger>
      <SelectContent>
        <SelectItem value="today">Hoy</SelectItem>
        <SelectItem value="week">Esta semana</SelectItem>
        <SelectItem value="month">Este mes</SelectItem>
        <SelectItem value="year">Este año</SelectItem>
      </SelectContent>
    </Select>
  );
}

function SalesPerformanceTab() {
  const [preset, setPreset] = useState("month");
  const { data, isLoading } = useQuery({
    queryKey: ["report-sales-performance", preset],
    queryFn: () => getSalesPerformance(preset),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{data?.current_period.label}</p>
        <PeriodSelect value={preset} onChange={setPreset} />
      </div>
      {isLoading || !data ? (
        <Skeleton className="h-32" />
      ) : (
        <div className="grid gap-4 sm:grid-cols-2">
          <MetricCard
            label="Ingresos"
            value={crc.format(data.current_metrics.revenue)}
            icon={ShoppingCart}
            trend={data.comparison.revenue_change_percent ?? undefined}
          />
          <MetricCard
            label="Ventas confirmadas"
            value={String(data.current_metrics.sales_count)}
            icon={CreditCard}
            trend={data.comparison.sales_count_change_percent ?? undefined}
          />
        </div>
      )}
      {data && (
        <p className="text-xs text-muted-foreground">
          Periodo anterior ({data.previous_period.label}): {crc.format(data.previous_metrics.revenue)} ·{" "}
          {data.previous_metrics.sales_count} ventas
        </p>
      )}
    </div>
  );
}

function ProductSalesTab() {
  const [period, setPeriod] = useState("month");
  const { data, isLoading } = useQuery({
    queryKey: ["report-product-sales", period],
    queryFn: () => getProductSales({ period }),
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <PeriodSelect value={period} onChange={setPeriod} />
        <div className="flex gap-2">
          <Button asChild size="sm" variant="outline">
            <a href={productSalesPdfUrl(period)} target="_blank" rel="noopener noreferrer">
              <Download className="h-4 w-4" /> PDF
            </a>
          </Button>
          <Button asChild size="sm" variant="outline">
            <a href={productSalesExcelUrl(period)} target="_blank" rel="noopener noreferrer">
              <Download className="h-4 w-4" /> Excel
            </a>
          </Button>
        </div>
      </div>
      {isLoading || !data ? (
        <Skeleton className="h-72" />
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Producto</TableHead>
                  <TableHead>SKU</TableHead>
                  <TableHead className="text-right">Unidades</TableHead>
                  <TableHead className="text-right">Ingresos</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">
                      Sin ventas en el periodo.
                    </TableCell>
                  </TableRow>
                ) : (
                  data.rows.map((r) => (
                    <TableRow key={r.product_id}>
                      <TableCell className="font-medium">{r.name}</TableCell>
                      <TableCell className="text-muted-foreground">{r.sku}</TableCell>
                      <TableCell className="text-right">{r.units_sold}</TableCell>
                      <TableCell className="text-right">{crc.format(r.revenue)}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function CategorySalesTab() {
  const [period, setPeriod] = useState("month");
  const { data, isLoading } = useQuery({
    queryKey: ["report-category-sales", period],
    queryFn: () => getCategorySales(period),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          {data ? `Total: ${crc.format(data.grandTotal)} · ${data.totalUnits} unidades` : ""}
        </p>
        <PeriodSelect value={period} onChange={setPeriod} />
      </div>
      {isLoading || !data ? (
        <Skeleton className="h-80" />
      ) : data.rows.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            Sin ventas por categoría en el periodo.
          </CardContent>
        </Card>
      ) : (
        <>
          <Card>
            <CardHeader><CardTitle>Ingresos por categoría</CardTitle></CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={data.chartData}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                  <YAxis tick={{ fontSize: 12 }} tickFormatter={(v) => crc.format(Number(v))} width={80} />
                  <Tooltip formatter={(v) => crc.format(Number(v))} />
                  <Bar dataKey="value" fill="#235347" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Categoría</TableHead>
                    <TableHead className="text-right">Unidades</TableHead>
                    <TableHead className="text-right">Ingresos</TableHead>
                    <TableHead className="text-right">%</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.rows.map((r) => (
                    <TableRow key={r.category_id}>
                      <TableCell className="font-medium">{r.category_name}</TableCell>
                      <TableCell className="text-right">{r.total_units}</TableCell>
                      <TableCell className="text-right">{crc.format(r.total_revenue)}</TableCell>
                      <TableCell className="text-right">{r.percentage}%</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}

export default function ReportsPage() {
  return (
    <>
      <PageHeader title="Reportes" description="Desempeño de ventas, productos y categorías." />
      <Tabs defaultValue="performance">
        <TabsList>
          <TabsTrigger value="performance">Desempeño</TabsTrigger>
          <TabsTrigger value="products">Productos vendidos</TabsTrigger>
          <TabsTrigger value="categories">Por categoría</TabsTrigger>
        </TabsList>
        <TabsContent value="performance" className="mt-4">
          <SalesPerformanceTab />
        </TabsContent>
        <TabsContent value="products" className="mt-4">
          <ProductSalesTab />
        </TabsContent>
        <TabsContent value="categories" className="mt-4">
          <CategorySalesTab />
        </TabsContent>
      </Tabs>
    </>
  );
}
