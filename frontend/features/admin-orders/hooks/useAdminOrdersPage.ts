"use client";

import { useMemo, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

import { getOrders } from "@/lib/api/admin/orders";
import { cancelSale, completeSale, getSale, markSaleReady, type SaleDetail } from "@/lib/api/admin/sales";
import { useDebouncedValue } from "@/lib/debounce";
import { apiErrorMessage } from "@/lib/errors";
import { queryKeys } from "@/lib/query-keys";
import { useViewMode } from "@/components/admin/view-toggle";

export const ALL_ORDER_FILTER = "all";

export function useAdminOrdersPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebouncedValue(search);
  const [status, setStatus] = useState(ALL_ORDER_FILTER);
  const [detail, setDetail] = useState<SaleDetail | null>(null);
  const [cancelId, setCancelId] = useState<number | null>(null);
  const [reason, setReason] = useState("");
  const [view, setView] = useViewMode("orders");

  const filters = useMemo(
    () => ({ status: status === ALL_ORDER_FILTER ? "" : status, search: debouncedSearch }),
    [debouncedSearch, status],
  );

  const ordersQuery = useQuery({
    queryKey: queryKeys.adminOrders(page, filters),
    queryFn: () => getOrders({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const refresh = () => queryClient.invalidateQueries({ queryKey: ["admin-orders"] });
  const resetToFirstPage = () => setPage(1);

  const ready = useMutation({
    mutationFn: (id: number) => markSaleReady(id),
    onSuccess: () => {
      toast.success("Marcado listo");
      refresh();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo.")),
  });
  const complete = useMutation({
    mutationFn: (id: number) => completeSale(id),
    onSuccess: () => {
      toast.success("Encargo confirmado");
      refresh();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo.")),
  });
  const cancel = useMutation({
    mutationFn: () => cancelSale(cancelId as number, reason.trim()),
    onSuccess: () => {
      toast.success("Encargo rechazado");
      setCancelId(null);
      setReason("");
      refresh();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo rechazar.")),
  });
  const openDetail = useMutation({
    mutationFn: (id: number) => getSale(id),
    onSuccess: setDetail,
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo cargar.")),
  });

  return {
    page,
    setPage,
    search,
    setSearch: (value: string) => {
      setSearch(value);
      resetToFirstPage();
    },
    status,
    setStatus: (value: string) => {
      setStatus(value);
      resetToFirstPage();
    },
    detail,
    setDetail,
    cancelId,
    setCancelId,
    reason,
    setReason,
    view,
    setView,
    ordersQuery,
    ready,
    complete,
    cancel,
    openDetail,
  };
}
