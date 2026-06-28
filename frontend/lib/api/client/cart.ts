import { api } from "@/lib/api/client";

export type CartItem = {
  productId: number;
  name: string;
  productUrl: string;
  unitPrice: number;
  unitPriceFormatted: string;
  quantity: number;
  subtotal: number;
  subtotalFormatted: string;
  stockCurrent: number;
  image: {
    fallback: string | null;
    desktopWebp: string | null;
    mobileWebp: string | null;
    usesPlaceholder: boolean;
  };
};

export type Cart = {
  items: CartItem[];
  total: number;
  totalFormatted: string;
  pickupPolicyLine: string | null;
  pickupPolicyNotice: string | null;
};

export async function getCart(): Promise<Cart> {
  const { data } = await api.get("/api/v1/cart");
  return data.data as Cart;
}

export async function addToCart(productId: number, quantity: number) {
  const { data } = await api.post("/api/v1/cart/add", { product_id: productId, quantity });
  return data;
}

export async function updateCartItem(productId: number, quantity: number) {
  const { data } = await api.put("/api/v1/cart/update", { product_id: productId, quantity });
  return data;
}

export async function removeCartItem(productId: number) {
  const { data } = await api.delete(`/api/v1/cart/remove/${productId}`);
  return data;
}

export async function clearCart() {
  const { data } = await api.delete("/api/v1/cart/clear");
  return data;
}
