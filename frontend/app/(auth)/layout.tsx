import Link from "next/link";

import { ThemeToggle } from "@/components/theme-toggle";

/** Layout minimal de autenticación (sin header/footer de tienda), como el ClientAuthLayout viejo. */
export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-svh flex-col bg-[#eef7f0] dark:bg-[#020f10]">
      <header className="flex items-center justify-between px-4 py-4">
        <Link href="/" className="flex items-center gap-2">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="Ciclo Finca 4" width={36} height={36} className="h-9 w-9 object-contain" />
          <span className="text-base font-semibold text-brand-medium dark:text-brand-light">
            Ciclo <span className="text-cta">Finca</span>
          </span>
        </Link>
        <ThemeToggle />
      </header>
      <main className="flex flex-1 items-center justify-center px-4 pb-12">{children}</main>
    </div>
  );
}
