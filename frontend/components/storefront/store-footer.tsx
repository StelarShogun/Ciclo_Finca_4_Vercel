"use client";

import Link from "next/link";
import { FileText, Store } from "lucide-react";

import { useMe } from "@/lib/auth/use-me";

function FooterColumn({ title, links }: { title: string; links: [string, string][] }) {
  return (
    <div>
      <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-white/90">{title}</h4>
      <ul className="space-y-2 text-sm text-[#DAF1DE]/70">
        {links.map(([label, href]) => (
          <li key={href}>
            <Link href={href} className="transition-colors hover:text-white">
              {label}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function StoreFooter() {
  const { data } = useMe();
  const isClient = data?.type === "client";

  const navLinks: [string, string][] = isClient
    ? [
        ["Inicio", "/"],
        ["Catálogo", "/catalog"],
        ["Carrito", "/cart"],
        ["Mi perfil", "/profile"],
      ]
    : [
        ["Inicio", "/"],
        ["Catálogo", "/catalog"],
        ["Iniciar sesión", "/login"],
        ["Crear cuenta", "/register"],
      ];

  return (
    <footer className="mt-16 bg-[#051F20] text-[#DAF1DE]" aria-label="Pie de página">
      <div className="mx-auto max-w-7xl px-4 py-12">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
          {/* Marca */}
          <div className="flex items-start gap-3">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/brand/logo-ciclo-finca-icon-transparent.png"
              alt=""
              width={64}
              height={64}
              className="h-16 w-16 shrink-0 object-contain"
            />
            <div>
              <h3 className="text-lg font-semibold text-white">Ciclo Finca 4</h3>
              <p className="mt-1 text-sm text-[#DAF1DE]/70">
                Ciclo Finca 4 es la tienda en línea para comprar bicicletas, componentes y
                accesorios con retiro en tienda. Creá tu cuenta para encargar productos,
                seguir tus pedidos y guardar favoritos.
              </p>
            </div>
          </div>

          <FooterColumn title="Navegación" links={navLinks} />

          <div>
            <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-white/90">Servicio</h4>
            <ul className="space-y-2 text-sm text-[#DAF1DE]/70">
              <li>Asesoría personalizada</li>
              <li>Preparación en taller</li>
              <li>Retiro en tienda</li>
              <li>Soporte post-retiro</li>
            </ul>
          </div>

          <div>
            <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-white/90">Contacto</h4>
            <ul className="space-y-2 text-sm text-[#DAF1DE]/70">
              <li className="flex items-center gap-2">
                <Store className="h-4 w-4 shrink-0" />
                <span>Tienda física · retiro de pedidos</span>
              </li>
              <li className="flex items-center gap-2">
                <FileText className="h-4 w-4 shrink-0" />
                <Link href="/contacto" className="transition-colors hover:text-white">
                  Formulario e información de contacto
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-10 flex flex-col gap-3 border-t border-[#DAF1DE]/15 pt-6 text-xs text-[#DAF1DE]/60 sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} Ciclo Finca 4. Todos los derechos reservados.</p>
          <nav className="flex flex-wrap items-center gap-x-3 gap-y-1" aria-label="Información legal">
            <Link href="/terminos" className="hover:text-white">Términos y condiciones</Link>
            <span aria-hidden>|</span>
            <Link href="/privacidad" className="hover:text-white">Política de privacidad</Link>
            <span aria-hidden>|</span>
            <Link href="/devoluciones" className="hover:text-white">Cambios y devoluciones</Link>
            <span aria-hidden>|</span>
            <Link href="/contacto" className="hover:text-white">Contacto</Link>
          </nav>
        </div>
      </div>
    </footer>
  );
}
