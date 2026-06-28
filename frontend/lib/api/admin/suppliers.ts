import { api } from "@/lib/api/client";

export type Supplier = {
  supplier_id: number;
  name: string;
  primary_contact: string;
  phone: string;
  email: string;
  address: string;
  delivery_time: number;
  rating: number | null;
  status: string;
  created_at: string | null;
};

export type SuppliersPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type SuppliersIndex = {
  suppliers: Supplier[];
  averageRating: number;
  pagination: SuppliersPagination;
  filters: Record<string, string>;
};

export type SupplierFormValues = {
  name: string;
  primary_contact: string;
  phone: string;
  email: string;
  address: string;
  delivery_time: number;
  rating?: number | null;
};

export type SuppliersListParams = { page?: number; name?: string; contact?: string };

export async function getSuppliers(params: SuppliersListParams): Promise<SuppliersIndex> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/suppliers", { params: clean });
  return data.data as SuppliersIndex;
}

export async function createSupplier(values: SupplierFormValues) {
  const { data } = await api.post("/api/v1/admin/suppliers", values);
  return data;
}

export async function updateSupplier(id: number, values: SupplierFormValues) {
  const { data } = await api.put(`/api/v1/admin/suppliers/${id}`, values);
  return data;
}

export async function deleteSupplier(id: number) {
  const { data } = await api.delete(`/api/v1/admin/suppliers/${id}`);
  return data;
}
