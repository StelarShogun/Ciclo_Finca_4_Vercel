"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";

import { getCatalogSearch } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { MetricCard } from "@/components/admin/metric-card";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Search, Tag } from "lucide-react";

export default function CatalogSearchReport() {
  const [period, setPeriod] = useState("30d");
  const { data, isLoading } = useQuery({ queryKey: ["report-catalog-search", period], queryFn: () => getCatalogSearch(period) });

  return (
    <>
      <ReportHeader
        title="Productos más buscados"
        description="Ranking de apariciones en búsquedas del catálogo."
        actions={
          <Select value={period} onValueChange={setPeriod}>
            <SelectTrigger className="w-40" size="sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="7d">Últimos 7 días</SelectItem>
              <SelectItem value="30d">Últimos 30 días</SelectItem>
              <SelectItem value="90d">Últimos 90 días</SelectItem>
            </SelectContent>
          </Select>
        }
      />
      {isLoading || !data ? (
        <Skeleton className="h-72" />
      ) : (
        <>
          <div className="mb-4 grid gap-4 sm:grid-cols-2">
            <MetricCard label="Eventos de búsqueda" value={String(data.totalEvents)} icon={Search} />
            <MetricCard label="Productos distintos" value={String(data.uniqueProducts)} icon={Tag} />
          </div>
          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader><TableRow><TableHead className="w-12">#</TableHead><TableHead>Producto</TableHead><TableHead>SKU</TableHead><TableHead className="text-right">Apariciones</TableHead></TableRow></TableHeader>
                <TableBody>
                  {data.rows.length === 0 ? (
                    <TableRow><TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">Sin búsquedas en el periodo.</TableCell></TableRow>
                  ) : data.rows.map((r, i) => (
                    <TableRow key={r.product_id}>
                      <TableCell className="text-muted-foreground">{i + 1}</TableCell>
                      <TableCell className="font-medium">{r.name}</TableCell>
                      <TableCell className="text-muted-foreground">{r.sku}</TableCell>
                      <TableCell className="text-right">{r.hit_count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </>
      )}
    </>
  );
}
