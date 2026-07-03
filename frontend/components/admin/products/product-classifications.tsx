"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  getProductClassifications,
  updateProductClassifications,
} from "@/lib/api/admin/products";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";

const NONE = "__none__";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export function ProductClassifications({ productId }: { productId: number | string }) {
  const queryClient = useQueryClient();
  // Overrides sparse: solo dimensiones que el usuario tocó. El valor efectivo
  // cae al `selected` del servidor cuando no hay override. Evita sincronizar
  // estado en un effect.
  const [overrides, setOverrides] = useState<Record<number, number | null>>({});

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-product-classifications", String(productId)],
    queryFn: () => getProductClassifications(productId),
  });

  const effective = (attrId: number, serverSelected: number | null) =>
    attrId in overrides ? overrides[attrId] : serverSelected;

  const save = useMutation({
    mutationFn: () =>
      updateProductClassifications(
        productId,
        (data?.attributes ?? [])
          .map((a) => effective(a.id, a.selected))
          .filter((v): v is number => v != null),
      ),
    onSuccess: () => {
      toast.success("Clasificaciones guardadas");
      setOverrides({});
      queryClient.invalidateQueries({ queryKey: ["admin-product-classifications", String(productId)] });
      queryClient.invalidateQueries({ queryKey: ["admin-product-detail", String(productId)] });
    },
    onError: (e) => toast.error(errMsg(e, "No se pudieron guardar las clasificaciones.")),
  });

  if (isLoading) return <Skeleton className="mt-6 h-40" />;
  if (isError || !data) return null;

  if (!data.editable) {
    return (
      <Card className="mt-6">
        <CardHeader><CardTitle>Clasificaciones</CardTitle></CardHeader>
        <CardContent className="text-sm text-muted-foreground">
          {data.reason ?? "Este producto no admite clasificaciones."}
        </CardContent>
      </Card>
    );
  }

  if (data.attributes.length === 0) {
    return (
      <Card className="mt-6">
        <CardHeader><CardTitle>Clasificaciones</CardTitle></CardHeader>
        <CardContent className="text-sm text-muted-foreground">
          Esta categoría no tiene atributos configurados.
        </CardContent>
      </Card>
    );
  }

  const dirty = data.attributes.some((a) => effective(a.id, a.selected) !== a.selected);

  return (
    <Card className="mt-6">
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle>Clasificaciones</CardTitle>
        <Button size="sm" disabled={!dirty || save.isPending} onClick={() => save.mutate()}>
          Guardar
        </Button>
      </CardHeader>
      <CardContent className="grid gap-4 sm:grid-cols-2">
        {data.attributes.map((attr) => (
          <div key={attr.id} className="space-y-1.5">
            <Label>{attr.label}</Label>
            <Select
              value={(() => {
                const cur = effective(attr.id, attr.selected);
                return cur != null ? String(cur) : NONE;
              })()}
              onValueChange={(v) =>
                setOverrides((s) => ({ ...s, [attr.id]: v === NONE ? null : Number(v) }))
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="Sin asignar" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={NONE}>Sin asignar</SelectItem>
                {attr.values.map((v) => (
                  <SelectItem key={v.id} value={String(v.id)}>
                    {v.value}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
