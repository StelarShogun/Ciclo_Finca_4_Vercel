"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";

import { getSalesPerformance } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";

const money = new Intl.NumberFormat("es-CR", { maximumFractionDigits: 0 });
const crc = (n: number) => `₡${money.format(Math.round(Number(n) || 0))}`;

const PRESETS = [
  { value: "today", label: "Hoy" },
  { value: "week", label: "Esta semana" },
  { value: "month", label: "Este mes" },
  { value: "year", label: "Este año" },
  { value: "custom", label: "Personalizado" },
];

function MetricBlock({
  icon,
  title,
  value,
  hint,
  primary,
}: {
  icon: string;
  title: string;
  value: string;
  hint: string;
  primary?: boolean;
}) {
  return (
    <Card className={primary ? "border-brand-medium/40 bg-brand-medium/5 dark:bg-brand-light/10" : undefined}>
      <CardContent className="p-4">
        <div className="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-accent text-brand-medium dark:text-brand-light">
          <i className={`fas ${icon}`} aria-hidden />
        </div>
        <p className="text-sm font-medium text-muted-foreground">{title}</p>
        <p className="text-2xl font-bold">{value}</p>
        <p className="text-xs text-muted-foreground">{hint}</p>
      </CardContent>
    </Card>
  );
}

export default function SalesPerformanceReport() {
  const [preset, setPreset] = useState("month");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [applied, setApplied] = useState<{ from: string; to: string } | null>(null);

  const customReady = preset !== "custom" || !!applied;
  const { data, isLoading, isError } = useQuery({
    queryKey: ["report-sales-performance", preset, applied],
    queryFn: () => getSalesPerformance(preset, applied?.from, applied?.to),
    enabled: customReady,
  });

  const cur = data?.current_metrics;
  const prev = data?.previous_metrics;
  const empty = !!(data && cur && cur.sales_count === 0 && cur.revenue === 0);
  const revenueDiff = cur && prev ? cur.revenue - prev.revenue : 0;
  const countDiff = cur && prev ? cur.sales_count - prev.sales_count : 0;

  return (
    <>
      <ReportHeader
        title="Desempeño de ventas"
        icon="fa-chart-line"
        description="Analiza las ventas completadas y los ingresos facturados del periodo seleccionado, comparándolos con el periodo anterior equivalente."
      />

      <div className="grid gap-4 lg:grid-cols-[16rem_1fr]">
        {/* Filtros de periodo */}
        <Card className="h-fit">
          <CardContent className="p-4">
            <p className="mb-2 text-sm font-semibold">Periodo</p>
            <div className="flex flex-col gap-1.5" role="group" aria-label="Opciones de periodo">
              {PRESETS.map((p) => (
                <Button
                  key={p.value}
                  variant={preset === p.value ? "default" : "outline"}
                  size="sm"
                  className="justify-start"
                  onClick={() => setPreset(p.value)}
                >
                  {p.label}
                </Button>
              ))}
            </div>
            {preset === "custom" && (
              <div className="mt-4 space-y-3">
                <div className="space-y-1.5">
                  <Label htmlFor="perf-from">Desde</Label>
                  <Input id="perf-from" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="perf-to">Hasta</Label>
                  <Input id="perf-to" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                </div>
                <Button
                  className="w-full"
                  disabled={!from || !to}
                  onClick={() => setApplied({ from, to })}
                >
                  Aplicar rango
                </Button>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Resultados */}
        <div aria-live="polite">
          {!customReady ? (
            <Card>
              <CardContent className="py-8 text-center text-sm text-muted-foreground">
                Elegí el rango de fechas y presioná «Aplicar rango».
              </CardContent>
            </Card>
          ) : isLoading ? (
            <Skeleton className="h-72" />
          ) : isError || !data ? (
            <Card>
              <CardContent className="py-8 text-center text-sm text-muted-foreground">
                No se pudieron cargar los datos.
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-4">
              {empty && (
                <Card>
                  <CardContent className="flex items-center gap-3 py-4 text-sm">
                    <i className="fas fa-inbox text-muted-foreground" aria-hidden />
                    <p>
                      <strong>No hay ventas completadas</strong> en el periodo elegido. Probá otro
                      rango o revisá más adelante.
                    </p>
                  </CardContent>
                </Card>
              )}

              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <h3 className="text-sm font-semibold">Periodo elegido</h3>
                  <p className="mb-2 text-xs text-muted-foreground">{data.current_period?.label}</p>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <MetricBlock icon="fa-receipt" title="Ventas" value={String(cur?.sales_count ?? "—")} hint="Órdenes completadas" />
                    <MetricBlock icon="fa-coins" title="Ingresos" value={cur ? crc(cur.revenue) : "—"} hint="Total facturado" primary />
                  </div>
                </div>
                <div>
                  <h3 className="text-sm font-semibold">
                    Periodo anterior{" "}
                    <span className="font-normal text-muted-foreground">(misma duración, para comparar)</span>
                  </h3>
                  <p className="mb-2 text-xs text-muted-foreground">{data.previous_period?.label}</p>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <MetricBlock icon="fa-receipt" title="Ventas" value={String(prev?.sales_count ?? "—")} hint="Órdenes completadas" />
                    <MetricBlock icon="fa-coins" title="Ingresos" value={prev ? crc(prev.revenue) : "—"} hint="Total facturado" />
                  </div>
                </div>
              </div>

              <Card>
                <CardContent className="p-4">
                  <h2 className="mb-3 text-sm font-semibold text-muted-foreground">
                    Diferencia real respecto al periodo anterior
                  </h2>
                  <ul className="divide-y text-sm">
                    <li className="flex justify-between py-2">
                      <span>Ingresos (actual − anterior)</span>
                      <span className={`font-semibold ${revenueDiff >= 0 ? "text-cta-strong dark:text-[#2ED27E]" : "text-[#d32f2f] dark:text-[#F87171]"}`}>
                        {`${revenueDiff >= 0 ? "+" : "−"}${crc(Math.abs(revenueDiff))}`}
                      </span>
                    </li>
                    <li className="flex justify-between py-2">
                      <span>Ventas (actual − anterior)</span>
                      <span className={`font-semibold ${countDiff >= 0 ? "text-cta-strong dark:text-[#2ED27E]" : "text-[#d32f2f] dark:text-[#F87171]"}`}>
                        {`${countDiff >= 0 ? "+" : "−"}${Math.abs(countDiff)}`}
                      </span>
                    </li>
                  </ul>
                </CardContent>
              </Card>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
