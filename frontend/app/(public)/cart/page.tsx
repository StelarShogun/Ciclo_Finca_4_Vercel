"use client";

import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Minus, Plus, ShoppingCart, Trash2 } from "lucide-react";

import {
  clearCart,
  getCart,
  removeCartItem,
  updateCartItem,
} from "@/lib/api/client/cart";
import { storeMediaUrl } from "@/lib/api/client/catalog";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function CartPage() {
  const queryClient = useQueryClient();
  const { data, isLoading, isError } = useQuery({ queryKey: ["cart"], queryFn: getCart });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["cart"] });

  const update = useMutation({
    mutationFn: ({ id, qty }: { id: number; qty: number }) => updateCartItem(id, qty),
    onSuccess: invalidate,
    onError: (e) => toast.error(errMsg(e, "No se pudo actualizar el carrito.")),
  });
  const remove = useMutation({
    mutationFn: (id: number) => removeCartItem(id),
    onSuccess: () => { toast.success("Producto quitado"); invalidate(); },
    onError: (e) => toast.error(errMsg(e, "No se pudo quitar el producto.")),
  });
  const empty = useMutation({
    mutationFn: clearCart,
    onSuccess: () => { toast.success("Carrito vaciado"); invalidate(); },
  });

  if (isLoading) {
    return <div className="mx-auto max-w-4xl px-4 py-12"><Skeleton className="h-64" /></div>;
  }

  const items = data?.items ?? [];

  return (
    <div className="mx-auto max-w-4xl px-4 py-12">
      <h1 className="mb-6 text-2xl font-semibold tracking-tight">Mi carrito</h1>

      {isError ? (
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar el carrito.</CardContent></Card>
      ) : items.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
            <ShoppingCart className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">Tu carrito está vacío.</p>
            <Button asChild className="bg-[#235347] hover:bg-[#1a3f37]">
              <Link href="/catalog">Ver catálogo</Link>
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
          <div className="space-y-3">
            {items.map((item) => {
              const img = storeMediaUrl(item.image.desktopWebp ?? item.image.fallback);
              return (
                <Card key={item.productId}>
                  <CardContent className="flex items-center gap-4 p-3">
                    <Link href={`/product/${item.productId}`} className="h-16 w-16 shrink-0 overflow-hidden rounded bg-muted">
                      {img && !item.image.usesPlaceholder ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={img} alt={item.name} className="h-full w-full object-cover" />
                      ) : (
                        <div className="flex h-full w-full items-center justify-center">🚲</div>
                      )}
                    </Link>
                    <div className="min-w-0 flex-1">
                      <Link href={`/product/${item.productId}`} className="line-clamp-1 text-sm font-medium hover:underline">
                        {item.name}
                      </Link>
                      <p className="text-sm text-muted-foreground">{item.unitPriceFormatted}</p>
                    </div>
                    <div className="flex items-center rounded-md border">
                      <Button variant="ghost" size="icon" className="h-8 w-8" disabled={update.isPending || item.quantity <= 1}
                        onClick={() => update.mutate({ id: item.productId, qty: item.quantity - 1 })}>
                        <Minus className="h-3 w-3" />
                      </Button>
                      <span className="w-8 text-center text-sm">{item.quantity}</span>
                      <Button variant="ghost" size="icon" className="h-8 w-8" disabled={update.isPending || item.quantity >= item.stockCurrent}
                        onClick={() => update.mutate({ id: item.productId, qty: item.quantity + 1 })}>
                        <Plus className="h-3 w-3" />
                      </Button>
                    </div>
                    <span className="w-24 text-right text-sm font-medium">{item.subtotalFormatted}</span>
                    <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive" disabled={remove.isPending}
                      onClick={() => remove.mutate(item.productId)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </CardContent>
                </Card>
              );
            })}
            <Button variant="ghost" size="sm" className="text-muted-foreground" onClick={() => empty.mutate()} disabled={empty.isPending}>
              Vaciar carrito
            </Button>
          </div>

          <Card className="h-fit">
            <CardContent className="space-y-4 p-5">
              <div className="flex justify-between text-base font-semibold">
                <span>Total</span>
                <span className="text-[#235347]">{data?.totalFormatted}</span>
              </div>
              {data?.pickupPolicyLine && (
                <p className="text-xs text-muted-foreground">{data.pickupPolicyLine}</p>
              )}
              <Button asChild className="w-full bg-[#235347] hover:bg-[#1a3f37]">
                <Link href="/checkout">Continuar al pago</Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
