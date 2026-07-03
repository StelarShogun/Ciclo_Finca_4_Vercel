"use client";

import Link from "next/link";
import { Star } from "lucide-react";

import type { ProductDetail } from "@/lib/api/client/product";
import { cn } from "@/lib/utils";

function Badge({
  href,
  icon,
  tone,
  children,
}: {
  href?: string;
  icon: string;
  tone: "category" | "subcategory" | "brand" | "stock" | "low" | "out" | "featured" | "new";
  children: React.ReactNode;
}) {
  const tones: Record<string, string> = {
    category: "bg-accent text-brand-medium dark:text-brand-light",
    subcategory: "bg-accent/70 text-brand-medium dark:text-brand-light",
    brand: "border bg-card text-foreground",
    stock: "bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300",
    low: "bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300",
    out: "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300",
    featured: "bg-amber-500 text-white",
    new: "bg-cta text-white",
  };
  const cls = cn("inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold", tones[tone]);
  const body = (
    <>
      <i className={cn(icon, "text-[0.7rem]")} aria-hidden />
      {children}
    </>
  );
  return href ? <Link href={href} className={cn(cls, "transition hover:opacity-80")}>{body}</Link> : <span className={cls}>{body}</span>;
}

function StarsInline({ avg, count }: { avg: number; count: number }) {
  if (count === 0) {
    return <p className="text-sm text-muted-foreground">Aún no hay valoraciones</p>;
  }
  return (
    <div className="flex items-center gap-1.5 text-sm">
      <span className="flex gap-0.5">
        {Array.from({ length: 5 }).map((_, i) => (
          <Star key={i} className={cn("h-4 w-4", i < Math.round(avg) ? "fill-amber-400 text-amber-400" : "text-muted-foreground")} />
        ))}
      </span>
      <span className="font-medium">{avg.toFixed(1)}</span>
      <span className="text-muted-foreground">· {count} reseña{count === 1 ? "" : "s"}</span>
    </div>
  );
}

function StockCard({ p }: { p: ProductDetail["product"] }) {
  const state = p.canBuy && !p.isLowStock ? "available" : p.canBuy ? "low" : "unavailable";
  const styles = {
    available: "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300",
    low: "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-300",
    unavailable: "border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/60 dark:text-red-300",
  }[state];
  const icon = { available: "fas fa-check-circle", low: "fas fa-exclamation-circle", unavailable: "fas fa-times-circle" }[state];
  const title = state === "low" ? "Últimas unidades" : state === "available" ? "En stock" : p.stockLabel;
  const subtitle =
    state === "low"
      ? `Solo quedan ${p.stockCurrent.toLocaleString("es-CR")} disponibles`
      : state === "available"
        ? `${p.stockCurrent.toLocaleString("es-CR")} unidades disponibles`
        : p.stockLabel === "Agotado"
          ? "Este producto no tiene unidades disponibles por ahora."
          : "No está disponible para compra en este momento.";

  return (
    <output className={cn("flex items-start gap-3 rounded-xl border p-3.5", styles)}>
      <i className={cn(icon, "mt-0.5")} aria-hidden />
      <span className="flex flex-col">
        <strong className="text-sm">{title}</strong>
        <span className="text-xs opacity-90">{subtitle}</span>
      </span>
    </output>
  );
}

type PurchasePanelProps = {
  detail: ProductDetail;
  quantity: number;
  isBusy: boolean;
  isFavoritePending: boolean;
  onQuantityChange: (q: number) => void;
  onAddToCart: () => void;
  onToggleFavorite: () => void;
};

