export type CatalogProductImage = {
  fallback: string;
  desktopWebp?: string | null;
  mobileWebp?: string | null;
  usesPlaceholder: boolean;
  placeholderIconClass: string;
};

export type CatalogTaxonomy = {
  id: number;
  name: string;
};

export type CatalogBrand = {
  id: number;
  name: string;
};

export type CatalogProduct = {
  id: number;
  name: string;
  description?: string | null;
  price: number;
  priceFormatted: string;
  stockCurrent: number;
  stockLabel: string;
  canBuy: boolean;
  isFeatured: boolean;
  isNew: boolean;
  isFavorite: boolean;
  sku?: string | null;
  url: string;
  category?: CatalogTaxonomy | null;
  parentCategory?: CatalogTaxonomy | null;
  brands: CatalogBrand[];
  image: CatalogProductImage;
  reviews: {
    avg: number;
    count: number;
  };
};

export type CatalogCategory = {
  id: number;
  name: string;
  icon: string;
  url_parent: string;
  children: Array<{
    id: number;
    name: string;
    url: string;
  }>;
};

export type CatalogFilters = {
  search: string;
  categoryId?: number | null;
  brandId?: number | null;
  minPrice: string;
  maxPrice: string;
  sort: string;
  direction: string;
  perPage: number;
};

export type CatalogPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  from?: number | null;
  to?: number | null;
  links: Array<{
    url?: string | null;
    label: string;
    active: boolean;
  }>;
};

export type CatalogSpotlightItem = {
  kind: 'featured' | 'novelty' | string;
  product: CatalogProduct;
};
