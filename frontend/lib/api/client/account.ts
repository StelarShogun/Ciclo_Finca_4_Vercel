import { api } from "@/lib/api/client";

// --- Favoritos ---

export type FavoriteItem = {
  product_id: number;
  name: string;
  category: string;
  price: number;
  price_formatted: string;
  stock_label: string;
  url: string;
  image_url: string | null;
  uses_placeholder_image: boolean;
};

export type FavoritesResponse = {
  favorites: FavoriteItem[];
  pagination: { current_page: number; last_page: number; total: number };
};

export async function getFavorites(page = 1): Promise<FavoritesResponse> {
  const { data } = await api.get("/api/v1/favorites", { params: { page } });
  return data.data as FavoritesResponse;
}

export async function toggleFavorite(productId: number) {
  const { data } = await api.post("/api/v1/favorites/toggle", { product_id: productId });
  return data;
}

// --- Facturas ---

export type InvoiceRow = {
  id: number;
  invoiceNumber: string | null;
  saleDateLabel: string;
  statusLabel: string;
  statusTone: string;
  totalFormatted: string;
};

export type InvoicesIndex = {
  tab: string;
  orders: InvoiceRow[];
  pagination: { currentPage: number; lastPage: number; total: number };
  invoiceCount: number;
  readyToPickupCount: number;
};

export async function getInvoices(tab = "facturas", page = 1): Promise<InvoicesIndex> {
  const { data } = await api.get("/api/v1/invoices", { params: { tab, page } });
  return data.data as InvoicesIndex;
}

export type InvoiceLineItem = {
  productId: number;
  name: string;
  quantity: number;
  unitPriceFormatted: string;
  totalFormatted: string;
};

export type InvoiceTotals = {
  subtotalFormatted: string;
  ivaFormatted: string;
  discountFormatted: string;
  totalFormatted: string;
  itemsCount: number;
};

export type InvoiceOrderMeta = {
  saleDateLabel: string;
  statusLabel: string;
  paymentDisplay: string;
  cancellationReason: string | null;
};

export type InvoiceDetail = {
  invoiceNumber: string | null;
  documentTitle: string;
  orderMeta: InvoiceOrderMeta;
  totals: InvoiceTotals;
  items: InvoiceLineItem[];
  printUrl: string;
};

export async function getInvoice(id: number | string): Promise<InvoiceDetail> {
  const { data } = await api.get(`/api/v1/invoices/${id}`);
  return data.data as InvoiceDetail;
}
