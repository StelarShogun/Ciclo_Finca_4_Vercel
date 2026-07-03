"use client";

import { useRef } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

import type { CatalogSpotlightItem } from "@/lib/api/client/catalog";
import { ProductCard } from "@/components/storefront/product-card";
import { Button } from "@/components/ui/button";

/**
 * Fila "Destacados y novedades" del catálogo, fiel al carrusel viejo
 * (CatalogSpotlightCarousel): solo se muestra en la primera página y sin
 * filtros activos — el gating lo hace la página.
 * ponytail: scroll-snap nativo con flechas en vez de Swiper; autoplay fuera.
 */
export function CatalogSpotlight({ items }: { items: CatalogSpotlightItem[] }) {
  const trackRef = useRef<HTMLDivElement>(null);

  if (items.length === 0) return null;

  const scrollBy = (dir: 1 | -1) =>
    trackRef.current?.scrollBy({ left: dir * trackRef.current.clientWidth * 0.8, behavior: "smooth" });

  return (
    <section aria-labelledby="catalog-spotlight-heading" className="mb-6">
      <header className="mb-3 flex items-end justify-between gap-3">
        <div>
          <h2 id="catalog-spotlight-heading" className="text-lg font-semibold tracking-tight">
            Destacados y novedades
          </h2>
          <p className="text-sm text-muted-foreground">
            Productos recomendados y recién incorporados al catálogo.
          </p>
        </div>
        <div className="hidden gap-1.5 sm:flex">
          <Button variant="outline" size="icon" className="h-8 w-8 rounded-full" aria-label="Anterior" onClick={() => scrollBy(-1)}>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button variant="outline" size="icon" className="h-8 w-8 rounded-full" aria-label="Siguiente" onClick={() => scrollBy(1)}>
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </header>
      <div
        ref={trackRef}
        className="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:thin]"
        role="region"
        aria-label="Productos destacados y novedades del catálogo"
      >
        {items.map((item, index) => (
          <div
            key={`${item.kind}-${item.product.id}`}
            className="w-[46%] shrink-0 snap-start sm:w-[30%] xl:w-[23%]"
            aria-label={`${index + 1} de ${items.length}: ${item.product.name}`}
          >
            <ProductCard product={item.product} />
          </div>
        ))}
      </div>
    </section>
  );
}
