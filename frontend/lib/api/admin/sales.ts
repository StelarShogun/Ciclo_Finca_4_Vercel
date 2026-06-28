import { api } from "@/lib/api/client";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

export type SaleRow = {
  sale_id: number;
  invoice_number: string;
  customer: string;
  customer_email: string | null;
  sale_date_label: string;
  status: string;
  status_label: string;
  payment_method: string;
  payment_label: string;
  total: number;
};

export type SalesPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type SalesKpis = {
  dailySales: number;
  dailySalesTrend: number;
  dailyTransactions: number;
  dailyTransactionsTrend: number;
  refunds: number;
  refundsTrend: number;
};

export type SalesIndex = {
  sales: SaleRow[];
  pagination: SalesPagination;
  kpis: SalesKpis;
  salesStatusUi: string;
  latestHistorySaleId: number;
  filters: Record<string, string>;
};

export type SaleItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: string;
  total: string;
  product: { product_id: number; name: string; sku: string } | null;
};

export type SaleDetail = {
  sale_id: number;
  invoice_number: string | null;
  sale_date: string;
  sale_date_label: string;
  status: string;
  payment_method: string;
  payment_reference: string | null;
  subtotal: string;
  iva: string;
  discount: string;
  total: string;
  notes: string | null;
  order_source: string | null;
  ready_at_label: string | null;
  confirmed_at_label: string | null;
  pickup_time_remaining_label: string | null;
  is_pickup_expired: boolean;
  buyer: { name: string | null; email: string | null };
  client: {
    user_id: number;
    name: string;
    first_surname: string;
    second_surname: string | null;
    gmail: string;
  } | null;
  sale_items: SaleItem[];
};

export type SalesListParams = {
  page?: number;
  status?: string;
  date_range?: string;
  payment_method?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
};

export async function getSales(params: SalesListParams): Promise<SalesIndex> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/sales", { params: clean });
  return data.data as SalesIndex;
}

export type SalesHeartbeat = {
  hasNew: boolean;
  newCount: number;
  latestSaleId: number;
  pendingCount: number;
  dailySales: number;
  dailyTransactions: number;
};

export async function getSalesHeartbeat(since: number): Promise<SalesHeartbeat> {
  const { data } = await api.get("/api/v1/admin/sales/heartbeat", { params: { since } });
  return data as SalesHeartbeat;
}

export async function getSale(id: number): Promise<SaleDetail> {
  const { data } = await api.get(`/api/v1/admin/sales/${id}`);
  return data.sale as SaleDetail;
}

export async function markSaleReady(id: number) {
  const { data } = await api.post(`/api/v1/admin/sales/${id}/ready`);
  return data;
}

export async function completeSale(id: number) {
  const { data } = await api.post(`/api/v1/admin/sales/${id}/complete`);
  return data;
}

export async function cancelSale(id: number, reason: string) {
  const { data } = await api.post(`/api/v1/admin/sales/${id}/cancel`, { reason });
  return data;
}

export async function returnSale(id: number, reason: string) {
  const { data } = await api.post(`/api/v1/admin/sales/${id}/return`, { reason });
  return data;
}

export async function deletePendingSale(id: number, reason: string) {
  const { data } = await api.delete(`/api/v1/admin/sales/${id}`, { data: { reason } });
  return data;
}

/** Factura/impresión: HTML servido por el backend (Blade). Se abren en pestaña nueva con la cookie de sesión. */
export function invoiceUrl(id: number): string {
  return `${API_URL}/sales/${id}/invoice`;
}

export function printUrl(id: number): string {
  return `${API_URL}/sales/${id}/print`;
}
