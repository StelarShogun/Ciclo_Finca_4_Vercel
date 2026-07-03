"use client";

import Link from "next/link";

import { storeMediaUrl } from "@/lib/api/client/catalog";
import type { CartItem } from "@/lib/api/client/cart";

type CartItemRowProps = {
  item: CartItem;
  isBusy?: boolean;
  onQuantityChange: (item: CartItem, quantity: number) => void;
  onRemove: (item: CartItem) => void;
};

/** Fila de producto en el carrito, fiel al CartItemRow viejo. */
export function CartItemRow({ item, isBusy = false, onQuantityChange, onRemove }: CartItemRowProps) {
  const productUrl = `/product/${item.productId}`;
  const img = storeMediaUrl(item.image.desktopWebp ?? item.image.fallback);

  return (
    <li className="flex flex-wrap items-center gap-4 rounded-xl border bg-card p-3.5 sm:flex-nowrap">
      <Link href={productUrl} className="block h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-muted" tabIndex={-1} aria-hidden>
        {img && !item.image.usesPlaceholder ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={img} alt="" className="h-full w-full object-cover" />
        ) : (
          <span className="flex h-full w-full items-center justify-center text-muted-foreground">
            <i className="fas fa-box text-xl" aria-hidden />
          </span>
        )}
      </Link>

      <div className="min-w-0 flex-1">
        <h3 className="text-sm font-semibold">
          <Link href={productUrl} className="hover:underline">{item.name}</Link>
        </h3>
        <div className="mt-1 flex flex-wrap items-center gap-2.5">
          <span className="text-sm font-medium text-brand-medium dark:text-brand-light">
            {item.unitPriceFormatted} <span className="text-xs font-normal text-muted-foreground">c/u</span>
          </span>
          <span className="inline-flex items-center gap-1.5 rounded-full bg-accent px-2 py-0.5 text-[11px] font-medium text-brand-medium dark:text-brand-light" title="Stock disponible en tienda">
            <i className="fas fa-boxes-stacked" aria-hidden />
            {item.stockCurrent} disponibles
          </span>
        </div>
      </div>

      {/* Selector de cantidad − input + */}
      <div className="flex flex-col items-center gap-1" aria-label="Cantidad">
        <span className="text-[11px] font-medium text-muted-foreground" id={`qty-label-${item.productId}`}>Cantidad</span>
        <div className="flex items-center overflow-hidden rounded-lg border">
          <button
            type="button"
            aria-label="Disminuir cantidad"
            disabled={isBusy || item.quantity <= 1}
            onClick={() => onQuantityChange(item, item.quantity - 1)}
            className="grid h-8 w-8 place-items-center text-sm transition-colors hover:bg-accent disabled:opacity-40"
          >
            <i className="fas fa-minus" aria-hidden />
          </button>
          <input
            type="number"
            min={1}
            max={item.stockCurrent}
            value={item.quantity}
            aria-labelledby={`qty-label-${item.productId}`}
            disabled={isBusy}
            onChange={(e) => {
              const qty = Number(e.currentTarget.value);
              if (Number.isFinite(qty) && qty >= 1 && qty <= item.stockCurrent) onQuantityChange(item, qty);
            }}
            className="h-8 w-12 border-x bg-transparent text-center text-sm [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
          />
          <button
            type="button"
            aria-label="Aumentar cantidad"
            disabled={isBusy || item.quantity >= item.stockCurrent}
            onClick={() => onQuantityChange(item, item.quantity + 1)}
            className="grid h-8 w-8 place-items-center text-sm transition-colors hover:bg-accent disabled:opacity-40"
          >
            <i className="fas fa-plus" aria-hidden />
          </button>
        </div>
      </div>

      <div className="flex items-center gap-3">
        <div className="text-right">
          <span className="block text-[11px] font-medium text-muted-foreground">Subtotal</span>
          <span className="text-sm font-bold text-brand-medium dark:text-brand-light">{item.subtotalFormatted}</span>
        </div>
        <button
          type="button"
          title="Quitar del carrito"
          aria-label={`Quitar ${item.name} del carrito`}
          disabled={isBusy}
          onClick={() => onRemove(item)}
          className="grid h-9 w-9 place-items-center rounded-lg border border-red-200 text-red-600 transition-colors hover:bg-red-50 disabled:opacity-40 dark:border-red-900 dark:hover:bg-red-950"
        >
          <i className="fas fa-trash-alt" aria-hidden />
        </button>
      </div>
    </li>
  );
}
