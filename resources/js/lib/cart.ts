type AddToCartResponse = {
  success: boolean;
  message?: string;
  cartCount?: number;
};

import { parseJsonResponse } from '@/lib/parseJsonResponse';

type CartApiPayload = {
  success?: boolean;
  message?: string;
  cart_count?: number;
};

export async function addToCart(
  productId: number,
  quantity: number,
  csrfToken: string,
): Promise<AddToCartResponse> {
  const response = await fetch('/cart/add', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ product_id: productId, quantity }),
  });

  const parsed = await parseJsonResponse<CartApiPayload>(response);
  if (!parsed.ok) {
    return { success: false, message: parsed.message };
  }

  const payload = parsed.data;

  return {
    success: response.ok && payload.success === true,
    message: payload.message,
    cartCount: payload.cart_count,
  };
}
