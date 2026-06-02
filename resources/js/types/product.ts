import type { CatalogPagination } from '@/types/catalog';

export type ProductCarouselSlide = {
  fallback: string;
  desktopWebp: string | null;
  mobileWebp: string | null;
};

export type ProductDetail = {
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
  carouselSlides: ProductCarouselSlide[];
  showImagePlaceholder: boolean;
  placeholderIconClass: string;
};

export type ProductReviewRow = {
  id: number;
  stars: number;
  author: string;
  publishedAt: string | null;
  publishedAtIso: string | null;
  verified: boolean;
  mine: boolean;
};

export type ProductDetailPageProps = {
  product: ProductDetail;
  taxonomy: {
    parentCategory: { id: number; name: string; url: string } | null;
    subcategory: { id: number; name: string; url: string } | null;
  };
  primaryBrand: { id: number; name: string; catalogUrl: string } | null;
  isNoveltyProduct: boolean;
  whatsappConsultUrl: string | null;
  orderReservationHours: number;
  tabs: {
    defaultTab: string;
    hasDescription: boolean;
    hasSpecs: boolean;
    hasRelated: boolean;
  };
  specs: Array<{ dimensionLabel: string | null; value: string }>;
  reviews: {
    totalCount: number;
    averageStars: number | null;
    starDistribution: Record<number, number>;
    sort: string;
    filter: string;
    clientCanReview: boolean;
    clientReviewStars: number | null;
    myHighlighted: ProductReviewRow | null;
    showMyHighlighted: boolean;
    items: ProductReviewRow[];
    pagination: Pick<CatalogPagination, 'currentPage' | 'lastPage' | 'total' | 'links'>;
  };
  relatedProducts: Array<{
    id: number;
    name: string;
    url: string;
    sku: string | null;
    priceFormatted: string;
    price: number;
    stockLabel: string;
    stockCurrent: number;
    canBuy: boolean;
    isFavorite: boolean;
    categoryName: string;
    brandName: string | null;
    image: {
      fallback: string;
      desktopWebp: string | null;
      mobileWebp: string | null;
      usesPlaceholder: boolean;
      placeholderIconClass: string;
    };
    reviews: { avg: number; count: number };
  }>;
  favoriteConfig: {
    toggleUrl: string;
    loginUrl: string;
  };
  seo: {
    canonicalUrl: string;
    description: string;
    ogImage: string;
    robots: string;
  };
};
