"use client";

import { CartItemRow } from "@/components/storefront/cart/cart-item-row";
import { ListPagination } from "@/components/shared/list-pagination";
import type { CartItem } from "@/lib/api/client/cart";

type CartItemsProps = {
  items: CartItem[];
  busyItemId: string | null;
  pagination: { currentPage: number; lastPage: number; total: number; perPage: number };
  onPageChange: (page: number) => void;
  onQuantityChange: (item: CartItem, qty: number) => void;
  onRemove: (item: CartItem) => void;
};

export function CartItems({
  items,
  busyItemId,
  pagination,
  onPageChange,
  onQuantityChange,
  onRemove,
}: CartItemsProps) {
  return (
    <div>
      <ul className="space-y-3" aria-label="Productos en el carrito">
        {items.map((item) => (
          <CartItemRow
            key={item.productId}
            item={item}
            isBusy={busyItemId === item.productId}
            onQuantityChange={onQuantityChange}
            onRemove={onRemove}
          />
        ))}
      </ul>
      <ListPagination pagination={pagination} onPageChange={onPageChange} label="carrito" />
    </div>
  );
}
