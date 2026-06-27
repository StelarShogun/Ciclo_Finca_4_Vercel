"use client";

import { useRef } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { ImageOff, Star, Trash2, Upload } from "lucide-react";

import {
  deleteGalleryImage,
  getProductGallery,
  mediaUrl,
  promoteGalleryImage,
  uploadGalleryImage,
  type ProductGallery as Gallery,
} from "@/lib/api/admin/products";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export function ProductGallery({ productId }: { productId: number | string }) {
  const queryClient = useQueryClient();
  const fileRef = useRef<HTMLInputElement>(null);
  const key = ["admin-product-gallery", productId];

  const { data, isLoading } = useQuery({
    queryKey: key,
    queryFn: () => getProductGallery(productId),
  });

  const onMutated = (g: Gallery) => {
    queryClient.setQueryData(key, g);
    queryClient.invalidateQueries({ queryKey: ["admin-product-detail", productId] });
  };

  const upload = useMutation({
    mutationFn: (file: File) => uploadGalleryImage(productId, file),
    onSuccess: (g) => {
      toast.success("Imagen agregada");
      onMutated(g);
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo subir la imagen.")),
    onSettled: () => {
      if (fileRef.current) fileRef.current.value = "";
    },
  });

  const promote = useMutation({
    mutationFn: (mediaId: number) => promoteGalleryImage(productId, mediaId),
    onSuccess: (g) => {
      toast.success("Imagen principal actualizada");
      onMutated(g);
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo promover la imagen.")),
  });

  const remove = useMutation({
    mutationFn: (mediaId: number) => deleteGalleryImage(productId, mediaId),
    onSuccess: (g) => {
      toast.success("Imagen eliminada");
      onMutated(g);
    },
    onError: (e) => toast.error(errMsg(e, "No se pudo eliminar la imagen.")),
  });

  const mainUrl = mediaUrl(data?.main?.url);
  const busy = upload.isPending || promote.isPending || remove.isPending;

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle>Galería</CardTitle>
        <Button
          size="sm"
          variant="outline"
          disabled={upload.isPending}
          onClick={() => fileRef.current?.click()}
        >
          <Upload className="h-4 w-4" />
          {upload.isPending ? "Subiendo…" : "Subir"}
        </Button>
        <input
          ref={fileRef}
          type="file"
          accept="image/jpeg,image/png,image/gif,image/webp"
          className="hidden"
          onChange={(e) => {
            const f = e.target.files?.[0];
            if (f) upload.mutate(f);
          }}
        />
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {isLoading ? (
          <Skeleton className="aspect-square w-full" />
        ) : (
          <>
            <div className="flex aspect-square items-center justify-center overflow-hidden rounded-lg border bg-muted">
              {mainUrl ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={mainUrl} alt="Principal" className="h-full w-full object-cover" />
              ) : (
                <ImageOff className="h-10 w-10 text-muted-foreground" />
              )}
            </div>

            {data && data.gallery.length > 0 && (
              <div className="grid grid-cols-3 gap-2">
                {data.gallery.map((g) => {
                  const url = mediaUrl(g.url);
                  return (
                    <div key={g.id} className="group relative aspect-square overflow-hidden rounded border bg-muted">
                      {url && (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={url} alt="" className="h-full w-full object-cover" />
                      )}
                      <div className="absolute inset-0 flex items-center justify-center gap-1 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                        <Button
                          size="icon"
                          variant="secondary"
                          className="h-7 w-7"
                          title="Hacer principal"
                          disabled={busy}
                          onClick={() => promote.mutate(g.id)}
                        >
                          <Star className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                          size="icon"
                          variant="destructive"
                          className="h-7 w-7"
                          title="Eliminar"
                          disabled={busy}
                          onClick={() => remove.mutate(g.id)}
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}
