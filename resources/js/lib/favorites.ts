import { parseJsonResponse } from '@/lib/parseJsonResponse';

type FavoriteTogglePayload = {
  success?: boolean;
  is_favorite?: boolean;
  message?: string;
};

export type FavoriteToggleResult = {
  success: boolean;
  isFavorite: boolean;
  message?: string;
};

export async function toggleFavorite(productId: number, csrfToken: string): Promise<FavoriteToggleResult> {
  const response = await fetch('/favorites/toggle', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ product_id: productId }),
  });

  const parsed = await parseJsonResponse<FavoriteTogglePayload>(response);
  if (!parsed.ok) {
    return { success: false, isFavorite: false, message: parsed.message };
  }

  const payload = parsed.data;

  return {
    success: response.ok && payload.success === true,
    isFavorite: payload.is_favorite === true,
    message: payload.message,
  };
}
