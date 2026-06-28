import { api } from "@/lib/api/client";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

// --- Desempeño de ventas (KPIs vs periodo anterior) ---

export type SalesMetrics = { sales_count: number; revenue: number };

export type SalesComparison = {
  revenue_change_percent: number | null;
  sales_count_change_percent: number | null;
  revenue_trend: "up" | "down" | "flat";
  sales_count_trend: "up" | "down" | "flat";
};

export type SalesPerformance = {
  preset: string;
  current_period: { label: string };
  previous_period: { label: string };
  current_metrics: SalesMetrics;
  previous_metrics: SalesMetrics;
  comparison: SalesComparison;
};

export async function getSalesPerformance(
  preset: string,
  from?: string,
  to?: string,
): Promise<SalesPerformance> {
  const { data } = await api.get("/api/v1/admin/reports/sales-performance", {
    params: { preset, ...(preset === "custom" ? { from, to } : {}) },
  });
  return data.data as SalesPerformance;
}

// --- Productos vendidos ---

export type ProductSalesRow = {
  product_id: number;
  name: string;
  sku: string;
  units_sold: number;
  revenue: number;
};

export type ProductSales = {
  period: string;
  sort: string;
  dir: string;
  top10: ProductSalesRow[];
  rows: ProductSalesRow[];
  pagination: { page: number; per_page: number; total: number; last_page: number };
};

export async function getProductSales(params: {
  period?: string;
  sort?: string;
  dir?: string;
  q?: string;
  page?: number;
}): Promise<ProductSales> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/reports/product-sales", { params: clean });
  return data.data as ProductSales;
}

export function productSalesPdfUrl(period: string, q = ""): string {
  const qs = new URLSearchParams({ period, ...(q ? { q } : {}) }).toString();
  return `${API_URL}/reports/productos-vendidos/pdf?${qs}`;
}

export function productSalesExcelUrl(period: string, q = ""): string {
  const qs = new URLSearchParams({ period, ...(q ? { q } : {}) }).toString();
  return `${API_URL}/reports/productos-vendidos/excel?${qs}`;
}

// --- Ventas por categoría ---

export type CategorySalesRow = {
  category_id: number;
  category_name: string;
  total_units: number;
  total_revenue: number;
  percentage: number;
};

export type CategorySales = {
  rows: CategorySalesRow[];
  grandTotal: number;
  totalUnits: number;
  chartData: { label: string; value: number; percent: number }[];
};

export async function getCategorySales(dateRange = "month"): Promise<CategorySales> {
  const { data } = await api.get("/api/v1/admin/reports/category-sales", {
    params: { date_range: dateRange },
  });
  return data.data as CategorySales;
}
