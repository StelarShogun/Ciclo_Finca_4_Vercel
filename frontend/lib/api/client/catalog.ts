import { api } from "@/lib/api/client";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

/** Prefija rutas relativas de media (/storage/...) con el origen de la API. */
export function storeMediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith("http") || path.startsWith("data:")) return path;
  return `${API_URL}${path}`;
}

export type CatalogProduct = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  priceFormatted: string;
  stockCurrent: number;
  stockLabel: string;
  canBuy: boolean;
  isFeatured: boolean;
  isNew: boolean;
  isFavorite: boolean;
  sku: string | null;
  url: string;
  category: { id: number; name: string } | null;
  parentCategory: { id: number; name: string } | null;
  brands: { id: number; name: string }[];
  image: {
    fallback: string | null;
    desktopWebp: string | null;
    mobileWebp: string | null;
    usesPlaceholder: boolean;
    placeholderIconClass: string | null;
  };
  reviews: { avg: number; count: number };
};

export type CatalogPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type CatalogCategoryNav = {
  id: number;
  name: string;
  url?: string;
  [key: string]: unknown;
};

export type CatalogResponse = {
  products: CatalogProduct[];
  pagination: CatalogPagination;
  categories: CatalogCategoryNav[];
  brands: { id: number; name: string }[];
  filters: {
    search: string;
    categoryId: number | null;
    brandId: number | null;
    minPrice: string;
    maxPrice: string;
    sort: string;
    direction: string;
    perPage: number;
  };
  selectedCategory: { id: number; name: string } | null;
  summary: { totalProducts: number; totalCategories: number; activeFilterCount: number };
};

export type CatalogParams = {
  search?: string;
  category_id?: number;
  brand_id?: number;
  min_price?: string;
  max_price?: string;
  sort?: string;
  direction?: string;
  page?: number;
};

export async function getCatalog(params: CatalogParams): Promise<CatalogResponse> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/catalog", { params: clean });
  return data.data as CatalogResponse;
}
