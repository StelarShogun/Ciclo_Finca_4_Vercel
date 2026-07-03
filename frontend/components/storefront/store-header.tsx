"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Bell, Heart, Home, LogOut, Menu, Receipt, Search, ShoppingCart, Store, Tag, User } from "lucide-react";

import { clientLogout } from "@/lib/api/auth";
import { useMe } from "@/lib/auth/use-me";
import { getCart } from "@/lib/api/client/cart";
import { getSuggestions } from "@/lib/api/client/catalog";
import { useFavoritesDrawer } from "@/components/storefront/favorites-drawer";
import { api } from "@/lib/api/client";
import { ThemeToggle } from "@/components/theme-toggle";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { cn } from "@/lib/utils";

function NavLink({ href, active, icon: Icon, children }: { href: string; active: boolean; icon: React.ElementType; children: React.ReactNode }) {
  return (
    <Link
      href={href}
      className={cn(
        "flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
        active ? "bg-brand-medium text-white" : "text-brand-lightest hover:bg-brand-medium/60 hover:text-white",
      )}
    >
      <Icon className="h-4 w-4" />
      <span className="hidden md:inline">{children}</span>
    </Link>
  );
}

/**
 * Búsqueda inteligente con sugerencias. Componente propio para poder montarla
 * dos veces (fila principal en desktop, segunda fila en móvil) con estado
 * independiente sin duplicar lógica.
 */
