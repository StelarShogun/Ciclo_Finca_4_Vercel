import { api } from "@/lib/api/client";

export type CarouselSlide = {
  fallback: string | null;
  desktopWebp: string | null;
  mobileWebp: string | null;
};

export type ProductDetailProduct = {
  id: number;
  name: string;
  slug: string;
  sku: string | null;
  description: string | null;
  price: number;
  priceFormatted: string;
  stockCurrent: number;
  stockLabel: string;
  isLowStock: boolean;
  canBuy: boolean;
  isFeatured: boolean;
  isFavorite: boolean;
  isNew: boolean;
  carouselSlides: CarouselSlide[];
  showImagePlaceholder: boolean;
  placeholderIconClass: string | null;
};

export type ProductSpec = { dimensionLabel: string | null; value: string };

export type ReviewRow = {
  id: number;
  author: string;
  stars: number;
  publishedAt: string | null;
  verified: boolean;
  mine: boolean;
};

export type RelatedProduct = {
  id: number;
  name: string;
  url: string;
  sku: string | null;
  price: number;
  priceFormatted: string;
  [key: string]: unknown;
};

export type ProductDetail = {
  product: ProductDetailProduct;
  taxonomy: {
    parentCategory: { id: number; name: string } | null;
    subcategory: { id: number; name: string } | null;
  };
  primaryBrand: { id: number; name: string } | null;
  isNoveltyProduct: boolean;
  orderReservationHours: number;
  specs: ProductSpec[];
  reviews: {
    totalCount: number;
    averageStars: number;
    starDistribution: Record<string, number>;
    clientCanReview: boolean;
    clientReviewStars: number | null;
    items: ReviewRow[];
  };
  relatedProducts: RelatedProduct[];
};

export async function getProductDetail(id: number | string): Promise<ProductDetail> {
  const { data } = await api.get(`/api/v1/products/${id}`);
  return data.data as ProductDetail;
}

export async function saveReview(productId: number | string, stars: number) {
  const { data } = await api.post(`/api/v1/products/${productId}/reviews`, { stars });
  return data;
}
