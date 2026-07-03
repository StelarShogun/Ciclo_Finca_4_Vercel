import type { CatalogPagination } from '@/types/catalog';

export type CartItemImage = {
  fallback: string;
  desktopWebp?: string | null;
  mobileWebp?: string | null;
  usesPlaceholder: boolean;
  placeholderIconClass: string;
};

export type CartItem = {
  productId: number;
  name: string;
  slug?: string | null;
  productUrl?: string | null;
  quantity: number;
  unitPrice: number;
  unitPriceFormatted: string;
  subtotal: number;
  subtotalFormatted: string;
  stockCurrent: number;
  canUpdate: boolean;
  image: CartItemImage;
};

export type CartFeaturedProduct = {
  id: number;
  name: string;
  priceFormatted: string;
  url: string;
  image: CartItemImage;
};

export type CartPagePayload = {
  items: CartItem[];
  pagination: CatalogPagination;
  total: number;
  totalFormatted: string;
  pickupPolicyLine: string;
  pickupPolicyNotice: string;
  stockAdjustedMessage: string | null;
  featuredProducts?: CartFeaturedProduct[];
};

export type CartPageProps = CartPagePayload;

export type CartPaymentMethod = 'cash' | 'sinpe' | 'transfer';

export type CartActionResult = {
  success: boolean;
  message?: string;
  cartCount?: number;
  cart?: CartPagePayload;
  redirectUrl?: string;
};

type RawCartItem = Partial<CartItem> & {
  product_id?: number;
  product_url?: string | null;
  price?: number;
  priceFormatted?: string;
  image_url?: string | null;
  uses_placeholder_image?: boolean;
  placeholder_icon_class?: string;
  stock_available?: number;
};

function numberFrom(value: unknown, fallback = 0): number {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function stringFrom(value: unknown, fallback = ''): string {
  return typeof value === 'string' ? value : fallback;
}

export function normalizeCartItem(raw: RawCartItem): CartItem {
  const unitPrice = numberFrom(raw.unitPrice ?? raw.price);
  const quantity = numberFrom(raw.quantity, 1);
  const fallback = raw.image?.fallback ?? raw.image_url ?? '/favicon.svg';
  const stockCurrent = numberFrom(raw.stockCurrent ?? raw.stock_available);

  return {
    productId: numberFrom(raw.productId ?? raw.product_id),
    name: stringFrom(raw.name, 'Producto'),
    slug: raw.slug ?? null,
    productUrl: raw.productUrl ?? raw.product_url ?? null,
    quantity,
    unitPrice,
    unitPriceFormatted: stringFrom(raw.unitPriceFormatted ?? raw.priceFormatted),
    subtotal: numberFrom(raw.subtotal, unitPrice * quantity),
    subtotalFormatted: stringFrom(raw.subtotalFormatted),
    stockCurrent,
    canUpdate: raw.canUpdate ?? stockCurrent > 0,
    image: {
      fallback,
      desktopWebp: raw.image?.desktopWebp ?? null,
      mobileWebp: raw.image?.mobileWebp ?? null,
      usesPlaceholder: raw.image?.usesPlaceholder ?? raw.uses_placeholder_image ?? false,
      placeholderIconClass: raw.image?.placeholderIconClass ?? raw.placeholder_icon_class ?? 'fas fa-box',
    },
  };
}

export function normalizeCartItems(items: RawCartItem[]): CartItem[] {
  return items.map((item) => normalizeCartItem(item));
}
