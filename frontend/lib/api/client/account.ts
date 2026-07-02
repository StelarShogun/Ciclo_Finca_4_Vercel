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
  placeholder_icon_class: string | null;
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

// --- Notificaciones ---

export type NotificationItem = {
  id: string;
  createdAtLabel: string;
  message: string;
  actionUrl: string | null;
  actionLabel: string;
};

export type NotificationsResponse = {
  notifications: NotificationItem[];
  pagination: { currentPage: number; lastPage: number; total: number };
};

export async function getNotifications(page = 1): Promise<NotificationsResponse> {
  const { data } = await api.get("/api/v1/notifications", { params: { page } });
  return data.data as NotificationsResponse;
}

export async function markNotificationRead(id: string) {
  const { data } = await api.post(`/api/v1/notifications/${id}/read`);
  return data;
}

export async function markAllNotificationsRead() {
  const { data } = await api.post("/api/v1/notifications/read-all");
  return data;
}

// --- Perfil ---

export type ClientProfile = {
  name: string;
  first_surname: string;
  second_surname: string;
  gmail: string;
  provider: string;
  avatar_url: string | null;
  isGoogleOnly: boolean;
};

export async function getProfile(): Promise<ClientProfile> {
  const { data } = await api.get("/api/v1/profile");
  return data.data as ClientProfile;
}

export async function updateProfile(values: {
  name: string;
  first_surname: string;
  second_surname: string | null;
  gmail: string;
}) {
  const { data } = await api.put("/api/v1/profile", values);
  return data;
}

export async function updatePassword(values: {
  current_password?: string;
  new_password: string;
  new_password_confirmation: string;
}) {
  const { data } = await api.put("/api/v1/profile/password", values);
  return data;
}

export async function updateAvatar(file: File): Promise<{ avatar_url: string | null }> {
  const form = new FormData();
  form.append("avatar", file);
  const { data } = await api.post("/api/v1/profile/avatar", form, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return data.data as { avatar_url: string | null };
}
