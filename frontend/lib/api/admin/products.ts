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

export type ProductVariant = {
  product_id: number;
  name: string;
  status: string;
  stock_current: number;
  sale_price: string;
  sku: string;
  sku_custom: string | null;
  sku_locked: boolean;
};

export type ClassificationValue = {
  id: number;
  value?: string;
  dimension?: { name?: string } | null;
};

export type ProductDetail = {
  product_id: number;
  name: string;
  sku: string | null;
  description: string | null;
  sale_price: string;
  purchase_price: string;
  stock_current: number;
  stock_minimum: number;
  status: string;
  is_featured: boolean;
  category: { name: string } | null;
  supplier: { name: string } | null;
  brands: { id: number; name: string }[];
  media_main: string | null;
  media_gallery: string[];
  uses_placeholder_image: boolean;
  placeholder_icon_class?: string;
  variants: ProductVariant[];
  classification_values?: ClassificationValue[];
};

/** Detalle (ProductResource): relaciones, media, variantes y clasificaciones. */
export async function getProduct(id: number | string): Promise<Record<string, unknown>> {
  const { data } = await api.get(`/api/v1/admin/products/${id}`);
  return data.data as Record<string, unknown>;
}

export async function getProductDetail(id: number | string): Promise<ProductDetail> {
  const { data } = await api.get(`/api/v1/admin/products/${id}`);
  return data.data as ProductDetail;
}

/** Prefija las URLs relativas de media (/storage/...) con el origen de la API. */
export function mediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${process.env.NEXT_PUBLIC_API_URL ?? ""}${path}`;
}

export async function createProduct(payload: ProductFormValues) {
  const { data } = await api.post("/api/v1/admin/products", payload);
  return data;
}

export async function updateProduct(id: number | string, payload: ProductFormValues) {
  const { data } = await api.put(`/api/v1/admin/products/${id}`, payload);
  return data;
}

// --- Acciones rápidas ---

export async function activateProduct(id: number | string) {
  const { data } = await api.post(`/api/v1/admin/products/${id}/activate`);
  return data;
}

export async function deactivateProduct(id: number | string) {
  const { data } = await api.post(`/api/v1/admin/products/${id}/deactivate`);
  return data;
}

export async function toggleProductFeatured(id: number | string) {
  const { data } = await api.post(`/api/v1/admin/products/${id}/featured`);
  return data;
}

export async function forceDeleteProduct(id: number | string) {
  const { data } = await api.delete(`/api/v1/admin/products/${id}/force`);
  return data;
}

// --- Galería ---

export type GalleryItem = { id: number; url: string };
export type ProductGallery = { main: GalleryItem | null; gallery: GalleryItem[] };

export async function getProductGallery(id: number | string): Promise<ProductGallery> {
  const { data } = await api.get(`/api/v1/admin/products/${id}/gallery`);
  return data.data as ProductGallery;
}

export async function uploadGalleryImage(
  id: number | string,
  file: File,
): Promise<ProductGallery> {
  const form = new FormData();
  form.append("image", file);
  const { data } = await api.post(`/api/v1/admin/products/${id}/gallery`, form);
  return data.data as ProductGallery;
}

export async function promoteGalleryImage(id: number | string, mediaId: number) {
  const { data } = await api.post(`/api/v1/admin/products/${id}/gallery/${mediaId}/promote`);
  return data.data as ProductGallery;
}

export async function deleteGalleryImage(id: number | string, mediaId: number) {
  const { data } = await api.delete(`/api/v1/admin/products/${id}/gallery/${mediaId}`);
  return data.data as ProductGallery;
}

// --- Clasificaciones (por categoría, un valor por dimensión) ---

export type ClassificationAttribute = {
  id: number;
  label: string;
  selected: number | null;
  values: { id: number; value: string }[];
};

export type ProductClassifications = {
  editable: boolean;
  reason?: string;
  attributes: ClassificationAttribute[];
};

export async function getProductClassifications(
  id: number | string,
): Promise<ProductClassifications> {
  const { data } = await api.get(`/api/v1/admin/products/${id}/classifications`);
  return data.data as ProductClassifications;
}

export async function updateProductClassifications(
  id: number | string,
  classificationValueIds: number[],
) {
  const { data } = await api.put(`/api/v1/admin/products/${id}/classifications`, {
    classification_value_ids: classificationValueIds,
  });
  return data;
}

// --- Variantes (productos existentes enlazados) ---

export async function addVariant(productId: number | string, variantProductId: number) {
  const { data } = await api.post(`/api/v1/admin/products/${productId}/variants`, {
    variant_product_id: variantProductId,
  });
  return data;
}

export async function removeVariant(productId: number | string, variantId: number) {
  const { data } = await api.delete(`/api/v1/admin/products/${productId}/variants/${variantId}`);
  return data;
}
