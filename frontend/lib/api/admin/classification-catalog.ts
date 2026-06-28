import { api } from "@/lib/api/client";

export type CatalogSubcategory = {
  category_id: number;
  name: string;
  parent_name: string | null;
  dimensions_count: number;
};

export type CatalogPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type CatalogIndex = {
  subcategories: CatalogSubcategory[];
  pagination: CatalogPagination;
};

export type CatalogAttribute = {
  id: number;
  label: string;
  slug: string;
  values_count: number;
  trashed: boolean;
};

export type CategoryAttributes = {
  category: { category_id: number; name: string; parent_name: string | null };
  attributes: CatalogAttribute[];
};

export type CatalogValue = { id: number; value: string; trashed: boolean };

export type DimensionValues = {
  dimension: {
    id: number;
    label: string;
    category_id: number;
    category_name: string | null;
    parent_name: string | null;
  };
  values: CatalogValue[];
};

export async function getCatalog(page = 1): Promise<CatalogIndex> {
  const { data } = await api.get("/api/v1/admin/classification-catalog", { params: { page } });
  return data.data as CatalogIndex;
}

export async function getCategoryAttributes(categoryId: number): Promise<CategoryAttributes> {
  const { data } = await api.get(`/api/v1/admin/classification-catalog/${categoryId}`);
  return data.data as CategoryAttributes;
}

export async function getDimensionValues(dimensionId: number): Promise<DimensionValues> {
  const { data } = await api.get(
    `/api/v1/admin/classification-catalog/dimensions/${dimensionId}/values`,
  );
  return data.data as DimensionValues;
}

// Dimensiones
export async function createDimension(categoryId: number, label: string) {
  const { data } = await api.post(
    `/api/v1/admin/classification-catalog/${categoryId}/dimensions`,
    { label },
  );
  return data;
}

export async function updateDimension(dimensionId: number, label: string) {
  const { data } = await api.put(
    `/api/v1/admin/classification-catalog/dimensions/${dimensionId}`,
    { label },
  );
  return data;
}

export async function deleteDimension(dimensionId: number) {
  const { data } = await api.delete(
    `/api/v1/admin/classification-catalog/dimensions/${dimensionId}`,
  );
  return data;
}

export async function restoreDimension(dimensionId: number) {
  const { data } = await api.post(
    `/api/v1/admin/classification-catalog/dimensions/${dimensionId}/restore`,
  );
  return data;
}

// Valores
export async function createValue(dimensionId: number, value: string) {
  const { data } = await api.post(
    `/api/v1/admin/classification-catalog/dimensions/${dimensionId}/values`,
    { value },
  );
  return data;
}

export async function updateValue(valueId: number, value: string) {
  const { data } = await api.put(`/api/v1/admin/classification-catalog/values/${valueId}`, {
    value,
  });
  return data;
}

export async function deleteValue(valueId: number) {
  const { data } = await api.delete(`/api/v1/admin/classification-catalog/values/${valueId}`);
  return data;
}

export async function restoreValue(valueId: number) {
  const { data } = await api.post(
    `/api/v1/admin/classification-catalog/values/${valueId}/restore`,
  );
  return data;
}
