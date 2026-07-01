import type { ReactNode } from "react";

import { cn } from "@/lib/utils";

/**
 * Tarjeta genérica para la vista de tarjetas del admin (réplica del
 * `.product-card` viejo): medallón/imagen, título, subtítulo, badge, meta y
 * barra de acciones. Cada módulo la rellena con sus datos.
 */
export function AdminCard({
  media,
  title,
  subtitle,
  badge,
  meta,
  actions,
  corner,
  className,
}: {
  media?: ReactNode;
  title: ReactNode;
  subtitle?: ReactNode;
  badge?: ReactNode;
  meta?: ReactNode;
  actions?: ReactNode;
  corner?: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "relative flex h-full flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md",
        className,
      )}
    >
      {corner}
      <div className="flex items-start gap-3">
        {media}
        <div className="min-w-0 flex-1">
          <h4 className="truncate font-medium leading-tight">{title}</h4>
          {subtitle ? <p className="truncate text-xs text-muted-foreground">{subtitle}</p> : null}
        </div>
        {badge}
      </div>
      {meta ? <div className="space-y-1 text-sm">{meta}</div> : null}
      {actions ? (
        <div className="mt-auto flex items-center justify-end gap-1 border-t pt-3">{actions}</div>
      ) : null}
    </div>
  );
}

/** Miniatura cuadrada con fallback a emoji, como en las tablas del admin. */
export function CardThumb({ src, alt, placeholder = "🚲", size = 56 }: { src?: string | null; alt?: string; placeholder?: string; size?: number }) {
  return (
    <div
      className="shrink-0 overflow-hidden rounded-lg border bg-muted"
      style={{ height: size, width: size }}
    >
      {src ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={src} alt={alt ?? ""} className="h-full w-full object-cover" />
      ) : (
        <div className="flex h-full w-full items-center justify-center text-xl">{placeholder}</div>
      )}
    </div>
  );
}
