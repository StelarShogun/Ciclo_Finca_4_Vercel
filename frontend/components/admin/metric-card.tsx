import { ArrowDown, ArrowUp, Minus, type LucideIcon } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";

type MetricCardProps = {
  label: string;
  value: string;
  icon?: LucideIcon;
  /** Variación porcentual; signo decide color e ícono. */
  trend?: number;
};

export function MetricCard({ label, value, icon: Icon, trend }: MetricCardProps) {
  const TrendIcon = trend === undefined ? null : trend > 0 ? ArrowUp : trend < 0 ? ArrowDown : Minus;
  const trendColor =
    trend === undefined
      ? ""
      : trend > 0
        ? "text-emerald-600"
        : trend < 0
          ? "text-red-600"
          : "text-muted-foreground";

  return (
    <Card className="border-l-4 border-l-primary/70">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {label}
        </CardTitle>
        {Icon && (
          <span className="flex h-8 w-8 items-center justify-center rounded-md bg-accent text-accent-foreground">
            <Icon className="h-4 w-4" />
          </span>
        )}
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-semibold">{value}</div>
        {TrendIcon && (
          <p className={cn("mt-1 flex items-center gap-1 text-xs", trendColor)}>
            <TrendIcon className="h-3 w-3" />
            {Math.abs(trend as number)}%
          </p>
        )}
      </CardContent>
    </Card>
  );
}
