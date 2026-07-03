"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import {
  ArrowRight,
  CheckCircle2,
  ClipboardList,
  PackageCheck,
  ShieldCheck,
  Star,
  Store,
  UserCog,
  Warehouse,
  Wrench,
} from "lucide-react";

import { getHome, type HomeProduct } from "@/lib/api/client/home";
import { useMe } from "@/lib/auth/use-me";
import { ProductCard } from "@/components/storefront/product-card";
import { CarouselRow } from "@/components/storefront/carousel-row";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

function toCardProduct(p: HomeProduct) {
  return {
    ...p,
    isFeatured: false,
    isNew: false,
    isFavorite: false,
    category: p.category ? { id: "", name: p.category } : null,
    parentCategory: null,
    brands: [],
    image: { ...p.image },
  };
}

const TRUST = [
  { icon: UserCog, title: "Atención experta", text: "Acompañamiento personalizado" },
  { icon: Wrench, title: "Taller propio", text: "Preparación técnica incluida" },
  { icon: Star, title: "4.9/5", text: "Satisfacción de servicio" },
];

const BENEFITS = [
  { icon: Wrench, title: "Taller propio", text: "Armado, ajuste y revisión técnica antes de retirar." },
  { icon: UserCog, title: "Asesoría personalizada", text: "Te guiamos en elección y compatibilidad." },
  { icon: Warehouse, title: "Inventario y disponibilidad", text: "Stock real actualizado en el catálogo." },
  { icon: ShieldCheck, title: "Soporte post-retiro", text: "Acompañamiento después de tu compra." },
];

const TESTIMONIALS = [
  { name: "Andrés M.", context: "Ciclista de montaña", text: "Excelente asesoría, me ayudaron a elegir la bici ideal y la dejaron lista para rodar." },
  { name: "Carolina R.", context: "Cliente recurrente", text: "El retiro en tienda fue rapidísimo y el taller dejó todo perfecto." },
  { name: "José P.", context: "Triatleta", text: "Componentes originales y atención de primera. Recomendados al 100%." },
];

