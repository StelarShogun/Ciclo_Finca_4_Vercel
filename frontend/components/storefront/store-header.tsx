"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Bell, Heart, Home, LogOut, Receipt, Search, ShoppingCart, Store, User } from "lucide-react";

import { clientLogout } from "@/lib/api/auth";
import { useMe } from "@/lib/auth/use-me";
import { getCart } from "@/lib/api/client/cart";
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
  const params = useSearchParams();
  const queryClient = useQueryClient();
  const { data } = useMe();
  const [search, setSearch] = useState(params.get("search") ?? "");

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
      <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4">
        {/* Logo */}
        <Link href="/" className="flex shrink-0 items-center gap-2">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="Ciclo Finca 4" width={36} height={36} className="h-9 w-9 object-contain" />
          <span className="hidden text-base font-semibold leading-tight sm:block">
            Ciclo <span className="text-[#8EB69B]">Finca</span>
          </span>
        </Link>

        {/* Nav */}
        <nav className="ml-1 flex items-center gap-1">
          <NavLink href="/" active={pathname === "/"} icon={Home}>Inicio</NavLink>
          <NavLink href="/catalog" active={pathname.startsWith("/catalog")} icon={Store}>Catálogo</NavLink>
        </nav>

        {/* Búsqueda */}
        <form onSubmit={submitSearch} className="relative ml-2 hidden flex-1 lg:block">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-[#235347]" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar productos…"
            className="border-0 bg-white pl-9 text-foreground"
          />
        </form>

        {/* Acciones */}
        <div className="ml-auto flex items-center gap-1">
          <ThemeToggle className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white" />

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
                <DropdownMenuItem asChild><Link href="/favorites"><Heart className="h-4 w-4" /> Mis favoritos</Link></DropdownMenuItem>
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
