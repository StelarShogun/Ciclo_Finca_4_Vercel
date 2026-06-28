"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Heart, Search, ShoppingCart, User } from "lucide-react";

import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export function StoreHeader() {
  const router = useRouter();
  const params = useSearchParams();
  const { data } = useMe();
  const [search, setSearch] = useState(params.get("search") ?? "");

  const isClient = data?.type === "client";

  function submitSearch(e: React.FormEvent) {
    e.preventDefault();
    const q = search.trim();
    router.push(q ? `/catalog?search=${encodeURIComponent(q)}` : "/catalog");
  }

  return (
    <header className="sticky top-0 z-40 border-b bg-[#051F20] text-[#DAF1DE]">
      <div className="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4">
        <Link href="/" className="flex shrink-0 items-center gap-2 font-semibold">
          <span className="flex h-8 w-8 items-center justify-center rounded-md bg-[#235347] text-sm font-bold text-white">
            CF
          </span>
          <span className="hidden sm:inline">Ciclo Finca</span>
        </Link>

        <form onSubmit={submitSearch} className="relative flex-1 max-w-xl">
          <Search className="absolute left-3 top-2.5 h-4 w-4 text-[#235347]" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar productos…"
            className="border-0 bg-white pl-9 text-foreground"
          />
        </form>

        <nav className="ml-auto flex items-center gap-1">
          <Button asChild variant="ghost" size="icon" className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
            <Link href="/favorites" aria-label="Favoritos">
              <Heart className="h-5 w-5" />
            </Link>
          </Button>
          <Button asChild variant="ghost" size="icon" className="text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
            <Link href="/cart" aria-label="Carrito">
              <ShoppingCart className="h-5 w-5" />
            </Link>
          </Button>
          <Button asChild variant="ghost" size="sm" className="gap-2 text-[#DAF1DE] hover:bg-[#235347] hover:text-white">
            <Link href={isClient ? "/account" : "/login"}>
              <User className="h-4 w-4" />
              <span className="hidden sm:inline">
                {isClient ? (data?.user.name ?? "Mi cuenta") : "Ingresar"}
              </span>
            </Link>
          </Button>
        </nav>
      </div>
    </header>
  );
}
