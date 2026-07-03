import { api } from "@/lib/api/client";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

/** Prefija rutas relativas de media (/storage/...) con el origen de la API. */
export function storeMediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith("http") || path.startsWith("data:")) return path;
  return `${API_URL}${path}`;
}

export type CatalogProduct = {
  id: string; // ID público (ULID); el autoincremental nunca sale del API
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
  category: { id: string; name: string } | null;
  parentCategory: { id: string; name: string } | null;
  brands: { id: string; name: string }[];
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
  id: string;
  name: string;
  icon?: string;
  url_parent?: string;
  children: { id: string; name: string; url: string }[];
};

export type CatalogSpotlightItem = {
  kind: string; // "featured" | "novelty"
  product: CatalogProduct;
};

export type CatalogResponse = {
  products: CatalogProduct[];
  catalogSpotlight: CatalogSpotlightItem[];
  pagination: CatalogPagination;
  categories: CatalogCategoryNav[];
  brands: { id: string; name: string }[];
  filters: {
    search: string;
    categoryId: string | null;
    brandId: string | null;
    minPrice: string;
    maxPrice: string;
    sort: string;
    direction: string;
    perPage: number;
  };
  selectedCategory: { id: string; name: string } | null;
  summary: { totalProducts: number; totalCategories: number; activeFilterCount: number };
};

export type CatalogParams = {
  search?: string;
  category_id?: string;
  brand_id?: string;
  min_price?: string;
  max_price?: string;
  sort?: string;
  direction?: string;
  per_page?: number;
  page?: number;
};

export type Suggestion = {
  type: "product" | "category";
  id: number;
  name: string;
  sku: string | null;
  category: string | null;
  image_url: string | null;
  url: string;
};

export async function getSuggestions(q: string): Promise<Suggestion[]> {
  if (q.trim().length < 2) return [];
  const { data } = await api.get("/api/v1/catalog/suggestions", { params: { q } });
  return (data.suggestions ?? []) as Suggestion[];
}

export async function getCatalog(params: CatalogParams): Promise<CatalogResponse> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/catalog", { params: clean });
  return data.data as CatalogResponse;
}
