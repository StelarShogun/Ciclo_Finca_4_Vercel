import { normalizeCartItems } from '@/features/client/cart/types';
import type { CartActionResult, CartPagePayload, CartPaymentMethod } from '@/features/client/cart/types';

type CartApiPayload = {
  success?: boolean;
  message?: string;
  cart_count?: number;
  cartCount?: number;
  cart?: CartPagePayload;
  redirect_url?: string;
  redirectUrl?: string;
};

async function readCartActionResponse(response: Response): Promise<CartActionResult> {
  const contentType = response.headers.get('content-type') ?? '';

  if (response.status === 419) {
    return {
      success: false,
      message: 'La sesión expiró. Recargá la página e inténtalo de nuevo.',
    };
  }

  if (!contentType.includes('application/json')) {
    return {
      success: false,
      message: response.ok ? 'Respuesta inesperada del servidor.' : 'No se pudo completar la acción.',
    };
  }

  let payload: CartApiPayload;
  try {
    payload = (await response.json()) as CartApiPayload;
  } catch {
    return {
      success: false,
      message: 'No se pudo leer la respuesta del servidor.',
    };
  }

  return {
    success: response.ok && payload.success === true,
    message: payload.message,
    cartCount: payload.cartCount ?? payload.cart_count,
    cart: payload.cart
      ? {
          ...payload.cart,
          items: normalizeCartItems(payload.cart.items),
        }
      : undefined,
    redirectUrl: payload.redirectUrl ?? payload.redirect_url,
  };
}

async function sendCartRequest(
  url: string,
  method: string,
  csrfToken: string,
  body?: Record<string, unknown>,
): Promise<CartActionResult> {
  const response = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  return await readCartActionResponse(response);
}

export function addToCart(productId: number, quantity: number, csrfToken: string): Promise<CartActionResult> {
  return sendCartRequest('/cart/add', 'POST', csrfToken, { product_id: productId, quantity });
}

export function updateCartItem(productId: number, quantity: number, csrfToken: string): Promise<CartActionResult> {
  return sendCartRequest('/cart/update', 'PUT', csrfToken, { product_id: productId, quantity });
}

export function removeCartItem(productId: number, csrfToken: string): Promise<CartActionResult> {
  return sendCartRequest(`/cart/remove/${productId}`, 'DELETE', csrfToken);
}

export function clearCart(csrfToken: string): Promise<CartActionResult> {
  return sendCartRequest('/cart/clear', 'DELETE', csrfToken);
}

export function checkoutCart(
  csrfToken: string,
  paymentMethod: CartPaymentMethod = 'cash',
): Promise<CartActionResult> {
  return sendCartRequest('/cart/checkout', 'POST', csrfToken, { payment_method: paymentMethod });
}
