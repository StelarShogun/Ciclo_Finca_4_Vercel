"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";

import { getCategorySales } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

export default function CategorySalesReport() {
  const [period, setPeriod] = useState("month");
  const { data, isLoading } = useQuery({ queryKey: ["report-category-sales", period], queryFn: () => getCategorySales(period) });

  return (
    <>
      <ReportHeader
        title="Ventas por categoría"
        description={data ? `Total: ${crc.format(data.grandTotal)} · ${data.totalUnits} unidades` : undefined}
        actions={
          <Select value={period} onValueChange={setPeriod}>
            <SelectTrigger className="w-36" size="sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="today">Hoy</SelectItem>
              <SelectItem value="week">Semana</SelectItem>
              <SelectItem value="month">Mes</SelectItem>
              <SelectItem value="year">Año</SelectItem>
            </SelectContent>
          </Select>
        }
      />
      {isLoading || !data ? (
        <Skeleton className="h-80" />
      ) : data.rows.length === 0 ? (
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">Sin ventas por categoría en el periodo.</CardContent></Card>
      ) : (
        <div className="space-y-4">
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
                <TableHeader><TableRow><TableHead>Categoría</TableHead><TableHead className="text-right">Unidades</TableHead><TableHead className="text-right">Ingresos</TableHead><TableHead className="text-right">%</TableHead></TableRow></TableHeader>
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
        </div>
      )}
    </>
  );
}
