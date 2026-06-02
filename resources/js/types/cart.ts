import type { CatalogPagination } from '@/types/catalog';

export type CartLineItem = {
  product_id: number;
  name: string;
  price: number;
  priceFormatted: string;
  image_url: string;
  uses_placeholder_image: boolean;
  placeholder_icon_class: string;
  quantity: number;
  stock_available: number;
  subtotal: number;
  subtotalFormatted: string;
  product_url: string;
};

export type CartPageProps = {
  items: CartLineItem[];
  pagination: CatalogPagination;
  total: number;
  totalFormatted: string;
  pickupPolicyLine: string;
  pickupPolicyNotice: string;
  stockAdjustedMessage: string | null;
};
