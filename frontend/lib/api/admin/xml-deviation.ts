import { api } from "@/lib/api/client";

/** Flujo XML de desviación de precios (proveedores), port del web viejo. */

export type XmlDeviationItem = {
  found: boolean;
  product_id: number | null;
  name: string;
  sku: string | null;
  quantity: number;
  current_price: number;
  xml_price: number;
  difference_amount: number;
  difference_percentage: number;
  has_deviation: boolean;
  suggested_sale_price: number | null;
  current_sale_price: number;
  current_margin_pct: number;
  sale_price_increase: number;
};

export type XmlDeviationAnalysis = {
  items: XmlDeviationItem[];
  file_name: string;
  threshold_percentage: number;
};

export async function analyzeXmlDeviation(file: File, threshold: number) {
  const form = new FormData();
  form.append("xml_file", file);
  form.append("threshold", String(threshold));
  const { data } = await api.post("/api/v1/admin/supplier-orders/xml-deviation/analyse", form);
  return data.data as { analysisId: string; analysis: XmlDeviationAnalysis };
}

export async function applyXmlDeviation(input: {
  analysisId: string;
  updates: number[];
  salePrices: Record<number, string>;
  reason: string;
}) {
  const { data } = await api.post("/api/v1/admin/supplier-orders/xml-deviation/apply", {
    analysis_id: input.analysisId,
    updates: input.updates,
    sale_prices: input.salePrices,
    reason: input.reason || null,
  });
  return data as { data: { updated: number }; message: string };
}
