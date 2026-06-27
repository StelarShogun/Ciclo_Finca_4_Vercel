"use client";

import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";

import { getProduct, type ProductFormValues } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { ProductForm } from "@/components/admin/products/product-form";
import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";

function toDefaults(p: Record<string, unknown>): Partial<ProductFormValues> {
  const num = (v: unknown) => (v == null ? undefined : Number(v));
  const category = p.category as Record<string, unknown> | null | undefined;
  const parent = category?.parent as Record<string, unknown> | null | undefined;
  return {
    parent_category_id: num(p.parent_category_id) ?? num(parent?.category_id),
    category_id: num(p.category_id),
    brand_id: num(p.brand_id),
    supplier_id: num(p.supplier_id),
    name: (p.name as string) ?? "",
    description: (p.description as string) ?? "",
    purchase_price: num(p.purchase_price),
    sale_price: num(p.sale_price),
    stock_current: num(p.stock_current),
    stock_minimum: num(p.stock_minimum),
    status: (p.status as string) ?? "active",
    is_featured: Boolean(p.is_featured),
  };
}

export default function EditProductPage() {
  const params = useParams<{ id: string }>();
  const id = params.id;

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-product", id],
    queryFn: () => getProduct(id),
    enabled: !!id,
  });

  return (
    <>
      <PageHeader title="Editar producto" description="Modificá los datos básicos del producto." />
      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar el producto.
          </CardContent>
        </Card>
      ) : (
        <ProductForm productId={id} defaultValues={toDefaults(data)} />
      )}
    </>
  );
}
