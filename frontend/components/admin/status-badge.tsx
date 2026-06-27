import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

export type StatusTone = "success" | "warning" | "danger" | "info" | "neutral";

const TONE_CLASS: Record<StatusTone, string> = {
  success: "border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  warning: "border-transparent bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  danger: "border-transparent bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  info: "border-transparent bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  neutral: "border-transparent bg-muted text-muted-foreground",
};

export function StatusBadge({
  tone = "neutral",
  children,
  className,
}: {
  tone?: StatusTone;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <Badge variant="outline" className={cn(TONE_CLASS[tone], className)}>
      {children}
    </Badge>
  );
}
