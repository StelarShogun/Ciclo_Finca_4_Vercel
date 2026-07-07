"use client";

import Link from "next/link";

import { CartEmptyState } from "@/components/storefront/cart/cart-empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { CartItems } from "@/features/cart/components/CartItems";
import { CheckoutPanel } from "@/features/cart/components/CheckoutPanel";
import { CheckoutSuccess } from "@/features/cart/components/CheckoutSuccess";
import { useCartPage } from "@/features/cart/hooks/useCartPage";

export default function CartPage() {
  const page = useCartPage();
  const { data, isLoading, isError } = page.cartQuery;

  if (page.done) return <CheckoutSuccess result={page.done} />;

  return (
    <section aria-labelledby="cart-page-title">
      <header className="bg-brand-darkest py-10 text-brand-lightest">
        <div className="mx-auto max-w-6xl px-4">
          <p className="text-xs font-medium uppercase tracking-wide text-brand-light">Ciclo Finca 4</p>
          <h1 id="cart-page-title" className="text-3xl font-bold tracking-tight">
            Tu carrito
          </h1>
          <p className="mt-1 text-sm text-brand-lightest/80">
            Revisá cantidades, elegí cómo pagar y confirmá cuando estés listo.
          </p>
        </div>
      </header>

      <div className="mx-auto max-w-6xl px-4 py-6">
        <nav className="mb-4 flex items-center gap-1.5 text-sm text-muted-foreground" aria-label="Migas de pan">
          <Link href="/" className="hover:text-foreground hover:underline">
            Inicio
          </Link>
          <span>/</span>
          <span>Carrito</span>
        </nav>

        {data?.pickupPolicyNotice && (
          <aside className="mb-4 rounded-xl border border-brand-light/50 bg-accent/60 p-4" aria-label="Política de retiro en tienda">
            <div className="mb-1 flex items-center gap-2 font-bold text-brand-medium dark:text-brand-light">
              <i className="fas fa-store" aria-hidden />
              Retiro en tienda
            </div>
            <p className="text-sm text-muted-foreground">{data.pickupPolicyNotice}</p>
          </aside>
        )}

        <div className="rounded-2xl border bg-card/50 p-4 sm:p-5">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-baseline gap-2">
              <span className="text-sm font-semibold">Resumen rápido</span>
              {page.hasItems && (
                <span className="text-sm text-muted-foreground">
                  {page.totalQuantity} {page.totalQuantity === 1 ? "artículo" : "artículos"}
                </span>
              )}
            </div>
            <div className="flex items-center gap-2">
              <Link
                href="/catalog"
                className="inline-flex items-center gap-2 rounded-lg border border-brand-medium/40 px-3.5 py-2 text-sm font-semibold text-brand-medium transition hover:bg-accent dark:text-brand-light"
              >
                <i className="fas fa-bicycle" aria-hidden />
                Seguir comprando
              </Link>
              {page.hasItems && (
                <button
                  type="button"
                  disabled={page.empty.isPending}
                  onClick={() => page.empty.mutate()}
                  className="inline-flex items-center gap-2 rounded-lg border border-red-300 px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:hover:bg-red-950"
                >
                  <i className="fas fa-trash-alt" aria-hidden />
                  {page.empty.isPending ? "Vaciando..." : "Vaciar carrito"}
                </button>
              )}
            </div>
          </div>

          {isLoading ? (
            <Skeleton className="h-64" />
          ) : isError ? (
            <p className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar el carrito.</p>
          ) : page.hasItems && data ? (
            <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
              <CartItems
                items={page.pageItems}
                busyItemId={page.busyItemId}
                pagination={page.pagination}
                onPageChange={page.setPage}
                onQuantityChange={(item, qty) => page.update.mutate({ item, qty })}
                onRemove={(item) => page.remove.mutate(item)}
              />
              <CheckoutPanel
                subtotalFormatted={data.totalFormatted}
                totalFormatted={data.totalFormatted}
                paymentMethod={page.paymentMethod}
                pickupPolicyLine={data.pickupPolicyLine}
                isCheckingOut={page.place.isPending}
                onCheckout={() => page.place.mutate()}
                onPaymentMethodChange={page.setPaymentMethod}
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
