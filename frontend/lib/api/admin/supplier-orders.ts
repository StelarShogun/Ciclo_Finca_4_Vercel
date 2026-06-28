import { api } from "@/lib/api/client";

export type SupplierOrderRow = {
  num_order: number;
  po_short: string;
  po_full: string;
  supplier_id: number | null;
  supplier_name: string | null;
  date_label: string;
  edd_label: string;
  delivered_label: string | null;
  state: string;
  state_label: string;
  initial_total: number;
  received_total: number;
  shorts_total: number;
  has_received_data: boolean;
  has_shorts: boolean;
};

export type SupplierOption = {
  supplier_id: number;
  name: string;
  primary_contact: string | null;
  email: string | null;
  phone: string | null;
};

export type SupplierPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type SupplierOrdersIndex = {
  orders: SupplierOrderRow[];
  pagination: SupplierPagination;
  openSupplierOrdersCount: number;
  suppliers: SupplierOption[];
  filters: Record<string, string>;
};

export type SupplierOrderItem = {
  id: number;
  name: string;
  quantity: number;
  received_quantity: number | null;
  unit_price: number;
  total: number;
};

export type SupplierOrderTimeline = {
  state: string;
  state_label: string;
  changed_at: string;
  user_name: string;
  reason: string | null;
};

export type SupplierOrderDetail = {
  num_order: number;
  po_number: string;
  supplier_name: string;
  date_label: string;
  estimated_delivery_date: string | null;
  delivered_at: string | null;
  received_at: string | null;
  state: string;
  state_label: string;
  closed_with_shorts: boolean;
  total: number;
  has_received_data: boolean;
  has_shorts: boolean;
  initial_total: number;
  received_total: number | null;
  shorts_total: number;
  items: SupplierOrderItem[];
  timeline: SupplierOrderTimeline[];
};

export type SupplierProduct = {
  product_id: number;
  name: string;
  sku: string;
  unit_price: number;
};

export type SupplierOrdersListParams = {
  page?: number;
  state?: string;
  date_range?: string;
  search?: string;
};

export async function getSupplierOrders(
  params: SupplierOrdersListParams,
): Promise<SupplierOrdersIndex> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/supplier-orders", { params: clean });
  return data.data as SupplierOrdersIndex;
}

export async function getSupplierOrder(id: number): Promise<SupplierOrderDetail> {
  const { data } = await api.get(`/api/v1/admin/supplier-orders/${id}`);
  return data.data as SupplierOrderDetail;
}

export async function searchSupplierProducts(
  supplierId: number,
  q: string,
): Promise<SupplierProduct[]> {
  const { data } = await api.get("/api/v1/admin/supplier-orders/search-products", {
    params: { supplier_id: supplierId, q },
  });
  return data.products as SupplierProduct[];
}

export async function createSupplierOrder(
  supplierId: number,
  items: { product_id: number; quantity: number }[],
) {
  const { data } = await api.post("/api/v1/admin/supplier-orders", {
    supplier_id: supplierId,
    items,
  });
  return data;
}

export async function updateSupplierOrderState(id: number, state: string, reason?: string) {
  const { data } = await api.post(`/api/v1/admin/supplier-orders/${id}/state`, {
    state,
    ...(reason ? { reason } : {}),
  });
  return data;
}

export async function closePartialSupplierOrder(id: number, reason: string) {
  const { data } = await api.post(`/api/v1/admin/supplier-orders/${id}/close-partial`, { reason });
  return data;
}

export async function receiveSupplierOrder(
  id: number,
  items: { order_item_id: number; received_quantity: number }[],
) {
  const { data } = await api.post(`/api/v1/admin/supplier-orders/${id}/receive`, { items });
  return data;
}
