"use client";

import { useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, CreditCard, ShoppingCart } from "lucide-react";

import { getSalesPerformance } from "@/lib/api/admin/reports";
import { MetricCard } from "@/components/admin/metric-card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const crc = new Intl.NumberFormat("es-CR", { style: "currency", currency: "CRC", maximumFractionDigits: 0 });

export default function SalesPerformanceReport() {
  const [preset, setPreset] = useState("month");
  const { data, isLoading } = useQuery({
    queryKey: ["report-sales-performance", preset],
    queryFn: () => getSalesPerformance(preset),
  });

  return (
    <>
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button asChild variant="ghost" size="icon"><Link href="/admin/reports"><ArrowLeft className="h-4 w-4" /></Link></Button>
          <div>
            <h1 className="text-2xl font-semibold tracking-tight">Desempeño de ventas</h1>
            <p className="text-sm text-muted-foreground">{data?.current_period.label}</p>
          </div>
        </div>
        <Select value={preset} onValueChange={setPreset}>
          <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="today">Hoy</SelectItem>
            <SelectItem value="week">Esta semana</SelectItem>
            <SelectItem value="month">Este mes</SelectItem>
            <SelectItem value="year">Este año</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {isLoading || !data ? (
        <Skeleton className="h-32" />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <MetricCard label="Ingresos" value={crc.format(data.current_metrics.revenue)} icon={ShoppingCart} trend={data.comparison.revenue_change_percent ?? undefined} />
            <MetricCard label="Ventas confirmadas" value={String(data.current_metrics.sales_count)} icon={CreditCard} trend={data.comparison.sales_count_change_percent ?? undefined} />
          </div>
          <p className="mt-3 text-xs text-muted-foreground">
            Periodo anterior ({data.previous_period.label}): {crc.format(data.previous_metrics.revenue)} · {data.previous_metrics.sales_count} ventas
          </p>
        </>
      )}
    </>
  );
}
