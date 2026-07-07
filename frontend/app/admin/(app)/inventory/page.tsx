"use client";

import { Boxes, PackageX, TriangleAlert, Upload } from "lucide-react";

import { ImportModal } from "@/components/admin/inventory/import-modal";
import { StockAdjust } from "@/components/admin/inventory/stock-adjust";
import { MetricCard } from "@/components/admin/metric-card";
import { PageHeader } from "@/components/admin/page-header";
import { Button } from "@/components/ui/button";
import { InventoryTable } from "@/features/admin-inventory/components/InventoryTable";
import { InventoryToolbar } from "@/features/admin-inventory/components/InventoryToolbar";
import { useAdminInventoryPage } from "@/features/admin-inventory/hooks/useAdminInventoryPage";

export default function InventoryPage() {
  const page = useAdminInventoryPage();
  const { data, isLoading, isError } = page.inventoryQuery;

  return (
    <>
      <PageHeader
        kicker="Productos"
        icon="fa-warehouse"
        title="Inventario"
        description="Stock y ajustes manuales con movimientos."
        actions={
          <Button variant="outline" onClick={() => page.setImportOpen(true)}>
            <Upload className="h-4 w-4" />
            Importar
          </Button>
        }
      />
      <ImportModal open={page.importOpen} onClose={() => page.setImportOpen(false)} />

      {data && (
        <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <MetricCard label="Productos" value={String(data.inventorySummary.total)} icon={Boxes} />
          <MetricCard label="Activos" value={String(data.inventorySummary.active)} icon={Boxes} />
          <MetricCard label="Stock bajo" value={String(data.inventorySummary.lowStock)} icon={TriangleAlert} />
          <MetricCard label="Agotados" value={String(data.inventorySummary.outOfStock)} icon={PackageX} />
        </div>
      )}

      <InventoryToolbar
        search={page.search}
        stockStatus={page.stockStatus}
        view={page.view}
        onSearchChange={page.setSearch}
        onStockStatusChange={page.setStockStatus}
        onViewChange={page.setView}
      />

      <InventoryTable
        data={data}
        isLoading={isLoading}
        isError={isError}
        view={page.view}
        onPageChange={page.setPage}
        onAdjust={page.setAdjust}
      />

      <StockAdjust product={page.adjust} open={page.adjust !== null} onClose={() => page.setAdjust(null)} />
    </>
  );
}
