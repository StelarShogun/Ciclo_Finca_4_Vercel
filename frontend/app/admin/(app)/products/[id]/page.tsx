"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { Pencil, Star } from "lucide-react";

import { getProductDetail } from "@/lib/api/admin/products";
import { PageHeader } from "@/components/admin/page-header";
import { ProductGallery } from "@/components/admin/products/product-gallery";
import { ProductVariants } from "@/components/admin/products/product-variants";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function statusTone(status: string): StatusTone {
  return status === "active" ? "success" : status === "out_of_stock" ? "warning" : "neutral";
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4 py-2 text-sm">
      <span className="text-muted-foreground">{label}</span>
      <span className="font-medium text-right">{value}</span>
    </div>
  );
}

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-product-detail", id],
    queryFn: () => getProductDetail(id),
    enabled: !!id,
  });

  if (isLoading) return <Skeleton className="h-96" />;
  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-8 text-center text-sm text-muted-foreground">
          No fue posible cargar el producto.
        </CardContent>
      </Card>
    );
  }

  const low = data.stock_current <= data.stock_minimum;

  return (
    <>
      <PageHeader
        title={data.name}
        description={data.sku ? `SKU ${data.sku}` : "Sin SKU"}
        actions={
          <Button asChild>
            <Link href={`/admin/products/${id}/edit`}>
              <Pencil className="h-4 w-4" />
              Editar
            </Link>
          </Button>
        }
      />

      <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
        {/* Imagen + galería */}
        <ProductGallery productId={id} />

        {/* Información */}
        <Card>
          <CardHeader className="flex flex-row items-center gap-2 space-y-0">
            <CardTitle>Información</CardTitle>
            <StatusBadge tone={statusTone(data.status)}>{data.status}</StatusBadge>
            {data.is_featured && (
              <span className="inline-flex items-center gap-1 text-xs text-amber-600">
                <Star className="h-3 w-3 fill-amber-400 text-amber-400" /> Destacado
              </span>
            )}
          </CardHeader>
          <CardContent className="divide-y">
            <Row label="Categoría" value={data.category?.name ?? "—"} />
            <Row label="Proveedor" value={data.supplier?.name ?? "—"} />
            <Row label="Marca" value={data.brands?.[0]?.name ?? "—"} />
            <Row label="Precio de venta" value={crc.format(Number(data.sale_price))} />
            <Row label="Precio de compra" value={crc.format(Number(data.purchase_price))} />
            <Row
              label="Stock"
              value={
                <StatusBadge tone={low ? "danger" : "neutral"}>
                  {data.stock_current} / mín {data.stock_minimum}
                </StatusBadge>
              }
            />
            {data.description && (
              <div className="py-2 text-sm">
                <p className="mb-1 text-muted-foreground">Descripción</p>
                <p>{data.description}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Variantes */}
      <ProductVariants productId={Number(id)} variants={data.variants} />

      {/* Clasificaciones */}
      {data.classification_values && data.classification_values.length > 0 && (
        <Card className="mt-6">
          <CardHeader><CardTitle>Clasificaciones</CardTitle></CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {data.classification_values.map((c) => (
              <span key={c.id} className="rounded-md border px-2 py-1 text-sm">
                {c.dimension?.name ? `${c.dimension.name}: ` : ""}
                {c.value ?? c.id}
              </span>
            ))}
          </CardContent>
        </Card>
      )}
    </>
  );
}
