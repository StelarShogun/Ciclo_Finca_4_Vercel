"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Bell, Heart, Home, LogOut, Receipt, Search, ShoppingCart, Store, Tag, User } from "lucide-react";

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
import { cn } from "@/lib/utils";

function NavLink({ href, active, icon: Icon, children }: { href: string; active: boolean; icon: React.ElementType; children: React.ReactNode }) {
  return (
    <Link
      href={href}
      className={cn(
        "flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
        active ? "bg-[#235347] text-white" : "text-[#DAF1DE] hover:bg-[#235347]/60 hover:text-white",
      )}
    >
      <Icon className="h-4 w-4" />
      <span className="hidden md:inline">{children}</span>
    </Link>
  );
}

export function StoreHeader() {
  const router = useRouter();
  const pathname = usePathname();
  const queryClient = useQueryClient();
  const { data } = useMe();
  const favoritesDrawer = useFavoritesDrawer();
  // Sin useSearchParams: fuerza CSR bail-out y saca el header (con el nombre
  // de la marca) del HTML estático, y el verificador de Google no lo ve.
  const [search, setSearch] = useState("");
  useEffect(() => {
    setSearch(new URLSearchParams(window.location.search).get("search") ?? "");
  }, []);
  const [debounced, setDebounced] = useState("");
  const [open, setOpen] = useState(false);
  const boxRef = useRef<HTMLDivElement>(null);

  const isClient = data?.type === "client";

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

  function submitSearch(e: React.FormEvent) {
    e.preventDefault();
    const q = search.trim();
    router.push(q ? `/catalog?search=${encodeURIComponent(q)}` : "/catalog");
  }

  const initials = isClient
    ? `${data?.user.name?.[0] ?? ""}${data?.user.first_surname?.[0] ?? ""}`.toUpperCase()
    : "";

  return (
    <header className="sticky top-0 z-40 border-b border-[#235347]/40 bg-[#051F20] text-[#DAF1DE]">
      {/* Modo administrador: equivalente SPA del admin_catalog_mode viejo */}
      {data?.type === "admin" && (
        <div className="bg-[#F59E0B] text-[#051F20]">
          <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-1.5 text-xs font-semibold">
            <span className="inline-flex items-center gap-1.5">
              <i className="fas fa-user-shield" aria-hidden />
              Estás navegando el sitio como administrador
            </span>
            <Link href="/admin" className="inline-flex items-center gap-1.5 rounded-full bg-[#051F20] px-3 py-1 text-[#DAF1DE] transition hover:bg-[#0B2B26]">
              <i className="fas fa-arrow-left" aria-hidden />
              Volver al panel
            </Link>
          </div>
        </div>
      )}
      <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4">
        {/* Logo */}
        <Link href="/" className="flex shrink-0 items-center gap-2">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="Ciclo Finca 4" width={36} height={36} className="h-9 w-9 object-contain" />
          <span className="hidden text-base font-semibold leading-tight sm:block">
            Ciclo <span className="text-[#8EB69B]">Finca 4</span>
          </span>
        </Link>

        {/* Nav */}
        <nav className="ml-1 flex items-center gap-1">
          <NavLink href="/" active={pathname === "/"} icon={Home}>Inicio</NavLink>
          <NavLink href="/catalog" active={pathname.startsWith("/catalog")} icon={Store}>Catálogo</NavLink>
        </nav>

        {/* Búsqueda inteligente */}
        <div ref={boxRef} className="relative ml-2 hidden flex-1 lg:block">
          <form onSubmit={submitSearch} className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-[#235347]" />
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
                      className="flex w-full items-center gap-2 px-3 py-2 text-sm font-medium text-[#235347] hover:bg-accent dark:text-[#8EB69B]"
                    >
                      <Search className="h-4 w-4" /> Ver todos los resultados de “{search.trim()}”
                    </button>
                  </li>
                </ul>
              )}
            </div>
          )}
        </div>

        {/* Acciones */}
        <div className="ml-auto flex items-center gap-1">
          <ThemeToggle className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white" />

          <Button
            variant="ghost"
            size="icon"
            aria-label="Favoritos"
            className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white"
            onClick={() => favoritesDrawer.open()}
          >
            <Heart className="h-5 w-5" />
          </Button>

          <Button asChild variant="ghost" size="icon" className="relative text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
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
                <Button variant="ghost" className="gap-2 px-2 text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
                  <Avatar className="h-7 w-7">
                    <AvatarFallback className="bg-[#235347] text-xs text-white">{initials || "U"}</AvatarFallback>
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
              <Button asChild variant="ghost" size="sm" className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
                <Link href="/login">Iniciar sesión</Link>
              </Button>
              <Button asChild size="sm" className="bg-[#12B36A] text-white hover:bg-[#0E9558]">
                <Link href="/register">Crear cuenta</Link>
              </Button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
