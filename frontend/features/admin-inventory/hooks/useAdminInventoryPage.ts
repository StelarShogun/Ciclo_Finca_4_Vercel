"use client";

import { useMemo, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";

import { getInventory, type InventoryProduct } from "@/lib/api/admin/inventory";
import { useDebouncedValue } from "@/lib/debounce";
import { queryKeys } from "@/lib/query-keys";
import { useViewMode } from "@/components/admin/view-toggle";

export const ALL_INVENTORY_FILTER = "all";

export function useAdminInventoryPage() {
  const [page, setPage] = useState(1);
  const [search, setSearchRaw] = useState("");
  const debouncedSearch = useDebouncedValue(search);
  const [stockStatus, setStockStatusRaw] = useState(ALL_INVENTORY_FILTER);
  const [adjust, setAdjust] = useState<InventoryProduct | null>(null);
  const [importOpen, setImportOpen] = useState(false);
  const [view, setView] = useViewMode("inventory");

  const filters = useMemo(
    () => ({ search: debouncedSearch, stock_status: stockStatus === ALL_INVENTORY_FILTER ? "" : stockStatus }),
    [debouncedSearch, stockStatus],
  );

  const inventoryQuery = useQuery({
    queryKey: queryKeys.adminInventory(page, filters),
    queryFn: () => getInventory({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  return {
    page,
    setPage,
    search,
    setSearch: (value: string) => {
      setSearchRaw(value);
      setPage(1);
    },
    stockStatus,
    setStockStatus: (value: string) => {
      setStockStatusRaw(value);
      setPage(1);
    },
    adjust,
    setAdjust,
    importOpen,
    setImportOpen,
    view,
    setView,
    inventoryQuery,
  };
}
