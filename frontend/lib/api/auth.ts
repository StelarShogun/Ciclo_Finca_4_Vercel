import { api } from "./client";

export type AuthUser = {
  user_id: number;
  name: string;
  first_surname: string;
  second_surname: string | null;
  gmail: string;
  [key: string]: unknown;
};

export type Me = { type: "admin" | "client"; user: AuthUser };

export type AdminLoginInput = {
  gmail: string;
  password: string;
  "g-recaptcha-response"?: string;
};

/** Obtiene la cookie XSRF-TOKEN antes de cualquier POST (flujo Sanctum SPA). */
export async function csrfCookie(): Promise<void> {
  await api.get("/sanctum/csrf-cookie");
}

export async function adminLogin(input: AdminLoginInput): Promise<Me> {
  await csrfCookie();
  const { data } = await api.post("/api/v1/auth/admin/login", input);
  return data.data as Me;
}

export async function adminLogout(): Promise<void> {
  await api.post("/api/v1/auth/admin/logout");
}

export type ClientLoginInput = {
  gmail: string;
  password: string;
  "g-recaptcha-response"?: string;
};

/** Login de cliente: la sesión se establece por cookie; luego /me da el usuario. */
export async function clientLogin(input: ClientLoginInput): Promise<Me> {
  await csrfCookie();
  await api.post("/api/v1/auth/login", input);
  return me();
}

export async function clientLogout(): Promise<void> {
  await api.post("/api/v1/auth/logout");
}

export async function me(): Promise<Me> {
  const { data } = await api.get("/api/v1/me");
  return data.data as Me;
}
