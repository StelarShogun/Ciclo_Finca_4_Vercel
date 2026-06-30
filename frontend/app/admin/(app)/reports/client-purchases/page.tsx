"use client";

import { useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";

import { getClientPurchases } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

export default function ClientPurchasesReport() {
  const [period, setPeriod] = useState("30d");
  const { data, isLoading } = useQuery({
    queryKey: ["report-client-purchases", period],
    queryFn: () => getClientPurchases(period),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <ReportHeader
        title="Compras por cliente"
        description="Totales, órdenes y ticket promedio por cliente."
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
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader><TableRow><TableHead>Cliente</TableHead><TableHead>Correo</TableHead><TableHead className="text-right">Órdenes</TableHead><TableHead className="text-right">Total</TableHead><TableHead className="text-right">Ticket prom.</TableHead></TableRow></TableHeader>
              <TableBody>
                {data.rows.length === 0 ? (
                  <TableRow><TableCell colSpan={5} className="py-8 text-center text-sm text-muted-foreground">Sin compras en el periodo.</TableCell></TableRow>
                ) : data.rows.map((r) => (
                  <TableRow key={r.client_id}>
                    <TableCell className="font-medium">{r.display_name}</TableCell>
                    <TableCell className="text-muted-foreground">{r.gmail}</TableCell>
                    <TableCell className="text-right">{r.orders_count}</TableCell>
                    <TableCell className="text-right">{crc.format(r.total_purchased)}</TableCell>
                    <TableCell className="text-right">{crc.format(r.avg_ticket)}</TableCell>
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
