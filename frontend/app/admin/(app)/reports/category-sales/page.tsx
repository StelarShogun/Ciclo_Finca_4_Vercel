"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";

import { getCategorySales } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table";

const nf = new Intl.NumberFormat("es-CR");
const crc = (n: number) => `₡${nf.format(Math.round(Number(n) || 0))}`;

const COLORS = ["#4CAF50", "#2196F3", "#FF9800", "#9C27B0", "#F44336", "#00BCD4", "#795548", "#607D8B", "#E91E63", "#009688"];

function Kpi({ title, value, icon }: { title: string; value: string; icon: string }) {
  return (
    <Card>
      <CardContent className="flex items-center justify-between p-4">
        <div>
          <p className="text-sm font-medium text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold">{value}</p>
        </div>
        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-accent text-[#235347] dark:text-[#8EB69B]">
          <i className={`fas ${icon}`} aria-hidden />
        </div>
      </CardContent>
    </Card>
  );
}

export default function CategorySalesReport() {
  const [range, setRange] = useState("month");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const customReady = range !== "custom" || (!!dateFrom && !!dateTo);
  const { data, isLoading } = useQuery({
    queryKey: ["report-category-sales", range, dateFrom, dateTo],
    queryFn: () => getCategorySales(range, dateFrom, dateTo),
    enabled: customReady,
  });

  return (
    <>
      <ReportHeader
        title="Ventas por categoría"
        icon="fa-chart-pie"
        description="Analiza los ingresos, unidades vendidas y participación de cada categoría en el periodo seleccionado."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Select value={range} onValueChange={setRange}>
          <SelectTrigger className="w-44" size="sm"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="today">Hoy</SelectItem>
            <SelectItem value="week">Esta semana</SelectItem>
            <SelectItem value="month">Este mes</SelectItem>
            <SelectItem value="year">Este año</SelectItem>
            <SelectItem value="custom">Personalizado</SelectItem>
          </SelectContent>
        </Select>
        {range === "custom" && (
          <>
            <Input type="date" className="w-40" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} aria-label="Desde" />
            <Input type="date" className="w-40" value={dateTo} onChange={(e) => setDateTo(e.target.value)} aria-label="Hasta" />
          </>
        )}
      </div>

      {!customReady ? (
        <Card><CardContent className="py-8 text-center text-sm text-muted-foreground">Elegí las fechas del rango personalizado.</CardContent></Card>
      ) : isLoading || !data ? (
        <Skeleton className="h-80" />
      ) : data.rows.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-2 py-12 text-sm text-muted-foreground">
            <i className="fas fa-inbox text-2xl" aria-hidden />
            No hay ventas confirmadas en el periodo seleccionado.
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <Kpi title="Ingresos del periodo" value={crc(data.grandTotal)} icon="fa-dollar-sign" />
            <Kpi title="Categorías activas" value={String(data.rows.length)} icon="fa-tags" />
            <Kpi title="Unidades vendidas" value={nf.format(data.totalUnits)} icon="fa-box" />
          </div>

          <div className="grid gap-4 lg:grid-cols-[minmax(0,26rem)_1fr]">
            <Card>
              <CardHeader><CardTitle>Distribución por categoría</CardTitle></CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie data={data.chartData} dataKey="value" nameKey="label" outerRadius={100} strokeWidth={2}>
                      {data.chartData.map((_, i) => (
                        <Cell key={i} fill={COLORS[i % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip
                      formatter={(v, _n, item) =>
                        [`${crc(Number(v))} (${(item?.payload as { percent?: number })?.percent ?? 0}%)`]
                      }
                    />
                    <Legend wrapperStyle={{ fontSize: 12 }} />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Categoría</TableHead>
                      <TableHead className="text-center">Unidades</TableHead>
                      <TableHead className="text-right">Ingresos</TableHead>
                      <TableHead className="text-right">Participación</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data.rows.map((r) => (
                      <TableRow key={r.category_id}>
                        <TableCell className="font-medium">{r.category_name}</TableCell>
                        <TableCell className="text-center">{nf.format(r.total_units)}</TableCell>
                        <TableCell className="text-right">{crc(r.total_revenue)}</TableCell>
                        <TableCell className="text-right">
                          <span className="inline-flex items-center gap-2">
                            <span className="text-sm">{r.percentage}%</span>
                            <span className="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                              <span
                                className="block h-full rounded-full bg-[#235347] dark:bg-[#8EB69B]"
                                style={{ width: `${r.percentage}%` }}
                              />
                            </span>
                          </span>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                  <TableFooter>
                    <TableRow>
                      <TableCell className="font-semibold">Total</TableCell>
                      <TableCell className="text-center font-semibold">{nf.format(data.totalUnits)}</TableCell>
                      <TableCell className="text-right font-semibold">{crc(data.grandTotal)}</TableCell>
                      <TableCell className="text-right font-semibold">100%</TableCell>
                    </TableRow>
                  </TableFooter>
                </Table>
              </CardContent>
            </Card>
          </div>
        </div>
      )}
    </>
  );
}
