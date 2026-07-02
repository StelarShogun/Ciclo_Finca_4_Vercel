import { api } from "@/lib/api/client";

/** El home devuelve `category` como string y omite favoritos/isNew. */
export type HomeProduct = {
  id: string; // ID público (ULID)
  name: string;
  description: string | null;
  category: string;
  price: number;
  priceFormatted: string;
  stockCurrent: number;
  stockLabel: string;
  canBuy: boolean;
  sku: string | null;
  url: string;
  image: {
    fallback: string | null;
    desktopWebp: string | null;
    mobileWebp: string | null;
    usesPlaceholder: boolean;
    placeholderIconClass: string | null;
  };
  reviews: { avg: number; count: number };
};

export type HomeCategory = {
  id: string;
  name: string;
  description: string | null;
  url: string;
  iconClass: string | null;
  children: { id: string; name: string; url: string }[];
};

export type HomeHero = {
  title: string;
  emphasis: string;
  subtitle: string;
  description: string;
};

export type HomeResponse = {
  featuredProducts: HomeProduct[];
  categories: HomeCategory[];
  showGuestRegisterCta: boolean;
  hero: HomeHero;
};

export async function getHome(): Promise<HomeResponse> {
  const { data } = await api.get("/api/v1/home");
  return data.data as HomeResponse;
}
