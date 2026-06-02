export type FlashMessage = {
  success?: string | null;
  error?: string | null;
  clientSuccessModal?: {
    kind?: string;
    authIcon?: string;
    title?: string;
    text?: string;
  } | null;
};

export type ClientUser = {
  id: number;
  name: string;
  first_surname?: string | null;
  second_surname?: string | null;
  gmail: string;
  email_verified?: boolean;
};

export type AdminUser = {
  id: number;
  name: string;
  first_surname?: string | null;
  second_surname?: string | null;
  gmail: string;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type Product = {
  id: number;
  name: string;
  price?: number | null;
  image_url?: string | null;
  stock_label?: string | null;
};

export type CartItem = {
  product_id: number;
  name: string;
  quantity: number;
  price: number;
  subtotal: number;
};

export type InertiaSharedProps = {
  auth: {
    client: ClientUser | null;
    admin: AdminUser | null;
  };
  cartCount: number;
  csrfToken: string;
  flash: FlashMessage;
  theme?: string | null;
};
