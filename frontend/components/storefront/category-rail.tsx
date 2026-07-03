"use client";

import Link from "next/link";

import type { CatalogCategoryNav } from "@/lib/api/client/catalog";
import { cn } from "@/lib/utils";

/**
 * Rail de categorías fiel al Inertia: estrecho (solo íconos), se expande al
 * hover mostrando las etiquetas, y cada categoría con hijos muestra un flyout a
 * la derecha con sus subcategorías. Se superpone sobre el contenido (no empuja).
 */
export function CategoryRail({
  categories,
  activeCategoryId,
}: {
  categories: CatalogCategoryNav[];
  activeCategoryId: string | null;
}) {
  return (
    <>
    {/* Móvil: pills horizontales, como el catálogo viejo */}
    <nav aria-label="Categorías del catálogo" className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden">
      <Link
        href="/catalog"
        className={cn(
          "shrink-0 whitespace-nowrap rounded-full border border-brand-light/40 bg-card px-3.5 py-1.5 text-xs font-semibold",
          !activeCategoryId && "border-brand-medium bg-accent text-brand-medium dark:text-brand-light",
        )}
      >
        Todos
      </Link>
      {categories.map((c) => {
        const active = String(c.id) === activeCategoryId || c.children.some((ch) => String(ch.id) === activeCategoryId);
        return (
          <Link
            key={c.id}
            href={`/catalog?category_id=${c.id}`}
            className={cn(
              "shrink-0 whitespace-nowrap rounded-full border border-brand-light/40 bg-card px-3.5 py-1.5 text-xs font-semibold",
              active && "border-brand-medium bg-accent text-brand-medium dark:text-brand-light",
            )}
          >
            {c.icon && <i className={cn(c.icon, "mr-1.5")} aria-hidden />}
            {c.name}
          </Link>
        );
      })}
    </nav>

    {/* Desktop: rail sticky que acompaña el scroll; z-30 para volar sobre filtros y cards */}
    <div className="sticky top-20 z-30 hidden w-14 shrink-0 self-start lg:block">
      <nav
        aria-label="Categorías del catálogo"
        className="group/rail absolute left-0 top-0 z-30 flex max-h-[calc(100dvh-7rem)] w-14 flex-col gap-2 overflow-visible rounded-[22px] border border-brand-light/40 bg-card/90 p-2.5 shadow-lg backdrop-blur transition-[width] duration-200 hover:w-56"
      >
        <div className="flex items-center gap-2 border-b border-brand-light/30 pb-2.5">
          <span className="grid h-9 w-9 shrink-0 place-items-center rounded-[14px] bg-brand-dark text-white shadow">
            <i className="fas fa-bars" aria-hidden />
          </span>
          <span className="hidden whitespace-nowrap text-[11px] font-extrabold uppercase tracking-[0.12em] text-brand-darkest group-hover/rail:inline dark:text-brand-lightest">
            Categorías
          </span>
        </div>

        <div className="flex flex-1 flex-col gap-0.5 overflow-visible">
          <Link
            href="/catalog"
            title="Todos los productos"
            className={cn(
              "flex min-h-11 items-center gap-3 rounded-[15px] px-3 text-sm font-bold text-brand-darkest transition-colors hover:bg-accent dark:text-brand-lightest",
              "justify-center group-hover/rail:justify-start",
              !activeCategoryId && "bg-accent text-brand-medium dark:text-brand-light",
            )}
          >
            <i className="fas fa-list w-5 text-center" aria-hidden />
            <span className="hidden group-hover/rail:inline">Todos los productos</span>
          </Link>

          {categories.map((c) => {
            const parentActive = String(c.id) === activeCategoryId;
            const childActive = c.children.some((ch) => String(ch.id) === activeCategoryId);
            return (
              <div key={c.id} className="group/item relative">
                <Link
                  href={`/catalog?category_id=${c.id}`}
                  title={c.name}
                  className={cn(
                    "flex min-h-11 items-center gap-3 rounded-[15px] px-3 text-sm font-bold text-brand-darkest transition-colors hover:bg-accent dark:text-brand-lightest",
                    "justify-center group-hover/rail:justify-start",
                    (parentActive || childActive) && "bg-accent text-brand-medium dark:text-brand-light",
                  )}
                >
                  <i className={cn(c.icon || "fas fa-tag", "w-5 text-center")} aria-hidden />
                  <span className="hidden truncate group-hover/rail:inline">{c.name}</span>
                </Link>

                {/* Flyout de subcategorías */}
                {c.children.length > 0 && (
                  <div className="invisible absolute left-full top-0 z-50 ml-2 min-w-52 max-w-[280px] translate-x-[-4px] rounded-lg border bg-popover p-3 text-popover-foreground opacity-0 shadow-xl transition-all duration-200 group-hover/item:visible group-hover/item:translate-x-0 group-hover/item:opacity-100">
                    {/* puente invisible para no perder el hover */}
                    <span className="absolute -left-2.5 top-0 bottom-0 w-2.5" aria-hidden />
                    <p className="mb-2 text-[15px] font-bold">{c.name}</p>
                    <ul className="space-y-1">
                      {c.children.map((ch) => (
                        <li key={ch.id}>
                          <Link
                            href={`/catalog?category_id=${ch.id}`}
                            className={cn(
                              "block rounded-md px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-accent hover:text-brand-medium dark:hover:text-brand-light",
                              String(ch.id) === activeCategoryId && "bg-brand-medium font-semibold text-white",
                            )}
                          >
                            {ch.name}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </nav>
    </div>
    </>
  );
}
