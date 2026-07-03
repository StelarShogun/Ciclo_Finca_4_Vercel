"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Heart, HeartOff } from "lucide-react";

import { getFavorites, toggleFavorite } from "@/lib/api/client/account";
import { storeMediaUrl } from "@/lib/api/client/catalog";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export default function FavoritesPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/favorites");
    }
  }, [me.isError, me.error, me.data, router]);

  const { data, isLoading } = useQuery({ queryKey: ["favorites"], queryFn: () => getFavorites() });

  const remove = useMutation({
    mutationFn: (id: string) => toggleFavorite(id),
    onSuccess: () => {
      toast.success("Quitado de favoritos");
      queryClient.invalidateQueries({ queryKey: ["favorites"] });
    },
  });

  if (me.isLoading || isLoading) {
    return <div className="mx-auto max-w-5xl px-4 py-12"><Skeleton className="h-64" /></div>;
  }

  const items = data?.favorites ?? [];

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <h1 className="mb-6 flex items-center gap-2 text-2xl font-semibold tracking-tight">
        <Heart className="h-6 w-6 text-[#235347]" /> Favoritos
      </h1>

      {items.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
            <HeartOff className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">Todavía no tenés favoritos.</p>
            <Button asChild className="bg-[#235347] hover:bg-[#1a3f37]"><Link href="/catalog">Ver catálogo</Link></Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {items.map((f) => {
            const img = storeMediaUrl(f.image_url);
            return (
              <Card key={f.product_id} className="flex flex-col overflow-hidden p-0">
                <Link href={`/product/${f.product_id}`} className="block aspect-square overflow-hidden bg-muted">
                  {img && !f.uses_placeholder_image ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={img} alt={f.name} className="h-full w-full object-cover" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-muted-foreground"><i className={`${f.placeholder_icon_class ?? "fas fa-box"} text-4xl`} aria-hidden /></div>
                  )}
                </Link>
                <div className="flex flex-1 flex-col gap-1 p-3">
                  <span className="text-xs text-muted-foreground">{f.category}</span>
                  <Link href={`/product/${f.product_id}`} className="line-clamp-2 text-sm font-medium hover:underline">{f.name}</Link>
                  <div className="mt-auto flex items-center justify-between pt-2">
                    <span className="font-semibold text-[#235347]">{f.price_formatted}</span>
                    <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" title="Quitar"
                      disabled={remove.isPending} onClick={() => remove.mutate(f.product_id)}>
                      <Heart className="h-4 w-4 fill-current" />
                    </Button>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
