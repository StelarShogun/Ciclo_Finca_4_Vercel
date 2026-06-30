"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Download } from "lucide-react";

import { getProductSales, productSalesExcelUrl, productSalesPdfUrl } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

export default function ProductSalesReport() {
  const [period, setPeriod] = useState("month");
  const { data, isLoading } = useQuery({ queryKey: ["report-product-sales", period], queryFn: () => getProductSales({ period }) });

  return (
    <>
      <ReportHeader
        title="Productos más vendidos"
        description="Unidades e ingresos por producto."
        actions={
          <>
            <Select value={period} onValueChange={setPeriod}>
              <SelectTrigger className="w-36" size="sm"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="today">Hoy</SelectItem>
                <SelectItem value="week">Semana</SelectItem>
                <SelectItem value="month">Mes</SelectItem>
                <SelectItem value="year">Año</SelectItem>
              </SelectContent>
            </Select>
            <Button asChild size="sm" variant="outline"><a href={productSalesPdfUrl(period)} target="_blank" rel="noopener noreferrer"><Download className="h-4 w-4" /> PDF</a></Button>
            <Button asChild size="sm" variant="outline"><a href={productSalesExcelUrl(period)} target="_blank" rel="noopener noreferrer"><Download className="h-4 w-4" /> Excel</a></Button>
          </>
        }
      />
      {isLoading || !data ? (
        <Skeleton className="h-72" />
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader><TableRow><TableHead>Producto</TableHead><TableHead>SKU</TableHead><TableHead className="text-right">Unidades</TableHead><TableHead className="text-right">Ingresos</TableHead></TableRow></TableHeader>
              <TableBody>
                {data.rows.length === 0 ? (
                  <TableRow><TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">Sin ventas en el periodo.</TableCell></TableRow>
                ) : data.rows.map((r) => (
                  <TableRow key={r.product_id}>
                    <TableCell className="font-medium">{r.name}</TableCell>
                    <TableCell className="text-muted-foreground">{r.sku}</TableCell>
                    <TableCell className="text-right">{r.units_sold}</TableCell>
                    <TableCell className="text-right">{crc.format(r.revenue)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </>
  );
}
