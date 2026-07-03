export type HomeHero = {
  title: string;
  emphasis: string;
  subtitle: string;
  description: string;
};

export type HomeProductImage = {
  fallback: string;
  desktopWebp?: string | null;
  mobileWebp?: string | null;
  usesPlaceholder: boolean;
  placeholderIconClass: string;
};

export type HomeProductReviews = {
  avg: number;
  count: number;
};

export type HomeProduct = {
  id: number;
  name: string;
  description?: string | null;
  category: string;
  price: number;
  priceFormatted: string;
  stockCurrent: number;
  stockLabel: string;
  canBuy: boolean;
  sku?: string | null;
  url: string;
  image: HomeProductImage;
  reviews: HomeProductReviews;
};

export type HomeCategoryChild = {
  id: number;
  name: string;
  url: string;
};

export type HomeCategory = {
  id: number;
  name: string;
  description?: string | null;
  url: string;
  iconClass: string;
  children: HomeCategoryChild[];
};
