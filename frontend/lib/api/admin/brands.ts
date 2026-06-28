import { api } from "@/lib/api/client";
import type { Paginated } from "@/lib/api/admin/products";

export type Brand = { id: number; name: string };

export type BrandListParams = { page?: number; per_page?: number; name?: string };

export async function getBrands(params: BrandListParams): Promise<Paginated<Brand>> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/brands", { params: clean });
  return data as Paginated<Brand>;
}

export async function createBrand(name: string) {
  const { data } = await api.post("/api/v1/admin/brands", { name });
  return data;
}

export async function updateBrand(id: number, name: string) {
  const { data } = await api.put(`/api/v1/admin/brands/${id}`, { name });
  return data;
}

export async function deleteBrand(id: number) {
  const { data } = await api.delete(`/api/v1/admin/brands/${id}`);
  return data;
}
