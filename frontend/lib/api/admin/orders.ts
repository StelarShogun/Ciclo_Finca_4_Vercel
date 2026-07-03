import { api } from "@/lib/api/client";

export type OrderRow = {
  sale_id: number;
  reference: string;
  customer: string;
  customer_email: string | null;
  order_placed_label: string;
  status: string;
  status_label: string;
  total: number;
};

export type OrdersIndex = {
  orders: OrderRow[];
  pagination: { currentPage: number; lastPage: number; total: number };
  pendingWebOrdersCount: number;
  filters: Record<string, string>;
};

export type OrdersListParams = { page?: number; status?: string; date_range?: string; search?: string };

export async function getOrders(params: OrdersListParams): Promise<OrdersIndex> {
  const clean = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== "" && v != null));
  const { data } = await api.get("/api/v1/admin/orders", { params: clean });
  return data.data as OrdersIndex;
}
