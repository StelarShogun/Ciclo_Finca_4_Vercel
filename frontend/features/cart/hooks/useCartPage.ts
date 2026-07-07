"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  checkout,
  clearCart,
  getCart,
  removeCartItem,
  updateCartItem,
  type CartItem,
  type CheckoutResult,
} from "@/lib/api/client/cart";
import { useMe } from "@/lib/auth/use-me";
import { apiErrorMessage } from "@/lib/errors";
import { queryKeys } from "@/lib/query-keys";
import type { CartPaymentMethod } from "@/components/storefront/cart/cart-summary";
import { paginatedCartItems, totalCartQuantity } from "../cart-calculations";

export function useCartPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();
  const [page, setPage] = useState(1);
  const [paymentMethod, setPaymentMethod] = useState<CartPaymentMethod>("cash");
  const [busyItemId, setBusyItemId] = useState<string | null>(null);
  const [done, setDone] = useState<CheckoutResult | null>(null);

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) router.replace("/login?redirect=/cart");
  }, [me.isError, me.error, me.data, router]);

  const cartQuery = useQuery({ queryKey: queryKeys.cart, queryFn: getCart });
  const invalidateCart = () => queryClient.invalidateQueries({ queryKey: queryKeys.cart });

  const update = useMutation({
    mutationFn: ({ item, qty }: { item: CartItem; qty: number }) => {
      setBusyItemId(item.productId);
      return updateCartItem(item.productId, qty);
    },
    onSuccess: invalidateCart,
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo actualizar el carrito.")),
    onSettled: () => setBusyItemId(null),
  });
  const remove = useMutation({
    mutationFn: (item: CartItem) => {
      setBusyItemId(item.productId);
      return removeCartItem(item.productId);
    },
    onSuccess: () => {
      toast.success("Producto quitado");
      invalidateCart();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo quitar el producto.")),
    onSettled: () => setBusyItemId(null),
  });
  const empty = useMutation({
    mutationFn: clearCart,
    onSuccess: () => {
      toast.success("Carrito vaciado");
      invalidateCart();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No se pudo vaciar el carrito.")),
  });
  const place = useMutation({
    mutationFn: () => checkout(paymentMethod),
    onSuccess: (result) => {
      setDone(result);
      invalidateCart();
    },
    onError: (error) => toast.error(apiErrorMessage(error, "No fue posible procesar el pedido.")),
  });

  const items = useMemo(() => cartQuery.data?.items ?? [], [cartQuery.data]);
  const totalQuantity = useMemo(() => totalCartQuantity(items), [items]);
  const paginated = useMemo(() => paginatedCartItems(items, page), [items, page]);

  return {
    cartQuery,
    items,
    pageItems: paginated.items,
    pagination: paginated.pagination,
    hasItems: items.length > 0,
    totalQuantity,
    paymentMethod,
    setPaymentMethod,
    busyItemId,
    done,
    update,
    remove,
    empty,
    place,
    setPage,
  };
}
