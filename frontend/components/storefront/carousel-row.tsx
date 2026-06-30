"use client";

import { useEffect, useRef } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

import { Button } from "@/components/ui/button";

/**
 * Carrusel horizontal de una sola fila con botones prev/next y auto-scroll
 * opcional a velocidad media (avanza ~una tarjeta cada 3.5s, pausa al pasar el
 * mouse, vuelve al inicio al llegar al final).
 */
export function CarouselRow({
  children,
  autoScroll = true,
  itemClassName = "w-60",
}: {
  children: React.ReactNode;
  autoScroll?: boolean;
  itemClassName?: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const pausedRef = useRef(false);

  function scrollByStep(dir: 1 | -1) {
    const el = ref.current;
    if (!el) return;
    const step = el.querySelector<HTMLElement>("[data-carousel-item]")?.offsetWidth ?? 260;
    el.scrollBy({ left: dir * (step + 16), behavior: "smooth" });
  }

  useEffect(() => {
    if (!autoScroll) return;
    const el = ref.current;
    if (!el) return;
    const id = setInterval(() => {
      if (pausedRef.current) return;
      const step = el.querySelector<HTMLElement>("[data-carousel-item]")?.offsetWidth ?? 260;
      const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 8;
      if (atEnd) el.scrollTo({ left: 0, behavior: "smooth" });
      else el.scrollBy({ left: step + 16, behavior: "smooth" });
    }, 3500); // velocidad media
    return () => clearInterval(id);
  }, [autoScroll]);

  return (
    <div className="group relative">
      <Button
        variant="outline"
        size="icon"
        aria-label="Anterior"
        className="absolute -left-3 top-1/2 z-10 hidden h-9 w-9 -translate-y-1/2 rounded-full opacity-0 shadow transition group-hover:opacity-100 sm:flex"
        onClick={() => scrollByStep(-1)}
      >
        <ChevronLeft className="h-4 w-4" />
      </Button>
      <div
        ref={ref}
        onMouseEnter={() => (pausedRef.current = true)}
        onMouseLeave={() => (pausedRef.current = false)}
        className="flex gap-4 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
      >
        {Array.isArray(children)
          ? children.map((c, i) => (
              <div key={i} data-carousel-item className={`shrink-0 ${itemClassName}`}>
                {c}
              </div>
            ))
          : children}
      </div>
      <Button
        variant="outline"
        size="icon"
        aria-label="Siguiente"
        className="absolute -right-3 top-1/2 z-10 hidden h-9 w-9 -translate-y-1/2 rounded-full opacity-0 shadow transition group-hover:opacity-100 sm:flex"
        onClick={() => scrollByStep(1)}
      >
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  );
}
