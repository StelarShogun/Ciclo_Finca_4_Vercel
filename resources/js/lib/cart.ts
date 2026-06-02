type AddToCartResponse = {
  success: boolean;
  message?: string;
  cartCount?: number;
};

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

  const payload = (await response.json()) as CartApiPayload;

  return {
    success: response.ok && payload.success === true,
    message: payload.message,
    cartCount: payload.cart_count,
  };
}
