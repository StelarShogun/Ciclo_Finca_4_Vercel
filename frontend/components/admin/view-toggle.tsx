"use client";

import { useEffect, useState } from "react";

import { cn } from "@/lib/utils";

export type ViewMode = "table" | "grid";

/**
 * Modo de vista tabla/tarjeta persistido en localStorage por módulo, como el
 * toggle del admin viejo.
 */
export function useViewMode(key: string, initial: ViewMode = "table") {
  const [view, setView] = useState<ViewMode>(initial);

  useEffect(() => {
    const saved = localStorage.getItem(`cf4-view-${key}`);
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (saved === "table" || saved === "grid") setView(saved);
  }, [key]);

  const update = (v: ViewMode) => {
    setView(v);
    localStorage.setItem(`cf4-view-${key}`, v);
  };

  return [view, update] as const;
}

export function ViewToggle({ view, onChange }: { view: ViewMode; onChange: (v: ViewMode) => void }) {
  return (
    <div className="inline-flex overflow-hidden rounded-md border">
      <button
        type="button"
        title="Vista de tabla"
        aria-pressed={view === "table"}
        onClick={() => onChange("table")}
        className={cn(
          "flex h-9 w-9 items-center justify-center transition-colors",
          view === "table" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-muted",
        )}
      >
        <i className="fas fa-table" aria-hidden />
      </button>
      <button
        type="button"
        title="Vista de tarjetas"
        aria-pressed={view === "grid"}
        onClick={() => onChange("grid")}
        className={cn(
          "flex h-9 w-9 items-center justify-center border-l transition-colors",
          view === "grid" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-muted",
        )}
      >
        <i className="fas fa-table-cells-large" aria-hidden />
      </button>
    </div>
  );
}
