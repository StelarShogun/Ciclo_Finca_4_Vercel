"use client";

import { useEffect, useState } from "react";

import { storeMediaUrl } from "@/lib/api/client/catalog";
import type { ProductDetailProduct } from "@/lib/api/client/product";
import { cn } from "@/lib/utils";

/**
 * Galería fiel a la vieja: carrusel con flechas, navegación por teclado y
 * miniaturas con estado activo.
 */
export function ProductGallery({ product }: { product: ProductDetailProduct }) {
  const slides = product.carouselSlides;
  const slideCount = slides.length;
  const [current, setCurrent] = useState(0);

  useEffect(() => {
    if (slideCount < 2) return;
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "ArrowLeft") setCurrent((s) => Math.max(0, s - 1));
      if (e.key === "ArrowRight") setCurrent((s) => Math.min(slideCount - 1, s + 1));
    }
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [slideCount]);

  if (product.showImagePlaceholder || slideCount === 0) {
    return (
      <div className="grid aspect-square w-full place-items-center rounded-2xl border bg-muted text-muted-foreground">
        <i className={cn(product.placeholderIconClass ?? "fas fa-box", "text-6xl")} aria-hidden />
      </div>
    );
  }

  return (
    <div>
      {/* Carrusel principal */}
      <div className="relative overflow-hidden rounded-2xl border bg-muted">
        <div className="flex transition-transform duration-300" style={{ transform: `translateX(-${current * 100}%)` }}>
          {slides.map((slide, i) => {
            const img = storeMediaUrl(slide.desktopWebp ?? slide.fallback);
            return (
              <div key={i} className="aspect-square w-full shrink-0">
                {img && (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={img} alt={i === 0 ? product.name : ""} className="h-full w-full object-cover" loading={i === 0 ? "eager" : "lazy"} />
                )}
              </div>
            );
          })}
        </div>

        {slideCount > 1 && (
          <>
            <button
              type="button"
              aria-label="Imagen anterior"
              disabled={current === 0}
              onClick={() => setCurrent((s) => Math.max(0, s - 1))}
              className="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-medium shadow transition hover:bg-white disabled:opacity-40 dark:bg-[#071F1F]/90 dark:text-brand-light"
            >
              <i className="fas fa-chevron-left" aria-hidden />
            </button>
            <button
              type="button"
              aria-label="Imagen siguiente"
              disabled={current >= slideCount - 1}
              onClick={() => setCurrent((s) => Math.min(slideCount - 1, s + 1))}
              className="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-medium shadow transition hover:bg-white disabled:opacity-40 dark:bg-[#071F1F]/90 dark:text-brand-light"
            >
              <i className="fas fa-chevron-right" aria-hidden />
            </button>
          </>
        )}
      </div>

      {/* Miniaturas */}
      {slideCount > 1 && (
        <ul className="mt-3 flex gap-2 overflow-x-auto" aria-label="Miniaturas del producto">
          {slides.map((slide, i) => {
            const thumb = storeMediaUrl(slide.mobileWebp ?? slide.fallback);
            return (
              <li key={i}>
                <button
                  type="button"
                  aria-label={`Ver imagen ${i + 1}`}
                  aria-current={i === current}
                  onClick={() => setCurrent(i)}
                  className={cn(
                    "h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition",
                    i === current ? "border-brand-medium dark:border-brand-light" : "border-transparent opacity-70 hover:opacity-100",
                  )}
                >
                  {thumb && (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={thumb} alt="" className="h-full w-full object-cover" />
                  )}
                </button>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
