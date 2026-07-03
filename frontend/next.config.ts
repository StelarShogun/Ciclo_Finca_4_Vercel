import type { NextConfig } from "next";

/**
 * BACKEND_URL (solo en Vercel): origen del Laravel. Con el proxy, el navegador
 * habla siempre con el dominio del front y las cookies de Sanctum quedan
 * first-party (los browsers bloquean cookies third-party → 419 CSRF).
 * En dev local no se define: axios apunta directo a NEXT_PUBLIC_API_URL.
 */
const backend = process.env.BACKEND_URL;

const nextConfig: NextConfig = {
  async rewrites() {
    if (!backend) return [];
    return [
      { source: "/api/:path*", destination: `${backend}/api/:path*` },
      { source: "/sanctum/:path*", destination: `${backend}/sanctum/:path*` },
    ];
  },
};

export default nextConfig;
