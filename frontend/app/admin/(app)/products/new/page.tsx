"use client";

import { PageHeader } from "@/components/admin/page-header";
import { ProductForm } from "@/components/admin/products/product-form";

export default function NewProductPage() {
  return (
    <>
      <PageHeader
        title="Nuevo producto"
        description="Datos básicos. Galería, variantes y clasificaciones se editan luego."
      />
      <ProductForm />
    </>
  );
}
