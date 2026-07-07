"use client";

import { useEffect, useMemo, useState } from "react";
import { keepPreviousData, useQuery, useQueryClient } from "@tanstack/react-query";

import { getProductFormOptions, getProducts } from "@/lib/api/admin/products";
import { useDebouncedValue } from "@/lib/debounce";
import { queryKeys } from "@/lib/query-keys";
import { useViewMode } from "@/components/admin/view-toggle";

export const ALL_PRODUCTS_FILTER = "all";

export function useAdminProductsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);
  const [status, setStatus] = useState(ALL_PRODUCTS_FILTER);
  const [stockStatus, setStockStatus] = useState(ALL_PRODUCTS_FILTER);
  const [formOpen, setFormOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [viewId, setViewId] = useState<number | null>(null);
  const [view, setView] = useViewMode("products");

  useEffect(() => {
    void queryClient.prefetchQuery({
      queryKey: queryKeys.productFormOptions,
      queryFn: getProductFormOptions,
      staleTime: 5 * 60_000,
    });
  }, [queryClient]);

  useEffect(() => {
    setPage(1);
  }, [debouncedSearch, status, stockStatus]);

  const filters = useMemo(
    () => ({
      search: debouncedSearch,
      status: status === ALL_PRODUCTS_FILTER ? "" : status,
      stock_status: stockStatus === ALL_PRODUCTS_FILTER ? "" : stockStatus,
    }),
    [debouncedSearch, status, stockStatus],
  );

  const productsQuery = useQuery({
    queryKey: queryKeys.adminProducts(page, filters),
    queryFn: () => getProducts({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  return {
    page,
    setPage,
    search,
    setSearch,
    status,
    setStatus,
    stockStatus,
    setStockStatus,
    formOpen,
    setFormOpen,
    editId,
    viewId,
    setViewId,
    view,
    setView,
    productsQuery,
    openCreate: () => {
      setEditId(null);
      setFormOpen(true);
    },
    openEdit: (id: number) => {
      setEditId(id);
      setFormOpen(true);
    },
    openView: (id: number) => setViewId(id),
  };
}
