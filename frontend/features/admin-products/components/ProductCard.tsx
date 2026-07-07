"use client";

import { AdminCard } from "@/components/admin/admin-card";
import { FeaturedStar } from "@/components/admin/products/featured-star";
import { ProductRowActions } from "@/components/admin/products/product-row-actions";
import { StatusBadge } from "@/components/admin/status-badge";
import { type AdminProduct, mediaUrl } from "@/lib/api/admin/products";
import { formatCRC } from "@/lib/money";
import { productStatusLabel, productStatusTone } from "../product-columns";

export function ProductCard({
  product,
  onEdit,
  onView,
}: {
  product: AdminProduct;
  onEdit: (id: number) => void;
  onView: (id: number) => void;
}) {
  const img = mediaUrl(product.image_url);
  const low = product.stock_current <= product.stock_minimum;

  return (
    <AdminCard
      media={
        <div className="relative h-14 w-14 shrink-0">
          <FeaturedStar productId={product.product_id} isFeatured={product.is_featured} />
          <div className="h-full w-full overflow-hidden rounded-lg border bg-muted">
            {img && !product.uses_placeholder ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={img} alt="" className="h-full w-full object-cover" />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-xl">🚲</div>
            )}
          </div>
        </div>
      }
      title={product.name}
      subtitle={product.sku ?? "Sin SKU"}
      badge={<StatusBadge tone={productStatusTone(product.status)}>{productStatusLabel(product.status)}</StatusBadge>}
      meta={
        <>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Categoría</span>
            <span>{product.category?.name ?? "—"}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Precio</span>
            <span className="font-medium">{formatCRC(product.sale_price)}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-muted-foreground">Stock</span>
            <StatusBadge tone={low ? "danger" : "neutral"}>{product.stock_current}</StatusBadge>
          </div>
        </>
      }
      actions={<ProductRowActions product={product} onEdit={onEdit} onView={onView} />}
    />
  );
}
