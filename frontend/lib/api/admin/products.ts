import { api } from "@/lib/api/client";

export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
};

export type AdminProduct = {
  product_id: number;
  name: string;
  sku: string | null;
  sale_price: string;
  purchase_price: string;
  stock_current: number;
  stock_minimum: number;
  status: string;
  is_featured: boolean;
  category: { name: string } | null;
  supplier: { name: string } | null;
  image: string | null;
};

export type ProductListParams = {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
  stock_status?: string;
};

export async function getProducts(
  params: ProductListParams,
): Promise<Paginated<AdminProduct>> {
  // Quita filtros vacíos para no mandar query params en blanco.
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/products", { params: clean });
  return data as Paginated<AdminProduct>;
}

// --- Crear / editar ---

export type IdName = { category_id: number; name: string };
export type ProductFormOptions = {
  categories: IdName[];
  subcategoriesByParent: Record<string, IdName[]>;
  brands: { id: number; name: string }[];
  suppliers: { supplier_id: number; name: string }[];
  statuses: { value: string; label: string }[];
};

export type ProductFormValues = {
  parent_category_id: number;
  category_id: number;
  brand_id: number;
  supplier_id: number;
  name: string;
  description?: string | null;
  purchase_price: number;
  sale_price: number;
  stock_current: number;
  stock_minimum: number;
  status: string;
  is_featured: boolean;
};

export async function getProductFormOptions(): Promise<ProductFormOptions> {
  const { data } = await api.get("/api/v1/admin/products/form-options");
  return data.data as ProductFormOptions;
}

/** Detalle (ProductResource): incluye relaciones para precargar el form de edición. */
export async function getProduct(id: number | string): Promise<Record<string, unknown>> {
  const { data } = await api.get(`/api/v1/admin/products/${id}`);
  return data.data as Record<string, unknown>;
}

export async function createProduct(payload: ProductFormValues) {
  const { data } = await api.post("/api/v1/admin/products", payload);
  return data;
}

export async function updateProduct(id: number | string, payload: ProductFormValues) {
  const { data } = await api.put(`/api/v1/admin/products/${id}`, payload);
  return data;
}
