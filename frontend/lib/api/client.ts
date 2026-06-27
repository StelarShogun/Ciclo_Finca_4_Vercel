import axios from "axios";

/**
 * Cliente HTTP de la API Laravel (Sanctum cookie / SPA).
 *
 * - withCredentials: envía/acepta la cookie de sesión.
 * - withXSRFToken: axios lee la cookie XSRF-TOKEN y la manda como X-XSRF-TOKEN
 *   (necesario cross-origin). En dev funciona porque las cookies ignoran el
 *   puerto: localhost:8080 y localhost:3000 comparten host "localhost".
 */
export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
  },
});
