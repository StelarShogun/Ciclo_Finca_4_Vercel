export const queryKeys = {
  productFormOptions: ["product-form-options"] as const,
  adminProducts: (page: number, filters: Record<string, string>) => ["admin-products", page, filters] as const,
  adminOrders: (page: number, filters: Record<string, string>) => ["admin-orders", page, filters] as const,
  adminInventory: (page: number, filters: Record<string, string>) => ["admin-inventory", page, filters] as const,
  cart: ["cart"] as const,
  salesPerformance: (preset: string, applied: { from: string; to: string } | null) =>
    ["report-sales-performance", preset, applied] as const,
};