/** Panel de compra fiel al ProductPurchasePanel viejo. */
export function PurchasePanel({
  detail,
  quantity,
  isBusy,
  isFavoritePending,
  onQuantityChange,
  onAddToCart,
  onToggleFavorite,
}: PurchasePanelProps) {
  const p = detail.product;
  const stockTone = p.canBuy ? (p.isLowStock ? "low" : "stock") : "out";

  return (
    <aside
      aria-label="Comprar producto"
      className="flex flex-col gap-3.5 rounded-2xl border bg-gradient-to-b from-card to-accent/30 p-5 sm:p-6"
    >
      {/* Badges */}
      <div className="flex flex-wrap gap-1.5" aria-label="Información rápida del producto">
        {detail.taxonomy.parentCategory && (
          <Badge href={`/catalog?category_id=${detail.taxonomy.parentCategory.id}`} icon="fas fa-layer-group" tone="category">
            {detail.taxonomy.parentCategory.name}
          </Badge>
        )}
        {detail.taxonomy.subcategory && (
          <Badge href={`/catalog?category_id=${detail.taxonomy.subcategory.id}`} icon="fas fa-tag" tone="subcategory">
            {detail.taxonomy.subcategory.name}
          </Badge>
        )}
        {detail.primaryBrand && (
          <Badge href={`/catalog?brand_id=${detail.primaryBrand.id}`} icon="fas fa-tag" tone="brand">
            {detail.primaryBrand.name}
          </Badge>
        )}
        <Badge icon="fas fa-check-circle" tone={stockTone}>{p.stockLabel}</Badge>
        {p.isFeatured && <Badge icon="fas fa-star" tone="featured">Destacado</Badge>}
        {detail.isNoveltyProduct && <Badge icon="fas fa-bolt" tone="new">Novedad</Badge>}
      </div>

      <div>
        <h1 className="text-2xl font-bold tracking-tight sm:text-[1.7rem]">{p.name}</h1>
        {p.sku && <p className="mt-0.5 text-xs text-muted-foreground">SKU: {p.sku}</p>}
      </div>

      <StarsInline avg={detail.reviews.averageStars} count={detail.reviews.totalCount} />

      <div>
        <span className="block text-xs font-medium uppercase tracking-wide text-muted-foreground">Precio</span>
        <span className="text-[clamp(1.75rem,4vw,2.35rem)] font-bold leading-tight text-brand-darkest dark:text-brand-lightest">
          {p.priceFormatted}
        </span>
      </div>

      <StockCard p={p} />

      {p.canBuy && (
        <div className="space-y-3">
          {/* Cantidad */}
          <div className="flex items-center gap-3">
            <span className="text-sm font-medium">Cantidad</span>
            <div className="flex items-center overflow-hidden rounded-lg border bg-card">
              <button
                type="button"
                aria-label="Disminuir cantidad"
                disabled={quantity <= 1}
                onClick={() => onQuantityChange(quantity - 1)}
                className="grid h-9 w-9 place-items-center transition hover:bg-accent disabled:opacity-40"
              >
                <i className="fas fa-minus text-xs" aria-hidden />
              </button>
              <span className="w-12 text-center text-sm font-semibold">{quantity}</span>
              <button
                type="button"
                aria-label="Aumentar cantidad"
                disabled={quantity >= p.stockCurrent}
                onClick={() => onQuantityChange(quantity + 1)}
                className="grid h-9 w-9 place-items-center transition hover:bg-accent disabled:opacity-40"
              >
                <i className="fas fa-plus text-xs" aria-hidden />
              </button>
            </div>
            <span className="text-xs text-muted-foreground">máx. {p.stockCurrent}</span>
          </div>

          {/* Acciones */}
          <div className="flex flex-col gap-2">
            <button
              type="button"
              disabled={isBusy}
              onClick={onAddToCart}
              className="flex w-full items-center justify-center gap-2.5 rounded-[10px] bg-brand-medium px-5 py-3 font-bold text-white shadow-[0_4px_14px_rgba(35,83,71,0.22)] transition hover:-translate-y-px hover:bg-[#256428] disabled:cursor-not-allowed disabled:opacity-60"
            >
              <i className={isBusy ? "fas fa-spinner fa-spin" : "fas fa-cart-plus"} aria-hidden />
              Agregar al carrito
            </button>
            <button
              type="button"
              disabled={isFavoritePending}
              aria-pressed={p.isFavorite}
              onClick={onToggleFavorite}
              className={cn(
                "flex w-full items-center justify-center gap-2.5 rounded-[10px] border-[1.5px] px-5 py-2.5 text-sm font-semibold transition disabled:opacity-60",
                p.isFavorite
                  ? "border-cta bg-accent text-cta-strong dark:text-[#2ED27E]"
                  : "border-border text-foreground hover:border-cta hover:text-cta-strong",
              )}
            >
              <i className={cn(p.isFavorite ? "fas" : "far", "fa-heart")} aria-hidden />
              {p.isFavorite ? "En favoritos" : "Agregar a favoritos"}
            </button>
            {detail.whatsappConsultUrl && (
              <a
                href={detail.whatsappConsultUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex w-full items-center justify-center gap-2.5 rounded-[10px] border-[1.5px] border-[#25D366]/60 px-5 py-2.5 text-sm font-semibold text-[#128C7E] transition hover:bg-[#25D366]/10 dark:text-[#25D366]"
              >
                <i className="fab fa-whatsapp text-base" aria-hidden />
                Consultar por WhatsApp
              </a>
            )}
          </div>
        </div>
      )}

      {/* Beneficios de compra */}
      <ul className="mt-1 grid grid-cols-2 gap-2 max-sm:grid-cols-1" aria-label="Beneficios de compra">
        {[
          ["fas fa-store", "Retiro en tienda"],
          ["fas fa-money-bill-wave", "Pago al retirar"],
          ["fas fa-clock", `Reserva por ${detail.orderReservationHours} horas`],
          ["fas fa-boxes", "Stock actualizado"],
          ...(detail.whatsappConsultUrl ? [["fas fa-comment-alt", "Atención por WhatsApp"]] : []),
        ].map(([icon, text]) => (
          <li key={text} className="flex items-center gap-2 text-xs text-muted-foreground">
            <span className="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-accent text-brand-medium dark:text-brand-light" aria-hidden>
              <i className={icon} />
            </span>
            {text}
          </li>
        ))}
      </ul>
    </aside>
  );
}
