"use client";

import { useState } from "react";
import { ChevronDown } from "lucide-react";

import { cn } from "@/lib/utils";

/** Sección colapsable con título + ícono, replicando el CollapsibleSection del Inertia. */
export function CollapsibleSection({
  title,
  icon,
  description,
  defaultOpen = true,
  children,
}: {
  title: string;
  icon?: string;
  description?: string;
  defaultOpen?: boolean;
  children: React.ReactNode;
}) {
  const [open, setOpen] = useState(defaultOpen);
  return (
    <section className="overflow-hidden rounded-lg border bg-card">
      <button
        type="button"
        aria-expanded={open}
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-accent/50"
      >
        {icon && (
          <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-accent text-[#235347] dark:text-[#8EB69B]">
            <i className={`fas ${icon}`} aria-hidden />
          </span>
        )}
        <span className="flex-1">
          <span className="block text-sm font-semibold">{title}</span>
          {description && <span className="block text-xs text-muted-foreground">{description}</span>}
        </span>
        <ChevronDown className={cn("h-4 w-4 shrink-0 text-muted-foreground transition-transform", open && "rotate-180")} />
      </button>
      {open && <div className="border-t p-4">{children}</div>}
    </section>
  );
}