function SmartSearch({ className }: { className?: string }) {
  const router = useRouter();
  // Sin useSearchParams: fuerza CSR bail-out y saca el header (con el nombre
  // de la marca) del HTML estático, y el verificador de Google no lo ve.
  const [search, setSearch] = useState("");
  useEffect(() => {
    setSearch(new URLSearchParams(window.location.search).get("search") ?? "");
  }, []);
  const [debounced, setDebounced] = useState("");
  const [open, setOpen] = useState(false);
  const boxRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 250);
    return () => clearTimeout(t);
  }, [search]);

  // Cerrar el dropdown al hacer clic afuera.
  useEffect(() => {
    function onClick(e: MouseEvent) {
      if (boxRef.current && !boxRef.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, []);

  const suggestions = useQuery({
    queryKey: ["suggestions", debounced],
    queryFn: () => getSuggestions(debounced),
    enabled: open && debounced.trim().length >= 2,
    placeholderData: keepPreviousData,
  });

  function submitSearch(e: React.FormEvent) {
    e.preventDefault();
    const q = search.trim();
    router.push(q ? `/catalog?search=${encodeURIComponent(q)}` : "/catalog");
  }

  return (
    <div ref={boxRef} className={cn("relative", className)}>
      <form onSubmit={submitSearch} className="relative">
        <Search className="absolute left-3 top-2.5 h-4 w-4 text-brand-medium" />
        <Input
          value={search}
          onChange={(e) => { setSearch(e.target.value); setOpen(true); }}
          onFocus={() => setOpen(true)}
          placeholder="Buscar productos…"
          className="border-0 bg-white pl-9 text-foreground"
        />
      </form>
      {open && debounced.trim().length >= 2 && (
        <div className="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-lg">
          {suggestions.isLoading ? (
            <p className="px-3 py-3 text-sm text-muted-foreground">Buscando…</p>
          ) : (suggestions.data ?? []).length === 0 ? (
            <p className="px-3 py-3 text-sm text-muted-foreground">Sin sugerencias.</p>
          ) : (
            <ul className="max-h-80 overflow-y-auto py-1">
              {(suggestions.data ?? []).map((s) => (
                <li key={`${s.type}-${s.id}`}>
                  <Link
                    href={s.type === "category" ? `/catalog?category_id=${s.id}` : `/product/${s.id}`}
                    onClick={() => setOpen(false)}
                    className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent"
                  >
                    {s.type === "category" ? <Tag className="h-4 w-4 text-muted-foreground" /> : <Search className="h-4 w-4 text-muted-foreground" />}
                    <span className="flex-1 truncate">{s.name}</span>
                    {s.category && <span className="text-xs text-muted-foreground">{s.category}</span>}
                  </Link>
                </li>
              ))}
              <li className="border-t">
                <button
                  onClick={() => { setOpen(false); router.push(`/catalog?search=${encodeURIComponent(search.trim())}`); }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm font-medium text-brand-medium hover:bg-accent dark:text-brand-light"
                >
                  <Search className="h-4 w-4" /> Ver todos los resultados de “{search.trim()}”
                </button>
              </li>
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

export function StoreHeader() {
  const router = useRouter();
  const pathname = usePathname();
  const queryClient = useQueryClient();
  const { data } = useMe();
  const favoritesDrawer = useFavoritesDrawer();
  const [menuOpen, setMenuOpen] = useState(false);

  const isClient = data?.type === "client";

  const cart = useQuery({ queryKey: ["cart"], queryFn: getCart, staleTime: 30_000 });
  const cartCount = cart.data?.items.reduce((n, i) => n + i.quantity, 0) ?? 0;

  const notif = useQuery({
    queryKey: ["notifications-heartbeat"],
    queryFn: async () => (await api.get("/api/v1/notifications/heartbeat")).data as { count?: number; unseen_history?: number },
    enabled: isClient,
    staleTime: 60_000,
  });
  const unread = notif.data?.count ?? 0;

  const logout = useMutation({
    mutationFn: clientLogout,
    onSuccess: () => {
      queryClient.clear();
      toast.success("Sesión cerrada");
      router.replace("/");
    },
  });

  const initials = isClient
    ? `${data?.user.name?.[0] ?? ""}${data?.user.first_surname?.[0] ?? ""}`.toUpperCase()
    : "";

  return (
    <header className="sticky top-0 z-40 border-b border-brand-medium/40 bg-brand-darkest text-brand-lightest">
      {/* Modo administrador: equivalente SPA del admin_catalog_mode viejo */}
      {data?.type === "admin" && (
        <div className="bg-[#F59E0B] text-brand-darkest">
          <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-1.5 text-xs font-semibold">
            <span className="inline-flex items-center gap-1.5">
              <i className="fas fa-user-shield" aria-hidden />
              Estás navegando el sitio como administrador
            </span>
            <Link href="/admin" className="inline-flex items-center gap-1.5 rounded-full bg-brand-darkest px-3 py-1 text-brand-lightest transition hover:bg-brand-dark">
              <i className="fas fa-arrow-left" aria-hidden />
              Volver al panel
            </Link>
          </div>
        </div>
      )}
      <div className="mx-auto flex h-16 max-w-7xl items-center gap-2 px-3 sm:gap-3 sm:px-4">
        {/* Logo */}
        <Link href="/" className="flex shrink-0 items-center gap-2">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="Ciclo Finca 4" width={36} height={36} className="h-9 w-9 object-contain" />
          <span className="hidden text-base font-semibold leading-tight sm:block">
            Ciclo <span className="text-brand-light">Finca 4</span>
          </span>
        </Link>

        {/* Nav (desktop) */}
        <nav className="ml-1 hidden items-center gap-1 md:flex">
          <NavLink href="/" active={pathname === "/"} icon={Home}>Inicio</NavLink>
          <NavLink href="/catalog" active={pathname.startsWith("/catalog")} icon={Store}>Catálogo</NavLink>
        </nav>

        {/* Búsqueda inteligente (fila principal, solo desktop) */}
        <SmartSearch className="ml-2 hidden flex-1 lg:block" />

        {/* Móvil: carrito + hamburguesa, como el header viejo */}
        <div className="ml-auto flex items-center gap-1 md:hidden">
          <Button asChild variant="ghost" size="icon" className="relative text-brand-lightest hover:bg-brand-medium hover:text-white">
            <Link href="/cart" aria-label="Carrito">
              <ShoppingCart className="h-5 w-5" />
              {cartCount > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">
                  {cartCount}
                </span>
              )}
            </Link>
          </Button>
          <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
            <SheetTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                aria-label={menuOpen ? "Cerrar menú de navegación" : "Abrir menú de navegación"}
                className="text-brand-lightest hover:bg-brand-medium hover:text-white"
              >
                <Menu className="h-5 w-5" />
              </Button>
            </SheetTrigger>
            <SheetContent side="right" className="flex w-[85vw] max-w-sm flex-col gap-0 overflow-y-auto bg-brand-darkest p-0 text-brand-lightest">
              <SheetHeader className="border-b border-brand-medium/40 p-4">
                <SheetTitle className="flex items-center gap-2 text-brand-lightest">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="" width={28} height={28} className="h-7 w-7 object-contain" />
                  Ciclo <span className="text-brand-light">Finca 4</span>
                </SheetTitle>
              </SheetHeader>

              <div className="flex flex-col gap-1 p-4">
                <SmartSearch className="mb-3" />

                {[
                  { href: "/", label: "Inicio", icon: Home, active: pathname === "/" },
                  { href: "/catalog", label: "Catálogo", icon: Store, active: pathname.startsWith("/catalog") },
                  { href: "/cart", label: "Carrito", icon: ShoppingCart, active: pathname.startsWith("/cart"), badge: cartCount },
                ].map((item) => (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={() => setMenuOpen(false)}
                    className={cn(
                      "flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium",
                      item.active ? "bg-brand-medium text-white" : "hover:bg-brand-medium/60 hover:text-white",
                    )}
                  >
                    <item.icon className="h-4 w-4" /> {item.label}
                    {!!item.badge && (
                      <span className="ml-auto rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{item.badge}</span>
                    )}
                  </Link>
                ))}
                <button
                  type="button"
                  onClick={() => { setMenuOpen(false); favoritesDrawer.open(); }}
                  className="flex items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm font-medium hover:bg-brand-medium/60 hover:text-white"
                >
                  <Heart className="h-4 w-4" /> Favoritos
                </button>

                <div className="my-2 border-t border-brand-medium/40" />

                <div className="flex items-center justify-between px-3 py-1.5 text-sm font-medium">
                  Tema
                  <ThemeToggle className="text-brand-lightest hover:bg-brand-medium hover:text-white" />
                </div>

                <div className="my-2 border-t border-brand-medium/40" />

                {isClient ? (
                  <>
                    <div className="flex items-center gap-3 px-3 py-2">
                      <Avatar className="h-8 w-8">
                        <AvatarFallback className="bg-brand-medium text-xs text-white">{initials || "U"}</AvatarFallback>
                      </Avatar>
                      <div className="min-w-0">
                        <p className="truncate text-sm font-semibold">{data?.user.name}</p>
                        <p className="truncate text-xs text-brand-lightest/60">{data?.user.gmail}</p>
                      </div>
                    </div>
                    {[
                      { href: "/profile", label: "Mi perfil", icon: User },
                      { href: "/invoices", label: "Mis facturas", icon: Receipt },
                      { href: "/notifications", label: "Notificaciones", icon: Bell, badge: unread },
                    ].map((item) => (
                      <Link
                        key={item.href}
                        href={item.href}
                        onClick={() => setMenuOpen(false)}
                        className="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium hover:bg-brand-medium/60 hover:text-white"
                      >
                        <item.icon className="h-4 w-4" /> {item.label}
                        {!!item.badge && (
                          <span className="ml-auto rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{item.badge}</span>
                        )}
                      </Link>
                    ))}
                    <button
                      type="button"
                      disabled={logout.isPending}
                      onClick={() => { setMenuOpen(false); logout.mutate(); }}
                      className="flex items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm font-medium text-red-300 hover:bg-red-500/15"
                    >
                      <LogOut className="h-4 w-4" /> Cerrar sesión
                    </button>
                  </>
                ) : (
                  <div className="flex flex-col gap-2 px-1 pt-1">
                    <Button asChild className="w-full bg-cta text-white hover:bg-cta-strong" onClick={() => setMenuOpen(false)}>
                      <Link href="/login">Iniciar sesión</Link>
                    </Button>
                    <Button asChild variant="outline" className="w-full border-brand-light/50 bg-transparent text-brand-lightest hover:bg-brand-medium hover:text-white" onClick={() => setMenuOpen(false)}>
                      <Link href="/register">Crear cuenta</Link>
                    </Button>
                  </div>
                )}
              </div>
            </SheetContent>
          </Sheet>
        </div>

        {/* Acciones (desktop) */}
        <div className="ml-auto hidden items-center gap-1 md:flex">
          <ThemeToggle className="text-brand-lightest hover:bg-brand-medium hover:text-white" />

          <Button
            variant="ghost"
            size="icon"
            aria-label="Favoritos"
            className="text-brand-lightest hover:bg-brand-medium hover:text-white"
            onClick={() => favoritesDrawer.open()}
          >
            <Heart className="h-5 w-5" />
          </Button>

          <Button asChild variant="ghost" size="icon" className="relative text-brand-lightest hover:bg-brand-medium hover:text-white">
            <Link href="/cart" aria-label="Carrito">
              <ShoppingCart className="h-5 w-5" />
              {cartCount > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">
                  {cartCount}
                </span>
              )}
            </Link>
          </Button>

          {isClient ? (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="gap-2 px-2 text-brand-lightest hover:bg-brand-medium hover:text-white">
                  <Avatar className="h-7 w-7">
                    <AvatarFallback className="bg-brand-medium text-xs text-white">{initials || "U"}</AvatarFallback>
                  </Avatar>
                  <span className="hidden max-w-28 truncate sm:inline">{data?.user.name}</span>
                  {unread > 0 && <span className="h-2 w-2 rounded-full bg-amber-400" />}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="truncate">{data?.user.gmail}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild><Link href="/profile"><User className="h-4 w-4" /> Mi perfil</Link></DropdownMenuItem>
                <DropdownMenuItem onClick={() => favoritesDrawer.open()}><Heart className="h-4 w-4" /> Mis favoritos</DropdownMenuItem>
                <DropdownMenuItem asChild><Link href="/invoices"><Receipt className="h-4 w-4" /> Mis facturas</Link></DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href="/notifications">
                    <Bell className="h-4 w-4" /> Notificaciones
                    {unread > 0 && <span className="ml-auto rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{unread}</span>}
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive" disabled={logout.isPending} onClick={() => logout.mutate()}>
                  <LogOut className="h-4 w-4" /> Cerrar sesión
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          ) : (
            <div className="flex items-center gap-1">
              <Button asChild variant="ghost" size="sm" className="text-brand-lightest hover:bg-brand-medium hover:text-white">
                <Link href="/login">Iniciar sesión</Link>
              </Button>
              <Button asChild size="sm" className="bg-cta text-white hover:bg-cta-strong">
                <Link href="/register">Crear cuenta</Link>
              </Button>
            </div>
          )}
        </div>
      </div>

      {/* Búsqueda en fila propia para tablet (en móvil vive en el menú hamburguesa) */}
      <div className="mx-auto hidden max-w-7xl px-4 pb-3 md:block lg:hidden">
        <SmartSearch />
      </div>
    </header>
  );
}
