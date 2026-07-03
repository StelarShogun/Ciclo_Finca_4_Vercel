import type { MetadataRoute } from "next";

const SITE = process.env.NEXT_PUBLIC_SITE_URL ?? "https://ciclo.dpdns.org";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      disallow: [
        "/admin",
        "/account",
        "/cart",
        "/checkout",
        "/favorites",
        "/invoices",
        "/notifications",
        "/profile",
        "/login",
        "/register",
        "/recovery",
        "/verify",
        "/api/",
      ],
    },
    sitemap: `${SITE}/sitemap.xml`,
  };
}
