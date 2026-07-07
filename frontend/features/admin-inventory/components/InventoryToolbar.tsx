"use client";

import { Search } from "lucide-react";

import { ViewToggle, type ViewMode } from "@/components/admin/view-toggle";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ALL_INVENTORY_FILTER } from "../hooks/useAdminInventoryPage";

export function InventoryToolbar({
  search,
  stockStatus,
  view,
  onSearchChange,
  onStockStatusChange,
  onViewChange,
}: {
  search: string;
  stockStatus: string;
  view: ViewMode;
  onSearchChange: (value: string) => void;
  onStockStatusChange: (value: string) => void;
  onViewChange: (value: ViewMode) => void;
}) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-3">
      <div className="relative w-full max-w-xs">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Buscar por nombre o SKU…"
          className="pl-8"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
        />
      </div>
      <Select value={stockStatus} onValueChange={onStockStatusChange}>
        <SelectTrigger className="w-44" size="sm">
          <SelectValue placeholder="Stock" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={ALL_INVENTORY_FILTER}>Todo el stock</SelectItem>
          <SelectItem value="in-stock">En stock</SelectItem>
          <SelectItem value="low">Stock bajo</SelectItem>
          <SelectItem value="out">Sin stock</SelectItem>
        </SelectContent>
      </Select>
      <div className="ml-auto">
        <ViewToggle view={view} onChange={onViewChange} />
      </div>
    </div>
  );
}
