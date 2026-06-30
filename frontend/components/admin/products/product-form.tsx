"use client";

import { useRouter } from "next/navigation";
import { Controller, useForm, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  createProduct,
  getProductFormOptions,
  updateProduct,
  type ProductFormValues,
} from "@/lib/api/admin/products";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const schema = z
  .object({
    parent_category_id: z.coerce.number().int().positive("Elegí una categoría"),
    category_id: z.coerce.number().int().positive("Elegí una subcategoría"),
    brand_id: z.coerce.number().int().positive("Elegí una marca"),
    supplier_id: z.coerce.number().int().positive("Elegí un proveedor"),
    name: z.string().min(1, "El nombre es obligatorio").max(200),
    description: z.string().optional(),
    purchase_price: z.coerce.number().min(0),
    sale_price: z.coerce.number().min(0),
    stock_current: z.coerce.number().int().min(0),
    stock_minimum: z.coerce.number().int().min(0),
    status: z.string().min(1),
    is_featured: z.boolean(),
  })
  .refine((d) => d.sale_price > d.purchase_price, {
    path: ["sale_price"],
    message: "Debe ser mayor al precio de compra",
  })
  .refine((d) => d.stock_current >= d.stock_minimum, {
    path: ["stock_current"],
    message: "Debe ser mayor o igual al stock mínimo",
  });

type FormValues = z.infer<typeof schema>;

function FieldError({ msg }: { msg?: string }) {
  return msg ? <p className="text-sm text-destructive">{msg}</p> : null;
}

export function ProductForm({
  productId,
  defaultValues,
  onSuccess,
}: {
  productId?: number | string;
  defaultValues?: Partial<ProductFormValues>;
  onSuccess?: () => void;
}) {
  const router = useRouter();
  const isEdit = productId !== undefined;

  const { data: options, isLoading } = useQuery({
    queryKey: ["product-form-options"],
    queryFn: getProductFormOptions,
  });

  const {
    register,
    handleSubmit,
    control,
    watch,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema) as Resolver<FormValues>,
    defaultValues: {
      status: "active",
      is_featured: false,
      ...defaultValues,
    } as FormValues,
  });

  const parentId = watch("parent_category_id");
  const subcategories = options?.subcategoriesByParent[String(parentId)] ?? [];

  const mutation = useMutation({
    mutationFn: (values: ProductFormValues) =>
      isEdit ? updateProduct(productId, values) : createProduct(values),
    onSuccess: () => {
      toast.success(isEdit ? "Producto actualizado" : "Producto creado");
      if (onSuccess) onSuccess();
      else router.push("/admin/products");
    },
    onError: (error) => {
      if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data?.errors ?? {};
        for (const [field, messages] of Object.entries(fieldErrors)) {
          setError(field as keyof FormValues, {
            message: Array.isArray(messages) ? String(messages[0]) : String(messages),
          });
        }
        return;
      }
      toast.error(
        (isAxiosError(error) && (error.response?.data?.message as string)) ||
          "No fue posible guardar el producto.",
      );
    },
  });

  if (isLoading || !options) {
    return <Skeleton className="h-96" />;
  }

  return (
    <Card>
      <CardContent className="pt-6">
        <form
          className="grid gap-6 md:grid-cols-2"
          onSubmit={handleSubmit((v) => mutation.mutate(v as ProductFormValues))}
          noValidate
        >
          <div className="flex flex-col gap-2 md:col-span-2">
            <Label htmlFor="name">Nombre</Label>
            <Input id="name" {...register("name")} aria-invalid={!!errors.name} />
            <FieldError msg={errors.name?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label>Categoría</Label>
            <Controller
              control={control}
              name="parent_category_id"
              render={({ field }) => (
                <Select
                  value={field.value ? String(field.value) : ""}
                  onValueChange={(v) => field.onChange(Number(v))}
                >
                  <SelectTrigger><SelectValue placeholder="Elegí categoría" /></SelectTrigger>
                  <SelectContent>
                    {options.categories.map((c) => (
                      <SelectItem key={c.category_id} value={String(c.category_id)}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
            <FieldError msg={errors.parent_category_id?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label>Subcategoría</Label>
            <Controller
              control={control}
              name="category_id"
              render={({ field }) => (
                <Select
                  value={field.value ? String(field.value) : ""}
                  onValueChange={(v) => field.onChange(Number(v))}
                  disabled={!parentId}
                >
                  <SelectTrigger><SelectValue placeholder={parentId ? "Elegí subcategoría" : "Elegí categoría primero"} /></SelectTrigger>
                  <SelectContent>
                    {subcategories.map((c) => (
                      <SelectItem key={c.category_id} value={String(c.category_id)}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
            <FieldError msg={errors.category_id?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label>Marca</Label>
            <Controller
              control={control}
              name="brand_id"
              render={({ field }) => (
                <Select value={field.value ? String(field.value) : ""} onValueChange={(v) => field.onChange(Number(v))}>
                  <SelectTrigger><SelectValue placeholder="Elegí marca" /></SelectTrigger>
                  <SelectContent>
                    {options.brands.map((b) => (
                      <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
            <FieldError msg={errors.brand_id?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label>Proveedor</Label>
            <Controller
              control={control}
              name="supplier_id"
              render={({ field }) => (
                <Select value={field.value ? String(field.value) : ""} onValueChange={(v) => field.onChange(Number(v))}>
                  <SelectTrigger><SelectValue placeholder="Elegí proveedor" /></SelectTrigger>
                  <SelectContent>
                    {options.suppliers.map((s) => (
                      <SelectItem key={s.supplier_id} value={String(s.supplier_id)}>{s.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
            <FieldError msg={errors.supplier_id?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="purchase_price">Precio de compra</Label>
            <Input id="purchase_price" type="number" step="0.01" {...register("purchase_price")} />
            <FieldError msg={errors.purchase_price?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="sale_price">Precio de venta</Label>
            <Input id="sale_price" type="number" step="0.01" {...register("sale_price")} aria-invalid={!!errors.sale_price} />
            <FieldError msg={errors.sale_price?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="stock_current">Stock actual</Label>
            <Input id="stock_current" type="number" {...register("stock_current")} aria-invalid={!!errors.stock_current} />
            <FieldError msg={errors.stock_current?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label htmlFor="stock_minimum">Stock mínimo</Label>
            <Input id="stock_minimum" type="number" {...register("stock_minimum")} />
            <FieldError msg={errors.stock_minimum?.message} />
          </div>

          <div className="flex flex-col gap-2">
            <Label>Estado</Label>
            <Controller
              control={control}
              name="status"
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {options.statuses.map((s) => (
                      <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>

          <div className="flex flex-col gap-2 md:col-span-2">
            <Label htmlFor="description">Descripción</Label>
            <Textarea id="description" rows={3} {...register("description")} />
          </div>

          <div className="flex items-center gap-3 md:col-span-2">
            <Controller
              control={control}
              name="is_featured"
              render={({ field }) => (
                <Switch id="is_featured" checked={field.value} onCheckedChange={field.onChange} />
              )}
            />
            <Label htmlFor="is_featured">Producto destacado</Label>
          </div>

          <div className="flex gap-2 md:col-span-2">
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? "Guardando…" : isEdit ? "Guardar cambios" : "Crear producto"}
            </Button>
            <Button type="button" variant="outline" onClick={() => router.push("/admin/products")}>
              Cancelar
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
