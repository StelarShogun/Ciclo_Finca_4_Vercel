"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";

import { getCatalogSearch } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const nf = new Intl.NumberFormat("es-CR");

const PERIOD_SHORT: Record<string, string> = { "7d": "7 días", "30d": "30 días", "90d": "90 días" };
const PERIOD_LONG: Record<string, string> = { "7d": "Últimos 7 días", "30d": "Últimos 30 días", "90d": "Últimos 90 días" };

const RANK_BADGE: Record<number, string> = {
  1: "bg-amber-400 text-amber-950",
  2: "bg-zinc-300 text-zinc-800",
  3: "bg-orange-300 text-orange-900",
};

function Kpi({ label, icon, children }: { label: string; icon: string; children: React.ReactNode }) {
  return (
    <Card>
      <CardContent className="flex items-start justify-between gap-3 p-4">
        <div className="min-w-0">
          <p className="text-sm font-medium text-muted-foreground">{label}</p>
          {children}
        </div>
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent text-[#235347] dark:text-[#8EB69B]">
          <i className={`fas ${icon}`} aria-hidden />
        </span>
      </CardContent>
    </Card>
  );
}

export default function CatalogSearchReport() {
  const [period, setPeriod] = useState("30d");
  const { data, isLoading } = useQuery({
    queryKey: ["report-catalog-search", period],
    queryFn: () => getCatalogSearch(period),
  });

  const periodShort = PERIOD_SHORT[period] ?? "30 días";
  const periodLong = PERIOD_LONG[period] ?? "Últimos 30 días";
  const safeMax = Math.max(1, data?.maxHits ?? 1);

  return (
    <>
      <ReportHeader
        title="Productos más buscados"
        icon="fa-magnifying-glass-chart"
        description="Consulta los productos que aparecen con mayor frecuencia en las búsquedas del catálogo público."
      />

      <div className="mb-4 flex gap-1.5" role="tablist" aria-label="Periodo del reporte">
        {(["7d", "30d", "90d"] as const).map((p) => (
          <Button
            key={p}
            role="tab"
            aria-selected={period === p}
            size="sm"
            variant={period === p ? "default" : "outline"}
            onClick={() => setPeriod(p)}
          >
            {PERIOD_SHORT[p]}
          </Button>
        ))}
      </div>

      {isLoading || !data ? (
        <Skeleton className="h-96" />
      ) : (
        <>
          <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Kpi label="Apariciones totales" icon="fa-chart-line">
              <p className="text-2xl font-bold">{nf.format(data.totalEvents)}</p>
            </Kpi>
            <Kpi label="Productos distintos" icon="fa-box-open">
              <p className="text-2xl font-bold">{nf.format(data.uniqueProducts)}</p>
            </Kpi>
            <Kpi label="Líder" icon="fa-trophy">
              {data.topProductName ? (
                <>
                  <p className="truncate font-semibold" title={data.topProductName}>{data.topProductName}</p>
                  <span className="mt-1 inline-block rounded-full bg-accent px-2 py-0.5 text-xs font-medium text-[#235347] dark:text-[#8EB69B]">
                    {nf.format(data.topProductHits ?? 0)} apariciones
                  </span>
                </>
              ) : (
                <p className="text-2xl font-bold">—</p>
              )}
            </Kpi>
            <Kpi label="Periodo" icon="fa-calendar-days">
              <p className="text-2xl font-bold">{periodShort}</p>
              <p className="text-xs text-muted-foreground">{periodLong}</p>
            </Kpi>
          </div>

          <Card>
            <CardContent className="p-5">
              <div className="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="text-lg font-semibold">Del más al menos buscado</h2>
                <p className="text-sm text-muted-foreground">{periodLong}</p>
              </div>

              {data.rows.length > 0 ? (
                <div className="space-y-3" role="list">
                  {data.rows.map((row, idx) => {
                    const rank = idx + 1;
                    const pct = Math.round((row.hit_count / safeMax) * 100);
                    return (
                      <div key={row.product_id} role="listitem" className="flex items-center gap-3">
                        <span
                          aria-label={`Puesto ${rank}`}
                          className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${RANK_BADGE[rank] ?? "bg-muted text-muted-foreground"}`}
                        >
                          {rank}
                        </span>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-medium">{row.name}</p>
                          <p className="text-xs text-muted-foreground">{row.sku}</p>
                          <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted" aria-hidden>
                            <div
                              className="h-full rounded-full bg-[#235347] dark:bg-[#8EB69B]"
                              style={{ width: `${pct}%` }}
                            />
                          </div>
                        </div>
                        <span className="shrink-0 rounded-full bg-accent px-2.5 py-1 text-xs font-semibold text-[#235347] dark:text-[#8EB69B]">
                          {nf.format(row.hit_count)}
                        </span>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="flex flex-col items-center gap-2 py-10 text-center">
                  <i className="fas fa-magnifying-glass text-2xl text-muted-foreground" aria-hidden />
                  <p className="font-medium">Aún no hay datos</p>
                  <p className="text-sm text-muted-foreground">
                    Cuando los visitantes busquen en el catálogo, aquí aparecerá la lista.
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}
    </>
  );
}
