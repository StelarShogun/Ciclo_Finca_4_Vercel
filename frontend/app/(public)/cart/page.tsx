"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { CheckCircle2 } from "lucide-react";

import {
  checkout,
  clearCart,
  getCart,
  removeCartItem,
  updateCartItem,
  type CartItem,
  type CheckoutResult,
} from "@/lib/api/client/cart";
import { CartItemRow } from "@/components/storefront/cart/cart-item-row";
import { CartSummary, type CartPaymentMethod } from "@/components/storefront/cart/cart-summary";
import { CartEmptyState } from "@/components/storefront/cart/cart-empty-state";
import { ListPagination } from "@/components/shared/list-pagination";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const PER_PAGE = 10; // mismo tamaño de página que el carrito viejo

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function CartPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();

  // El carrito exige sesión de cliente, como en la app vieja.
  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/cart");
    }
  }, [me.isError, me.error, me.data, router]);

  const [page, setPage] = useState(1);
  const [paymentMethod, setPaymentMethod] = useState<CartPaymentMethod>("cash");
  const [busyItemId, setBusyItemId] = useState<string | null>(null);
  const [done, setDone] = useState<CheckoutResult | null>(null);

  const { data, isLoading, isError } = useQuery({ queryKey: ["cart"], queryFn: getCart });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["cart"] });

  const update = useMutation({
    mutationFn: ({ item, qty }: { item: CartItem; qty: number }) => {
      setBusyItemId(item.productId);
      return updateCartItem(item.productId, qty);
    },
    onSuccess: invalidate,
    onError: (e) => toast.error(errMsg(e, "No se pudo actualizar el carrito.")),
    onSettled: () => setBusyItemId(null),
  });
  const remove = useMutation({
    mutationFn: (item: CartItem) => {
      setBusyItemId(item.productId);
      return removeCartItem(item.productId);
    },
    onSuccess: () => { toast.success("Producto quitado"); invalidate(); },
    onError: (e) => toast.error(errMsg(e, "No se pudo quitar el producto.")),
    onSettled: () => setBusyItemId(null),
  });
  const empty = useMutation({
    mutationFn: clearCart,
    onSuccess: () => { toast.success("Carrito vaciado"); invalidate(); },
    onError: (e) => toast.error(errMsg(e, "No se pudo vaciar el carrito.")),
  });
  const place = useMutation({
    mutationFn: () => checkout(paymentMethod),
    onSuccess: (res) => { setDone(res); invalidate(); },
    onError: (e) => toast.error(errMsg(e, "No fue posible procesar el pedido.")),
  });

  const items = useMemo(() => data?.items ?? [], [data]);
  const totalQuantity = useMemo(() => items.reduce((sum, i) => sum + i.quantity, 0), [items]);
  const lastPage = Math.max(1, Math.ceil(items.length / PER_PAGE));
  const safePage = Math.min(page, lastPage);
  const pageItems = items.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE);
  const hasItems = items.length > 0;

  if (done) {
    return (
      <div className="mx-auto max-w-md px-4 py-16">
        <Card className="border-t-4 border-t-[#235347]">
          <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
            <CheckCircle2 className="h-12 w-12 text-[#235347]" />
            <h1 className="text-xl font-semibold">¡Pedido confirmado!</h1>
            <p className="text-sm text-muted-foreground">
              Factura <span className="font-medium text-foreground">{done.invoice_number}</span>. Te avisaremos cuando esté listo para retirar.
            </p>
            <div className="flex gap-2">
              <Button asChild variant="outline"><Link href="/catalog">Seguir comprando</Link></Button>
              <Button asChild className="bg-[#235347] hover:bg-[#1a3f37]"><Link href="/invoices">Mis facturas</Link></Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <section aria-labelledby="cart-page-title">
      {/* Hero, fiel al cart-hero viejo */}
      <header className="bg-[#051F20] py-10 text-[#DAF1DE]">
        <div className="mx-auto max-w-6xl px-4">
          <p className="text-xs font-medium uppercase tracking-wide text-[#8EB69B]">Ciclo Finca 4</p>
          <h1 id="cart-page-title" className="text-3xl font-bold tracking-tight">Tu carrito</h1>
          <p className="mt-1 text-sm text-[#DAF1DE]/80">Revisá cantidades, elegí cómo pagar y confirmá cuando estés listo.</p>
        </div>
      </header>

      <div className="mx-auto max-w-6xl px-4 py-6">
        {/* Migas de pan */}
        <nav className="mb-4 flex items-center gap-1.5 text-sm text-muted-foreground" aria-label="Migas de pan">
          <Link href="/" className="hover:text-foreground hover:underline">Inicio</Link>
          <span>/</span>
          <span>Carrito</span>
        </nav>

        {/* Política de retiro en tienda */}
        {data?.pickupPolicyNotice && (
          <aside className="mb-4 rounded-xl border border-[#8EB69B]/50 bg-accent/60 p-4" aria-label="Política de retiro en tienda">
            <div className="mb-1 flex items-center gap-2 font-bold text-[#235347] dark:text-[#8EB69B]">
              <i className="fas fa-store" aria-hidden />
              Retiro en tienda
            </div>
            <p className="text-sm text-muted-foreground">{data.pickupPolicyNotice}</p>
          </aside>
        )}

        <div className="rounded-2xl border bg-card/50 p-4 sm:p-5">
          {/* Toolbar */}
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-baseline gap-2">
              <span className="text-sm font-semibold">Resumen rápido</span>
              {hasItems && (
                <span className="text-sm text-muted-foreground">
                  {totalQuantity} {totalQuantity === 1 ? "artículo" : "artículos"}
                </span>
              )}
            </div>
            <div className="flex items-center gap-2">
              <Link
                href="/catalog"
                className="inline-flex items-center gap-2 rounded-lg border border-[#235347]/40 px-3.5 py-2 text-sm font-semibold text-[#235347] transition hover:bg-accent dark:text-[#8EB69B]"
              >
                <i className="fas fa-bicycle" aria-hidden />
                Seguir comprando
              </Link>
              {hasItems && (
                <button
                  type="button"
                  disabled={empty.isPending}
                  onClick={() => empty.mutate()}
                  className="inline-flex items-center gap-2 rounded-lg border border-red-300 px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:hover:bg-red-950"
                >
                  <i className="fas fa-trash-alt" aria-hidden />
                  {empty.isPending ? "Vaciando..." : "Vaciar carrito"}
                </button>
              )}
            </div>
          </div>

          {isLoading ? (
            <Skeleton className="h-64" />
          ) : isError ? (
            <p className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar el carrito.</p>
          ) : hasItems && data ? (
            <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
              <div>
                <ul className="space-y-3" aria-label="Productos en el carrito">
                  {pageItems.map((item) => (
                    <CartItemRow
                      key={item.productId}
                      item={item}
                      isBusy={busyItemId === item.productId}
                      onQuantityChange={(it, qty) => update.mutate({ item: it, qty })}
                      onRemove={(it) => remove.mutate(it)}
                    />
                  ))}
                </ul>
                <ListPagination
                  pagination={{ currentPage: safePage, lastPage, total: items.length, perPage: PER_PAGE }}
                  onPageChange={setPage}
                  label="carrito"
                />
              </div>

              <CartSummary
                subtotalFormatted={data.totalFormatted}
                totalFormatted={data.totalFormatted}
                paymentMethod={paymentMethod}
                pickupPolicyLine={data.pickupPolicyLine}
                isCheckingOut={place.isPending}
                onCheckout={() => place.mutate()}
                onPaymentMethodChange={setPaymentMethod}
              />
            </div>
          ) : (
            <CartEmptyState />
          )}
        </div>
      </div>
    </section>
  );
}
