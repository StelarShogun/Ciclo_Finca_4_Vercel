"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { Search } from "lucide-react";

import { getInventoryMovements } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

export default function MovementsReport() {
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const { data, isLoading } = useQuery({
    queryKey: ["report-movements", debounced],
    queryFn: () => getInventoryMovements(debounced),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <ReportHeader title="Movimientos de inventario" description="Selecciona un producto para ver su historial de entradas y salidas." />
      <div className="mb-4 max-w-xs">
        <div className="relative">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Buscar producto o SKU…" className="pl-8" value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
      </div>
      {isLoading || !data ? (
        <Skeleton className="h-72" />
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader><TableRow><TableHead>Producto</TableHead><TableHead>SKU</TableHead><TableHead>Categoría</TableHead><TableHead className="text-right">Stock</TableHead></TableRow></TableHeader>
              <TableBody>
                {data.products.length === 0 ? (
                  <TableRow><TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">Sin productos.</TableCell></TableRow>
                ) : data.products.map((m) => (
                  <TableRow key={m.product.product_id}>
                    <TableCell className="font-medium">{m.product.name}</TableCell>
                    <TableCell className="text-muted-foreground">{m.product.sku}</TableCell>
                    <TableCell>{m.product.category_name}</TableCell>
                    <TableCell className="text-right">{m.product.stock_current}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
      <p className="mt-3 text-xs text-muted-foreground">El detalle de movimientos por producto está disponible en Inventario → Ajustar → Movimientos.</p>
    </>
  );
}
