import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { Dispatch, SetStateAction } from 'react';

import { checkoutCart, clearCart, removeCartItem, updateCartItem } from '@/features/client/cart/api';
import type { CartItem, CartPaymentMethod } from '@/features/client/cart/types';
import { confirm } from '@/shared/lib/confirm';
import { useToast } from '@/shared/hooks/useToast';

type UseCartActionsOptions = {
  csrfToken: string;
  items: CartItem[];
  setItems: Dispatch<SetStateAction<CartItem[]>>;
};

const paymentLabels: Record<CartPaymentMethod, string> = {
  cash: 'efectivo',
  sinpe: 'SINPE Móvil',
  transfer: 'transferencia',
};

function formatCurrency(amount: number): string {
  return `₡${amount.toLocaleString('es-CR', { maximumFractionDigits: 0 })}`;
}

function reloadCartProps() {
  router.reload({
    only: ['items', 'pagination', 'total', 'totalFormatted', 'stockAdjustedMessage', 'cartCount', 'flash'],
  });
}

function dispatchCartCount(cartCount?: number) {
  if (typeof cartCount === 'number') {
    window.dispatchEvent(new CustomEvent('cf4:cart-count', { detail: { count: cartCount } }));
  }
}

export function useCartActions({ csrfToken, items, setItems }: UseCartActionsOptions) {
  const { showToast } = useToast();
  const [busyItemId, setBusyItemId] = useState<number | null>(null);
  const [isClearing, setIsClearing] = useState(false);
  const [isCheckingOut, setIsCheckingOut] = useState(false);

  const updateQuantity = useCallback(
    async (item: CartItem, quantity: number) => {
      const normalizedQuantity = Math.trunc(quantity);

      if (!Number.isFinite(normalizedQuantity) || normalizedQuantity < 1) {
        showToast({
          variant: 'warning',
          title: 'Cantidad inválida',
          message: 'La cantidad mínima es 1.',
        });
        return;
      }

      if (normalizedQuantity > item.stockCurrent) {
        showToast({
          variant: 'warning',
          title: 'Stock disponible',
          message: 'La cantidad no puede exceder el stock disponible.',
        });
        return;
      }

      if (normalizedQuantity === item.quantity) {
        return;
      }

      setBusyItemId(item.productId);

      try {
        const result = await updateCartItem(item.productId, normalizedQuantity, csrfToken);

        if (!result.success) {
          showToast({
            variant: 'error',
            title: 'No se pudo actualizar',
            message: result.message ?? 'No se pudo actualizar el carrito.',
          });
          reloadCartProps();
          return;
        }

        if (result.cart) {
          setItems(result.cart.items);
        } else {
          setItems((current) =>
            current.map((currentItem) => {
              if (currentItem.productId !== item.productId) {
                return currentItem;
              }

              const subtotal = currentItem.unitPrice * normalizedQuantity;

              return {
                ...currentItem,
                quantity: normalizedQuantity,
                subtotal,
                subtotalFormatted: formatCurrency(subtotal),
              };
            }),
          );
        }

        dispatchCartCount(result.cartCount);
        showToast({
          variant: 'success',
          title: 'Carrito actualizado',
          message: result.message ?? 'Cantidad actualizada.',
        });
        reloadCartProps();
      } catch {
        showToast({
          variant: 'error',
          title: 'Error',
          message: 'Ocurrió un error al actualizar el carrito.',
        });
      } finally {
        setBusyItemId(null);
      }
    },
    [csrfToken, setItems, showToast],
  );

  const removeItem = useCallback(
    async (item: CartItem) => {
      const result = await confirm({
        title: '¿Eliminar producto?',
        text: `¿Deseas eliminar "${item.name}" del carrito?`,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        danger: true,
      });

      if (!result.isConfirmed) {
        return;
      }

      setBusyItemId(item.productId);

      try {
        const actionResult = await removeCartItem(item.productId, csrfToken);
        if (!actionResult.success) {
          showToast({
            variant: 'error',
            title: 'No se pudo eliminar',
            message: actionResult.message ?? 'No se pudo eliminar el producto.',
          });
          reloadCartProps();
          return;
        }

        if (actionResult.cart) {
          setItems(actionResult.cart.items);
        } else {
          setItems((current) => current.filter((currentItem) => currentItem.productId !== item.productId));
        }

        dispatchCartCount(actionResult.cartCount);
        showToast({
          variant: 'success',
          title: 'Producto eliminado',
          message: actionResult.message ?? 'Producto eliminado del carrito.',
        });
        reloadCartProps();
      } catch {
        showToast({
          variant: 'error',
          title: 'Error',
          message: 'No se pudo eliminar el producto.',
        });
      } finally {
        setBusyItemId(null);
      }
    },
    [csrfToken, setItems, showToast],
  );

  const clearItems = useCallback(async () => {
    if (items.length === 0) {
      return;
    }

    const result = await confirm({
      title: '¿Vaciar carrito?',
      text: 'Se eliminarán todos los productos del carrito.',
      icon: 'warning',
      confirmButtonText: 'Sí, vaciar',
      cancelButtonText: 'Cancelar',
      danger: true,
    });

    if (!result.isConfirmed) {
      return;
    }

    setIsClearing(true);

    try {
      const actionResult = await clearCart(csrfToken);
      if (!actionResult.success) {
        showToast({
          variant: 'error',
          title: 'No se pudo vaciar',
          message: actionResult.message ?? 'No se pudo vaciar el carrito.',
        });
        reloadCartProps();
        return;
      }

      setItems(actionResult.cart?.items ?? []);
      dispatchCartCount(actionResult.cartCount ?? 0);
      showToast({
        variant: 'success',
        title: 'Carrito vaciado',
        message: actionResult.message ?? 'Carrito vaciado correctamente.',
      });
      reloadCartProps();
    } catch {
      showToast({
        variant: 'error',
        title: 'Error',
        message: 'Ocurrió un error al vaciar el carrito.',
      });
    } finally {
      setIsClearing(false);
    }
  }, [csrfToken, items.length, setItems, showToast]);

  const checkout = useCallback(
    async (paymentMethod: CartPaymentMethod) => {
      if (items.length === 0) {
        showToast({
          variant: 'warning',
          title: 'Carrito vacío',
          message: 'Agregá productos antes de confirmar el pedido.',
        });
        return;
      }

      const result = await confirm({
        title: `¿Confirmar pedido con pago por ${paymentLabels[paymentMethod]}?`,
        text: 'Se enviará tu pedido para retiro en tienda.',
        icon: 'question',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
      });

      if (!result.isConfirmed) {
        return;
      }

      setIsCheckingOut(true);

      try {
        const actionResult = await checkoutCart(csrfToken, paymentMethod);
        if (!actionResult.success) {
          showToast({
            variant: 'error',
            title: 'No se pudo confirmar',
            message: actionResult.message ?? 'No se pudo procesar el pedido.',
          });
          reloadCartProps();
          return;
        }

        setItems(actionResult.cart?.items ?? []);
        dispatchCartCount(actionResult.cartCount ?? 0);
        showToast({
          variant: 'success',
          title: 'Pedido confirmado',
          message: actionResult.message ?? 'Pedido creado exitosamente.',
        });
        reloadCartProps();
      } catch {
        showToast({
          variant: 'error',
          title: 'Error',
          message: 'Ocurrió un error al procesar el pedido.',
        });
      } finally {
        setIsCheckingOut(false);
      }
    },
    [csrfToken, items.length, setItems, showToast],
  );

  return {
    busyItemId,
    isClearing,
    isCheckingOut,
    updateQuantity,
    removeItem,
    clearItems,
    checkout,
  };
}
