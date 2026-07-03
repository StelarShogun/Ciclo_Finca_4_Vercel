"use client";

import { cn } from "@/lib/utils";

export type ActionTone = "view" | "edit" | "stock" | "activate" | "delete" | "feature";

const TONES: Record<ActionTone, string> = {
  view: "text-sky-600 hover:bg-sky-100 dark:text-sky-400 dark:hover:bg-sky-950",
  edit: "text-brand-medium hover:bg-brand-lightest dark:text-brand-light dark:hover:bg-brand-dark",
  stock: "text-amber-600 hover:bg-amber-100 dark:text-amber-400 dark:hover:bg-amber-950",
  activate: "text-emerald-600 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-950",
  delete: "text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-950",
  feature: "text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-950",
};

/**
 * Botón de acción inline con ícono FA y tooltip, réplica del `.action-btn` del
 * admin viejo (uno por acción, no un menú de tres puntos).
 */
export function ActionBtn({
  icon,
  label,
  tone = "view",
  onClick,
  disabled,
  className,
}: {
  icon: string; // clase FA, ej "fa-eye"
  label: string;
  tone?: ActionTone;
  onClick?: () => void;
  disabled?: boolean;
  className?: string;
}) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      disabled={disabled}
      onClick={onClick}
      className={cn(
        "inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent transition-colors disabled:pointer-events-none disabled:opacity-40",
        TONES[tone],
        className,
      )}
    >
      <i className={cn("fas", icon)} aria-hidden />
    </button>
  );
}

/** Contenedor flex para agrupar varios ActionBtn en una celda/tarjeta. */
export function ActionBar({ children }: { children: React.ReactNode }) {
  return <div className="flex items-center gap-1">{children}</div>;
}