export default function HomePage() {
  const me = useMe();
  const { data, isLoading } = useQuery({ queryKey: ["home"], queryFn: getHome });
  const isGuest = me.data?.type !== "client";

  return (
    <div>
      {/* 1. Hero */}
      {/* Réplica del hero viejo: overlay negro neutro (no verde), más imagen
          visible a la derecha, badge claro y subtítulo + descripción. */}
      <section className="relative flex min-h-[min(85vh,720px)] items-center overflow-hidden text-white">
        <div
          className="absolute inset-0 bg-cover bg-center"
          style={{ backgroundImage: "url('/hero/hero-downhill-1920.webp')" }}
          aria-hidden
        />
        <div
          className="absolute inset-0"
          style={{
            background:
              "linear-gradient(90deg, rgba(0,0,0,0.82) 0%, rgba(0,0,0,0.58) 55%, rgba(0,0,0,0.42) 100%), linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.6) 100%)",
          }}
          aria-hidden
        />
        <div className="relative mx-auto w-full max-w-7xl px-4 py-16">
          <div className="max-w-2xl">
            <span className="inline-flex items-center gap-2 rounded-md border border-white/[0.28] bg-white/[0.12] px-4 py-2 text-sm font-semibold text-white">
              Atención ciclista especializada en tienda
            </span>
            <h1 className="mt-5 text-4xl font-bold leading-[1.08] tracking-tight [text-shadow:0_2px_32px_rgba(0,0,0,0.5),0_1px_4px_rgba(0,0,0,0.3)] sm:text-5xl">
              {data?.hero.title ?? "Equípate para rodar"}{" "}
              <strong className="font-bold text-[#c8e6c9]">
                {data?.hero.emphasis ?? "con asesoría real en tienda"}
              </strong>
            </h1>
            <div className="mt-5 h-1 w-16 rounded-full bg-cta" aria-hidden />
            <p className="mt-5 max-w-xl text-lg text-white/95">
              {data?.hero.subtitle ?? "Bicicletas, componentes y accesorios listos para encargo con retiro rápido."}
            </p>
            <p className="mt-2 max-w-xl text-base text-white/80">
              {data?.hero.description ?? "Te guiamos en elección, ajuste y preparación para que retires con confianza."}
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Button asChild size="lg" className="bg-cta hover:bg-cta-strong">
                <Link href="/catalog">Ver catálogo <ArrowRight className="h-4 w-4" /></Link>
              </Button>
              <Button asChild size="lg" variant="outline" className="border-white/40 bg-transparent text-white hover:bg-white/10 hover:text-white">
                <a href="#como-funciona">Cómo funciona el retiro</a>
              </Button>
            </div>
            <ul className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/90">
              {["Asesoría especializada", "Preparación en taller", "Retiro puntual"].map((b) => (
                <li key={b} className="flex items-center gap-1.5"><CheckCircle2 className="h-4 w-4 text-[#2ED27E]" /> {b}</li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      {/* 2. Trust strip */}
      <section className="border-b bg-card">
        <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:grid-cols-3">
          {TRUST.map((t) => (
            <div key={t.title} className="flex items-center gap-3">
              <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-accent text-brand-medium"><t.icon className="h-5 w-5" /></span>
              <div><p className="font-semibold">{t.title}</p><p className="text-sm text-muted-foreground">{t.text}</p></div>
            </div>
          ))}
        </div>
      </section>

      <div className="mx-auto max-w-7xl px-4 py-12">
        {/* 3. Destacados */}
        <section className="mb-14">
          <div className="mb-1 flex items-end justify-between">
            <div>
              <h2 className="text-2xl font-semibold tracking-tight">Productos destacados</h2>
              <p className="text-sm text-muted-foreground">Lo más buscado por nuestros clientes esta semana.</p>
            </div>
            <Link href="/catalog" className="text-sm font-medium text-brand-medium hover:underline dark:text-brand-light">Ver todos</Link>
          </div>
          {isLoading ? (
            <div className="mt-4 flex gap-4 overflow-hidden">
              {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="aspect-[3/4] w-60 shrink-0" />)}
            </div>
          ) : (
            <div className="mt-4">
              <CarouselRow itemClassName="w-60">
                {(data?.featuredProducts ?? []).map((p) => <ProductCard key={p.id} product={toCardProduct(p)} />)}
              </CarouselRow>
            </div>
          )}
        </section>

        {/* 4. Explora por categoría (railway) */}
        {data && data.categories.length > 0 && (
          <section className="mb-14">
            <h2 className="mb-1 text-2xl font-semibold tracking-tight">Explora por categoría</h2>
            <p className="mb-3 text-sm text-muted-foreground">Desliza para ver cada familia de productos y sus subcategorías.</p>
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
              <Link href="/catalog" className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-medium hover:underline dark:text-brand-light">
                <Store className="h-4 w-4" /> Ver todo el catálogo
              </Link>
              <span className="hidden items-center gap-1.5 text-xs text-muted-foreground sm:inline-flex">
                <ArrowRight className="h-3 w-3" /> Desliza para descubrir más
              </span>
            </div>
            <CarouselRow itemClassName="w-72">
              {data.categories.map((c) => (
                <Card key={c.id} className="flex h-full flex-col transition-shadow hover:shadow-md">
                  <CardContent className="flex flex-1 flex-col gap-3 p-5">
                    <Link href={`/catalog?category_id=${c.id}`} className="group/cat flex flex-col gap-2">
                      <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-accent text-brand-medium dark:text-brand-light"><Store className="h-6 w-6" /></span>
                      <h3 className="text-lg font-semibold">{c.name}</h3>
                      {c.description && <p className="line-clamp-2 text-sm text-muted-foreground">{c.description}</p>}
                      <span className="inline-flex items-center gap-1 text-sm font-medium text-cta group-hover/cat:gap-2 dark:text-[#2ED27E]">
                        Ver todo en {c.name} <ArrowRight className="h-4 w-4 transition-all" />
                      </span>
                    </Link>
                    {c.children.length > 0 && (
                      <div className="mt-auto flex flex-wrap gap-1.5 border-t pt-3">
                        {c.children.slice(0, 6).map((s) => (
                          <Link
                            key={s.id}
                            href={`/catalog?category_id=${s.id}`}
                            className="rounded-full border px-2.5 py-1 text-xs text-muted-foreground transition-colors hover:border-brand-medium hover:text-brand-medium dark:hover:border-brand-light dark:hover:text-brand-light"
                          >
                            {s.name}
                          </Link>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </CarouselRow>
          </section>
        )}

        {/* 5. Beneficios */}
        <section className="mb-14">
          <h2 className="mb-1 text-2xl font-semibold tracking-tight">Encargos listos para retirar</h2>
          <p className="mb-4 text-sm text-muted-foreground">Elegí en el catálogo y te ayudamos a dejar tu compra lista para retirar en tienda.</p>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {BENEFITS.map((b) => (
              <Card key={b.title}>
                <CardContent className="flex flex-col gap-2 p-5">
                  <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-medium text-white"><b.icon className="h-5 w-5" /></span>
                  <p className="font-semibold">{b.title}</p>
                  <p className="text-sm text-muted-foreground">{b.text}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </section>

        {/* 6. Cómo funciona */}
        <section id="como-funciona" className="mb-14">
          <h2 className="mb-1 text-2xl font-semibold tracking-tight">Cómo funciona</h2>
          <p className="mb-4 text-sm text-muted-foreground">Tres pasos simples para dejar tu encargo listo para retirar en tienda.</p>
          <div className="grid gap-4 sm:grid-cols-3">
            {[
              { n: 1, icon: Store, title: "Explora el catálogo", text: "Encontrá la bici, componente o accesorio que buscás.", cta: ["Ver catálogo", "/catalog"] },
              { n: 2, icon: ClipboardList, title: "Deja tu solicitud", text: "Agregá al carrito y confirmá tu encargo.", cta: isGuest ? ["Inicia sesión", "/login"] : ["Ir al carrito", "/cart"] },
              { n: 3, icon: PackageCheck, title: "Retira en tienda", text: "Te avisamos cuando esté listo para retirar." },
            ].map((s) => (
              <Card key={s.n}>
                <CardContent className="flex flex-col gap-2 p-5">
                  <div className="flex items-center gap-2">
                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-cta text-sm font-bold text-white">{s.n}</span>
                    <s.icon className="h-5 w-5 text-brand-medium dark:text-brand-light" />
                  </div>
                  <p className="font-semibold">{s.title}</p>
                  <p className="text-sm text-muted-foreground">{s.text}</p>
                  {s.cta && (
                    <Link href={s.cta[1]} className="mt-1 text-sm font-medium text-brand-medium hover:underline dark:text-brand-light">{s.cta[0]} →</Link>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </section>

        {/* 7. Testimonios */}
        <section className="mb-14">
          <h2 className="mb-1 text-2xl font-semibold tracking-tight">Clientes que confían en nosotros</h2>
          <p className="mb-4 text-sm text-muted-foreground">Experiencias reales en atención y preparación de encargos.</p>
          <div className="grid gap-4 sm:grid-cols-3">
            {TESTIMONIALS.map((t) => (
              <Card key={t.name}>
                <CardContent className="flex flex-col gap-3 p-5">
                  <div className="flex gap-0.5">{Array.from({ length: 5 }).map((_, i) => <Star key={i} className="h-4 w-4 fill-amber-400 text-amber-400" />)}</div>
                  <p className="text-sm italic text-foreground/80">“{t.text}”</p>
                  <p className="text-sm font-medium">{t.name} <span className="font-normal text-muted-foreground">· {t.context}</span></p>
                </CardContent>
              </Card>
            ))}
          </div>
        </section>
      </div>

      {/* 8. CTA final */}
      <section className="bg-brand-darkest text-white">
        <div className="mx-auto max-w-7xl px-4 py-16 text-center">
          <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">¿Listo para tu próximo rodaje?</h2>
          <p className="mx-auto mt-2 max-w-xl text-white/80">Explorá el catálogo y dejá tu solicitud para prepararlo en tienda con respaldo técnico.</p>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            <Button asChild size="lg" className="bg-cta hover:bg-cta-strong"><Link href="/catalog">Ver catálogo</Link></Button>
            <Button asChild size="lg" variant="outline" className="border-white/40 bg-transparent text-white hover:bg-white/10 hover:text-white">
              <Link href={isGuest ? "/register" : "/cart"}>{isGuest ? "Crear cuenta" : "Ir al carrito"}</Link>
            </Button>
          </div>
        </div>
      </section>
    </div>
  );
}
