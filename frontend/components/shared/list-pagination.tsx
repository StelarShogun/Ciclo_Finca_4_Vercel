"use client";

import { useId, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ELLIPSIS, clampPage, pageWindow } from "@/lib/pagination";

export type ListPaginationData = {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage?: number;
  from?: number | null;
  to?: number | null;
};

type ListPaginationProps = {
  pagination: ListPaginationData;
  onPageChange: (page: number) => void;
  label?: string;
};

/**
 * Paginación fiel a la vieja InertiaListPagination: info de resultados,
 * prev/next con chevrons, números con ellipsis y salto "Ir a página".
 */
export function ListPagination({ pagination, onPageChange, label = "" }: ListPaginationProps) {
  const inputId = useId();
  // null = mostrar la página actual; string = el usuario está escribiendo.
  const [draft, setDraft] = useState<string | null>(null);

  if (pagination.lastPage <= 1) return null;

  const { currentPage, lastPage, total, perPage } = pagination;
  const target = draft ?? String(currentPage);
  const from = pagination.from ?? (perPage ? (currentPage - 1) * perPage + 1 : null);
  const to = pagination.to ?? (perPage ? Math.min(currentPage * perPage, total) : null);

  function goTo(page: number) {
    const clamped = Math.max(1, Math.min(lastPage, page));
    setDraft(null);
    if (clamped !== currentPage) onPageChange(clamped);
  }

  function commitGo() {
    goTo(clampPage(target, currentPage, lastPage));
  }

  return (
    <nav
      className="flex flex-wrap items-center justify-between gap-3 pt-4"
      aria-label={label ? `Paginación ${label}` : "Paginación"}
      data-last-page={lastPage}
    >
      <p className="text-sm text-muted-foreground" aria-live="polite">
        {total === 0
          ? "Mostrando 0 resultados"
          : from != null && to != null
            ? `Mostrando ${from}–${to} de ${total} resultados`
            : `${total} resultados`}
      </p>

      <div className="flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon-sm"
            aria-label="Anterior"
            disabled={currentPage <= 1}
            onClick={() => goTo(currentPage - 1)}
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>

          {pageWindow(currentPage, lastPage).map((item, i) =>
            item === ELLIPSIS ? (
              <span
                key={`e-${i}`}
                aria-hidden="true"
                className="px-1.5 text-sm text-muted-foreground"
              >
                {ELLIPSIS}
              </span>
            ) : item === currentPage ? (
              <Button key={item} size="sm" aria-current="page" className="pointer-events-none min-w-8">
                {item}
              </Button>
            ) : (
              <Button
                key={item}
                variant="outline"
                size="sm"
                className="min-w-8"
                onClick={() => goTo(item)}
              >
                {item}
              </Button>
            ),
          )}

          <Button
            variant="outline"
            size="icon-sm"
            aria-label="Siguiente"
            disabled={currentPage >= lastPage}
            onClick={() => goTo(currentPage + 1)}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>

        <div className="flex items-center gap-1.5">
          <label className="sr-only" htmlFor={inputId}>
            Ir a página
          </label>
          <Input
            id={inputId}
            type="number"
            min={1}
            max={lastPage}
            step={1}
            inputMode="numeric"
            className="h-8 w-16 text-center"
            value={target}
            onChange={(e) => setDraft(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                e.preventDefault();
                commitGo();
              }
            }}
          />
          <Button variant="outline" size="sm" onClick={commitGo}>
            Ir
          </Button>
        </div>
      </div>
    </nav>
  );
}
