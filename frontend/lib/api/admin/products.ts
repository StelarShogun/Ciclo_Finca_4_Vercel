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

export async function getProducts(params: {
  page?: number;
  per_page?: number;
}): Promise<Paginated<AdminProduct>> {
  const { data } = await api.get("/api/v1/admin/products", { params });
  return data as Paginated<AdminProduct>;
}
