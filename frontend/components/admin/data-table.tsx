"use client";

import type { ReactNode } from "react";
import {
  type ColumnDef,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import type { ViewMode } from "./view-toggle";
import { EmptyState } from "./empty-state";

type DataTableProps<TData, TValue> = {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  emptyTitle?: string;
  emptyDescription?: string;
  /** Modo de vista. En "grid" se usa `renderCard` en vez de la tabla. */
  view?: ViewMode;
  /** Render de tarjeta por fila para el modo grid. */
  renderCard?: (row: TData) => ReactNode;
  /** Clave estable por fila para el modo grid. */
  rowKey?: (row: TData) => string | number;
};

/**
 * Tabla genérica sobre TanStack Table + shadcn Table. La paginación es del
 * servidor: el padre pasa la página de datos y usa <PaginationControls>.
 */
export function DataTable<TData, TValue>({
  columns,
  data,
  emptyTitle = "Sin datos",
  emptyDescription,
  view = "table",
  renderCard,
  rowKey,
}: DataTableProps<TData, TValue>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  if (data.length === 0) {
    return <EmptyState title={emptyTitle} description={emptyDescription} />;
  }

  if (view === "grid" && renderCard) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {data.map((row, i) => (
          <div key={rowKey ? rowKey(row) : i}>{renderCard(row)}</div>
        ))}
      </div>
    );
  }

  return (
    <div className="rounded-lg border">
      <Table>
        <TableHeader>
          {table.getHeaderGroups().map((group) => (
            <TableRow key={group.id}>
              {group.headers.map((header) => (
                <TableHead key={header.id}>
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {table.getRowModel().rows.map((row) => (
            <TableRow key={row.id}>
              {row.getVisibleCells().map((cell) => (
                <TableCell key={cell.id}>
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </TableCell>
              ))}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
