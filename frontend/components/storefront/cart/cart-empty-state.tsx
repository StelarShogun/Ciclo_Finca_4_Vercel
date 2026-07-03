"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import { getHome } from "@/lib/api/client/home";
import { storeMediaUrl } from "@/lib/api/client/catalog";

/** Carrito vacío con productos destacados, fiel al CartEmptyState viejo. */
export function CartEmptyState() {
  const { data } = useQuery({ queryKey: ["home"], queryFn: getHome, staleTime: 60_000 });
  const featured = data?.featuredProducts ?? [];

  return (
    <div>
      <div className="flex flex-col items-center gap-3 py-12 text-center">
        <span className="grid h-16 w-16 place-items-center rounded-full bg-accent text-2xl text-brand-medium dark:text-brand-light" aria-hidden>
          <i className="fas fa-cart-shopping" />
        </span>
        <h2 className="text-xl font-bold">Tu carrito está vacío</h2>
        <p className="text-sm text-muted-foreground">Explorá el catálogo y agregá productos para armar tu solicitud.</p>
        <div className="mt-2 flex flex-wrap justify-center gap-2.5">
          <Link
            href="/catalog"
            className="inline-flex items-center gap-2 rounded-[10px] bg-brand-medium px-5 py-2.5 font-semibold text-white transition hover:bg-[#256428]"
          >
            <i className="fas fa-bicycle" aria-hidden />
            Ir al catálogo
          </Link>
          <Link
            href="/catalog"
            className="inline-flex items-center gap-2 rounded-[10px] border border-brand-medium/40 px-5 py-2.5 font-semibold text-brand-medium transition hover:bg-accent dark:text-brand-light"
          >
            <i className="fas fa-star" aria-hidden />
            Ver destacados
          </Link>
        </div>
        <p className="mt-1 text-sm">
          <Link href="/" className="text-muted-foreground underline underline-offset-2 hover:text-foreground">Volver al inicio</Link>
        </p>
      </div>

      {featured.length > 0 && (
        <div aria-labelledby="cart-empty-featured-title" className="border-t pt-6">
          <h3 id="cart-empty-featured-title" className="mb-4 flex items-center gap-2 text-base font-bold">
            <i className="fas fa-star text-amber-500" aria-hidden />
            Productos destacados
          </h3>
          <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {featured.slice(0, 8).map((product) => {
              const img = storeMediaUrl(product.image.desktopWebp ?? product.image.fallback);
              return (
                <li key={product.id}>
                  <Link
                    href={`/product/${product.id}`}
                    className="block overflow-hidden rounded-xl border bg-card transition-shadow hover:shadow-md"
                  >
                    <span className="block aspect-square overflow-hidden bg-muted">
                      {img && !product.image.usesPlaceholder ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={img} alt={product.name} className="h-full w-full object-cover" loading="lazy" />
                      ) : (
                        <span className="flex h-full w-full items-center justify-center text-muted-foreground">
                          <i className={product.image.placeholderIconClass ?? "fas fa-box"} aria-hidden />
                        </span>
                      )}
                    </span>
                    <span className="block p-2.5">
                      <span className="line-clamp-1 text-sm font-medium">{product.name}</span>
                      <span className="text-sm font-semibold text-brand-medium dark:text-brand-light">{product.priceFormatted}</span>
                    </span>
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
      )}
    </div>
  );
}
