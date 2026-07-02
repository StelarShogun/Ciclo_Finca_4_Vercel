import { api } from "@/lib/api/client";

export type InventoryProduct = {
  product_id: number;
  name: string;
  sku: string;
  image_url: string | null;
  uses_placeholder: boolean;
  category_name: string;
  stock: number;
  stock_minimum: number;
  availability_label: string;
  price: string;
  status: string;
  status_label: string;
  status_class: string;
};

export type InventoryPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type InventorySummary = {
  total: number;
  active: number;
  lowStock: number;
  outOfStock: number;
};

export type InventoryIndex = {
  products: InventoryProduct[];
  pagination: InventoryPagination;
  lowStockProductsCount: number;
  inventorySummary: InventorySummary;
  filters: Record<string, string>;
};

export type InventoryMovement = {
  id: number;
  type: string;
  type_label: string;
  type_badge: string;
  origin: string | null;
  origin_label: string | null;
  quantity: number;
  stock_before: number;
  stock_after: number;
  reason: string | null;
  admin: { id: number; name: string } | null;
  created_at_human: string | null;
};

export type MovementsPayload = {
  success: boolean;
  product: { product_id: number; name: string; sku: string; stock_current: number };
  data: InventoryMovement[];
  summary: { total_entradas: number; total_salidas: number };
  meta: { current_page: number; last_page: number; total: number; per_page: number };
};

export type InventoryListParams = {
  page?: number;
  search?: string;
  stock_status?: string;
  status?: string;
};

export async function getInventory(params: InventoryListParams): Promise<InventoryIndex> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/inventory", { params: clean });
  return data.data as InventoryIndex;
}

export async function addStock(productId: number, quantity: number, reason: string) {
  const { data } = await api.post(`/api/v1/admin/inventory/${productId}/add`, { quantity, reason });
  return data;
}

export async function removeStock(productId: number, quantity: number, reason: string) {
  const { data } = await api.post(`/api/v1/admin/inventory/${productId}/remove`, { quantity, reason });
  return data;
}

export type MovementFilters = {
  page?: number;
  type?: string;
  origin?: string;
  date_from?: string;
  date_to?: string;
};

export async function getMovements(
  productId: number,
  filters: MovementFilters | number = 1,
): Promise<MovementsPayload> {
  const params = typeof filters === "number" ? { page: filters } : filters;
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get(`/api/v1/admin/inventory/${productId}/movements`, {
    params: clean,
  });
  return data as MovementsPayload;
}

// --- Importación de catálogo ---

export type ImportProgress = {
  status: string; // queued | processing | done | failed | unknown
  processed?: number;
  total?: number;
  created?: number;
  updated?: number;
  message?: string;
  [key: string]: unknown;
};

export async function importCatalog(file: File): Promise<{ importId: string }> {
  const fd = new FormData();
  fd.append("import_file", file);
  const { data } = await api.post("/api/v1/admin/inventory/import", fd);
  return data as { importId: string };
}

export async function getImportProgress(importId: string): Promise<ImportProgress> {
  const { data } = await api.get(`/api/v1/admin/inventory/import/${importId}/progress`);
  return data as ImportProgress;
}
