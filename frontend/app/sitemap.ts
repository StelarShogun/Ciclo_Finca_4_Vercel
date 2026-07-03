import type { MetadataRoute } from "next";

/**
 * Sitemap para Google: páginas públicas estáticas + fichas de producto
 * (IDs públicos ULID del catálogo). Se genera server-side contra el backend
 * (BACKEND_URL en Vercel, NEXT_PUBLIC_API_URL en dev local).
 */
const SITE = process.env.NEXT_PUBLIC_SITE_URL ?? "https://ciclo.dpdns.org";
const API = process.env.BACKEND_URL ?? process.env.NEXT_PUBLIC_API_URL ?? "";

type CatalogPage = {
  data: {
    products: { id: string }[];
    pagination: { currentPage: number; lastPage: number };
  };
};

async function productIds(): Promise<string[]> {
  if (!API) return [];
  const ids: string[] = [];
  try {
    let page = 1;
    let lastPage = 1;
    do {
      const res = await fetch(`${API}/api/v1/catalog?page=${page}`, {
        headers: { Accept: "application/json" },
        next: { revalidate: 3600 },
      });
      if (!res.ok) break;
      const json = (await res.json()) as CatalogPage;
      ids.push(...json.data.products.map((p) => p.id));
      lastPage = json.data.pagination.lastPage;
      page++;
    } while (page <= lastPage && page <= 50); // ponytail: tope 50 páginas; sitemap index si el catálogo crece más
  } catch {
    // Backend caído: sitemap parcial (solo estáticas) antes que un 500.
  }
  return ids;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticPages: MetadataRoute.Sitemap = [
    { url: `${SITE}/`, changeFrequency: "daily", priority: 1 },
    { url: `${SITE}/catalog`, changeFrequency: "daily", priority: 0.9 },
    { url: `${SITE}/contacto`, changeFrequency: "monthly", priority: 0.5 },
    { url: `${SITE}/devoluciones`, changeFrequency: "monthly", priority: 0.3 },
    { url: `${SITE}/privacidad`, changeFrequency: "yearly", priority: 0.3 },
    { url: `${SITE}/terminos`, changeFrequency: "yearly", priority: 0.3 },
  ];

  const products = (await productIds()).map((id) => ({
    url: `${SITE}/product/${id}`,
    changeFrequency: "weekly" as const,
    priority: 0.8,
  }));

  return [...staticPages, ...products];
}
