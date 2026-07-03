import { parseJsonResponse } from '@/shared/lib/parseJsonResponse';

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

export type FavoriteDrawerItem = {
  product_id: number;
  name: string;
  category: string;
  price_formatted: string;
  url: string;
  image_url: string | null;
  uses_placeholder_image: boolean;
  placeholder_icon_class: string;
};

export type FavoriteDrawerPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

type FavoritesIndexPayload = {
  success?: boolean;
  favorites?: FavoriteDrawerItem[];
  pagination?: FavoriteDrawerPagination;
  message?: string;
};

export function notifyFavoritesChanged(productId: number, isFavorite: boolean) {
  window.dispatchEvent(
    new CustomEvent('cf4:favorites:changed', {
      detail: { product_id: productId, is_favorite: isFavorite },
    }),
  );
}

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
  const result = {
    success: response.ok && payload.success === true,
    isFavorite: payload.is_favorite === true,
    message: payload.message,
  };

  if (result.success) {
    notifyFavoritesChanged(productId, result.isFavorite);
  }

  return result;
}

export async function fetchFavoriteDrawerPage(
  indexUrl: string,
  page: number,
  perPage = 10,
): Promise<{ favorites: FavoriteDrawerItem[]; pagination: FavoriteDrawerPagination } | null> {
  const url = new URL(indexUrl, window.location.origin);
  url.searchParams.set('page', String(Math.max(1, page)));
  url.searchParams.set('per_page', String(perPage));

  const response = await fetch(url.toString(), {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  const parsed = await parseJsonResponse<FavoritesIndexPayload>(response);
  if (!parsed.ok || parsed.data.success !== true) {
    return null;
  }

  return {
    favorites: Array.isArray(parsed.data.favorites) ? parsed.data.favorites : [],
    pagination: parsed.data.pagination ?? {
      current_page: page,
      last_page: 1,
      per_page: perPage,
      total: 0,
      from: null,
      to: null,
    },
  };
}
