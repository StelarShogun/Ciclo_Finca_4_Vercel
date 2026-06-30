"use client";

import { createContext, useContext, useState } from "react";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Heart, HeartOff, X } from "lucide-react";

import { getFavorites, toggleFavorite } from "@/lib/api/client/account";
import { storeMediaUrl } from "@/lib/api/client/catalog";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";

type Ctx = { open: () => void };
const FavoritesDrawerContext = createContext<Ctx>({ open: () => {} });
export const useFavoritesDrawer = () => useContext(FavoritesDrawerContext);

export function FavoritesDrawerProvider({ children }: { children: React.ReactNode }) {
  const [isOpen, setIsOpen] = useState(false);
  const queryClient = useQueryClient();
  const { data: me } = useMe();
  const isClient = me?.type === "client";

  const favorites = useQuery({
    queryKey: ["favorites"],
    queryFn: () => getFavorites(),
    enabled: isOpen && isClient,
  });

  const remove = useMutation({
    mutationFn: (id: number) => toggleFavorite(id),
    onSuccess: () => {
      toast.success("Quitado de favoritos");
      queryClient.invalidateQueries({ queryKey: ["favorites"] });
      queryClient.invalidateQueries({ queryKey: ["catalog"] });
    },
  });

  const items = favorites.data?.favorites ?? [];

  return (
    <FavoritesDrawerContext.Provider value={{ open: () => setIsOpen(true) }}>
      {children}
      <Sheet open={isOpen} onOpenChange={setIsOpen}>
        <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-md">
          <SheetHeader className="border-b px-4 py-3">
            <SheetTitle className="flex items-center gap-2">
              <Heart className="h-5 w-5 text-[#235347] dark:text-[#8EB69B]" /> Mis favoritos
            </SheetTitle>
          </SheetHeader>

          <div className="flex-1 overflow-y-auto p-4">
            {!isClient ? (
              <div className="flex flex-col items-center gap-3 py-12 text-center">
                <HeartOff className="h-8 w-8 text-muted-foreground" />
                <p className="text-sm text-muted-foreground">Iniciá sesión para ver tus favoritos.</p>
                <Button asChild className="bg-[#235347] hover:bg-[#1a3f37]" onClick={() => setIsOpen(false)}>
                  <Link href="/login">Iniciar sesión</Link>
                </Button>
              </div>
            ) : favorites.isLoading ? (
              <div className="space-y-3">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-16" />)}</div>
            ) : items.length === 0 ? (
              <div className="flex flex-col items-center gap-3 py-12 text-center">
                <HeartOff className="h-8 w-8 text-muted-foreground" />
                <p className="text-sm text-muted-foreground">Todavía no tenés favoritos.</p>
                <Button asChild variant="outline" onClick={() => setIsOpen(false)}><Link href="/catalog">Ver catálogo</Link></Button>
              </div>
            ) : (
              <ul className="space-y-2">
                {items.map((f) => {
                  const img = storeMediaUrl(f.image_url);
                  return (
                    <li key={f.product_id} className="flex items-center gap-3 rounded-md border p-2">
                      <Link href={`/product/${f.product_id}`} onClick={() => setIsOpen(false)} className="h-12 w-12 shrink-0 overflow-hidden rounded bg-muted">
                        {img && !f.uses_placeholder_image ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={img} alt={f.name} className="h-full w-full object-cover" />
                        ) : (
                          <div className="flex h-full w-full items-center justify-center">🚲</div>
                        )}
                      </Link>
                      <div className="min-w-0 flex-1">
                        <Link href={`/product/${f.product_id}`} onClick={() => setIsOpen(false)} className="line-clamp-1 text-sm font-medium hover:underline">{f.name}</Link>
                        <p className="text-sm text-[#235347] dark:text-[#8EB69B]">{f.price_formatted}</p>
                      </div>
                      <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" disabled={remove.isPending} onClick={() => remove.mutate(f.product_id)} title="Quitar">
                        <X className="h-4 w-4" />
                      </Button>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>

          {isClient && items.length > 0 && (
            <div className="border-t p-4">
              <Button asChild variant="outline" className="w-full" onClick={() => setIsOpen(false)}>
                <Link href="/favorites">Ver todos</Link>
              </Button>
            </div>
          )}
        </SheetContent>
      </Sheet>
    </FavoritesDrawerContext.Provider>
  );
}
