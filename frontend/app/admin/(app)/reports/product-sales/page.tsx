"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { Download, Search } from "lucide-react";

import { getProductSales, productSalesExcelUrl, productSalesPdfUrl } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

const money = new Intl.NumberFormat("es-CR", { maximumFractionDigits: 0 });
const crc = (n: number) => `₡${money.format(Math.round(Number(n) || 0))}`;

export default function ProductSalesReport() {
  const [period, setPeriod] = useState("30d");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [sort, setSort] = useState("revenue");
  const [dir, setDir] = useState("desc");
  const [q, setQ] = useState("");
  const [debounced, setDebounced] = useState("");
  const [top10Metric, setTop10Metric] = useState("revenue");
  const [page, setPage] = useState(1);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(q), 400);
    return () => clearTimeout(t);
  }, [q]);

  const params = {
    period,
    sort,
    dir,
    q: debounced,
    top10: top10Metric,
    page,
    ...(period === "custom" ? { date_from: dateFrom, date_to: dateTo } : {}),
  };

  const { data, isLoading } = useQuery({
    queryKey: ["report-product-sales", params],
    queryFn: () => getProductSales(params),
    placeholderData: keepPreviousData,
  });

  function sortBy(column: string) {
    if (sort === column) {
      setDir(dir === "asc" ? "desc" : "asc");
    } else {
      setSort(column);
      setDir("desc");
    }
    setPage(1);
  }

  const sortIcon = (column: string) =>
    sort === column ? <i className={`fas fa-sort-${dir === "asc" ? "up" : "down"} ml-1`} aria-hidden /> : null;

  const top10 = data?.top10 ?? [];
  const rows = data?.rows ?? [];
  const pag = data?.pagination;

  return (
    <>
      <ReportHeader
        title="Productos más vendidos"
        icon="fa-ranking-star"
        description="Analiza los productos con mayor rendimiento por ingresos o unidades vendidas en el periodo seleccionado."
        actions={
          <>
            <Button asChild size="sm" variant="outline" className="border-white/25 bg-white/10 text-white hover:bg-white/20 hover:text-white">
              <a href={productSalesPdfUrl(period, debounced)} target="_blank" rel="noopener noreferrer"><Download className="h-4 w-4" /> PDF</a>
            </Button>
            <Button asChild size="sm" variant="outline" className="border-white/25 bg-white/10 text-white hover:bg-white/20 hover:text-white">
              <a href={productSalesExcelUrl(period, debounced)} target="_blank" rel="noopener noreferrer"><Download className="h-4 w-4" /> Excel</a>
            </Button>
          </>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Select value={period} onValueChange={(v) => { setPeriod(v); setPage(1); }}>
          <SelectTrigger className="w-44" size="sm"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="7d">Últimos 7 días</SelectItem>
            <SelectItem value="30d">Últimos 30 días</SelectItem>
            <SelectItem value="90d">Últimos 90 días</SelectItem>
            <SelectItem value="custom">Por fechas</SelectItem>
          </SelectContent>
        </Select>
        {period === "custom" && (
          <>
            <Input type="date" className="w-40" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1); }} aria-label="Desde" />
            <Input type="date" className="w-40" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1); }} aria-label="Hasta" />
          </>
        )}
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            type="search"
            placeholder="Filtrar por nombre o SKU…"
            className="pl-8"
            value={q}
            onChange={(e) => { setQ(e.target.value); setPage(1); }}
          />
        </div>
      </div>

      {isLoading && !data ? (
        <Skeleton className="h-96" />
      ) : (
        <div className="space-y-6">
          {/* Top 10 */}
          <section>
            <div className="mb-1 flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-lg font-semibold">Top 10</h2>
              <div className="flex gap-1.5" role="group" aria-label="Top 10 por">
                <Button size="sm" variant={top10Metric === "revenue" ? "default" : "outline"} onClick={() => setTop10Metric("revenue")}>Ingresos</Button>
                <Button size="sm" variant={top10Metric === "units" ? "default" : "outline"} onClick={() => setTop10Metric("units")}>Unidades</Button>
              </div>
            </div>
            <p className="mb-2 text-sm text-muted-foreground">
              Top 10 por {top10Metric === "units" ? "unidades" : "ingresos"} en el periodo.
            </p>
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-10">#</TableHead>
                      <TableHead>Producto</TableHead>
                      <TableHead>SKU</TableHead>
                      <TableHead className="text-right">Unidades</TableHead>
                      <TableHead className="text-right">Ingresos</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {top10.length === 0 ? (
                      <TableRow><TableCell colSpan={5} className="py-8 text-center text-sm text-muted-foreground">Sin datos.</TableCell></TableRow>
                    ) : top10.map((r, i) => (
                      <TableRow key={r.product_id}>
                        <TableCell>{i + 1}</TableCell>
                        <TableCell className="font-medium">{r.name}</TableCell>
                        <TableCell><code className="text-xs">{r.sku}</code></TableCell>
                        <TableCell className="text-right">{r.units_sold}</TableCell>
                        <TableCell className="text-right">{crc(r.revenue)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </section>

          {/* Tabla completa */}
          <section>
            <h2 className="mb-2 text-lg font-semibold">Todos los productos con ventas</h2>
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Producto</TableHead>
                      <TableHead>SKU</TableHead>
                      <TableHead className="text-right">
                        <button type="button" className="font-medium hover:underline" onClick={() => sortBy("units")}>
                          Unidades{sortIcon("units")}
                        </button>
                      </TableHead>
                      <TableHead className="text-right">
                        <button type="button" className="font-medium hover:underline" onClick={() => sortBy("revenue")}>
                          Ingresos{sortIcon("revenue")}
                        </button>
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {rows.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">
                          No hay ventas completadas en este periodo para los criterios seleccionados.
                        </TableCell>
                      </TableRow>
                    ) : rows.map((r) => (
                      <TableRow key={r.product_id}>
                        <TableCell className="font-medium">{r.name}</TableCell>
                        <TableCell><code className="text-xs">{r.sku}</code></TableCell>
                        <TableCell className="text-right">{r.units_sold}</TableCell>
                        <TableCell className="text-right">{crc(r.revenue)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
            {pag && pag.last_page > 1 && (
              <div className="mt-3 flex items-center justify-between gap-3">
                <Button size="sm" variant="outline" disabled={pag.page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                  <i className="fas fa-chevron-left" aria-hidden /> Anterior
                </Button>
                <span className="text-sm text-muted-foreground">
                  Página {pag.page} de {pag.last_page} · {pag.total} productos
                </span>
                <Button size="sm" variant="outline" disabled={pag.page >= pag.last_page} onClick={() => setPage((p) => Math.min(pag.last_page, p + 1))}>
                  Siguiente <i className="fas fa-chevron-right" aria-hidden />
                </Button>
              </div>
            )}
          </section>
        </div>
      )}
    </>
  );
}
