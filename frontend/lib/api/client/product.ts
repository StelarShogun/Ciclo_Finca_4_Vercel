import { api } from "@/lib/api/client";

export type CarouselSlide = {
  fallback: string | null;
  desktopWebp: string | null;
  mobileWebp: string | null;
};

export type ProductDetailProduct = {
  id: string; // ID público (ULID)
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
  id: string;
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
    parentCategory: { id: string; name: string } | null;
    subcategory: { id: string; name: string } | null;
  };
  primaryBrand: { id: string; name: string } | null;
  isNoveltyProduct: boolean;
  whatsappConsultUrl: string | null;
  tabs: { defaultTab: string; hasDescription: boolean; hasSpecs: boolean; hasRelated: boolean };
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

export async function getProductDetail(id: string): Promise<ProductDetail> {
  const { data } = await api.get(`/api/v1/products/${id}`);
  const detail = data.data as ProductDetail;
  // Sin reseñas el backend manda null; el tipo promete number.
  detail.reviews.averageStars = Number(detail.reviews.averageStars ?? 0);
  return detail;
}

export async function saveReview(productId: string, stars: number) {
  const { data } = await api.post(`/api/v1/products/${productId}/reviews`, { stars });
  return data;
}
