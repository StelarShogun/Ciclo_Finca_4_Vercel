"use client";

import { ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";

type PaginationControlsProps = {
  currentPage: number;
  lastPage: number;
  total?: number;
  onPageChange: (page: number) => void;
};

/** Paginación de servidor: prev/next + indicador. Controlado por el padre. */
export function PaginationControls({
  currentPage,
  lastPage,
  total,
  onPageChange,
}: PaginationControlsProps) {
  if (lastPage <= 1) return null;

  return (
    <div className="flex items-center justify-between gap-4 pt-4">
      <p className="text-sm text-muted-foreground">
        Página {currentPage} de {lastPage}
        {typeof total === "number" ? ` · ${total} resultados` : ""}
      </p>
      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage <= 1}
        >
          <ChevronLeft className="h-4 w-4" />
          Anterior
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage >= lastPage}
        >
          Siguiente
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}
