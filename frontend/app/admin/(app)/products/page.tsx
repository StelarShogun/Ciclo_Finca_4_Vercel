"use client";

import { Plus } from "lucide-react";

import { PageHeader } from "@/components/admin/page-header";
import { ProductFormDialog } from "@/components/admin/products/product-form-dialog";
import { ViewProductModal } from "@/components/admin/products/view-product-modal";
import { Button } from "@/components/ui/button";
import { ProductTable } from "@/features/admin-products/components/ProductTable";
import { ProductToolbar } from "@/features/admin-products/components/ProductToolbar";
import { useAdminProductsPage } from "@/features/admin-products/hooks/useAdminProductsPage";

export default function ProductsPage() {
  const page = useAdminProductsPage();
  const { data, isLoading, isError } = page.productsQuery;

  return (
    <>
      <PageHeader
        kicker="Catálogo"
        icon="fa-box"
        title="Productos"
        description="Catálogo del inventario."
        actions={
          <Button onClick={page.openCreate}>
            <Plus className="h-4 w-4" />
            Nuevo producto
          </Button>
        }
      />

      <ProductToolbar
        search={page.search}
        status={page.status}
        stockStatus={page.stockStatus}
        view={page.view}
        onSearchChange={page.setSearch}
        onStatusChange={page.setStatus}
        onStockStatusChange={page.setStockStatus}
        onViewChange={page.setView}
      />

      <ProductTable
        data={data}
        isLoading={isLoading}
        isError={isError}
        view={page.view}
        onPageChange={page.setPage}
        onEdit={page.openEdit}
        onView={page.openView}
      />

      <ProductFormDialog open={page.formOpen} productId={page.editId} onClose={() => page.setFormOpen(false)} />
      <ViewProductModal
        productId={page.viewId}
        open={page.viewId !== null}
        onClose={() => page.setViewId(null)}
        onEdit={page.openEdit}
      />
    </>
  );
}
