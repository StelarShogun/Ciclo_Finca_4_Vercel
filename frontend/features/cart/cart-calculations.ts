import type { CartItem } from "@/lib/api/client/cart";
import { buildPaginationState } from "@/lib/pagination";

export const CART_PER_PAGE = 10;

export function totalCartQuantity(items: Pick<CartItem, "quantity">[]): number {
  return items.reduce((sum, item) => sum + item.quantity, 0);
}

export function paginatedCartItems<T>(items: T[], page: number, perPage = CART_PER_PAGE) {
  const pagination = buildPaginationState(items.length, page, perPage);
  return {
    pagination,
    items: items.slice(pagination.start, pagination.end),
  };
}
