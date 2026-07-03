"use client";

import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts";

import type { SalesByDay } from "@/lib/api/admin/dashboard";
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  type ChartConfig,
} from "@/components/ui/chart";
import { EmptyState } from "@/components/admin/empty-state";

const config = {
  total: { label: "Ventas", color: "var(--chart-1)" },
} satisfies ChartConfig;

const colones = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

export function SalesChart({ data }: { data: SalesByDay[] }) {
  if (data.length === 0) {
    return <EmptyState title="Sin ventas en el período" />;
  }

  return (
    <ChartContainer config={config} className="h-64 w-full">
      <BarChart accessibilityLayer data={data}>
        <CartesianGrid vertical={false} />
        <XAxis dataKey="date" tickLine={false} axisLine={false} tickMargin={8} />
        <YAxis
          tickLine={false}
          axisLine={false}
          width={70}
          tickFormatter={(v) => colones.format(Number(v))}
        />
        <ChartTooltip
          content={
            <ChartTooltipContent
              formatter={(value) => colones.format(Number(value))}
            />
          }
        />
        <Bar dataKey="total" fill="var(--color-total)" radius={6} />
      </BarChart>
    </ChartContainer>
  );
}
