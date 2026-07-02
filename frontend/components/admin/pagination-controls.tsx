"use client";

import { ListPagination } from "@/components/shared/list-pagination";

type PaginationControlsProps = {
  currentPage: number;
  lastPage: number;
  total?: number;
  perPage?: number;
  onPageChange: (page: number) => void;
};

/** Adaptador sobre ListPagination para los call sites admin existentes. */
export function PaginationControls({
  currentPage,
  lastPage,
  total,
  perPage,
  onPageChange,
}: PaginationControlsProps) {
  return (
    <ListPagination
      pagination={{ currentPage, lastPage, total: total ?? 0, perPage }}
      onPageChange={onPageChange}
    />
  );
}
