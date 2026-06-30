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

export type RegisterInput = {
  name: string;
  first_surname: string;
  second_surname?: string | null;
  gmail: string;
  password: string;
  password_confirmation: string;
  accept_terms: boolean;
};

export async function clientRegister(
  input: RegisterInput,
): Promise<{ success: boolean; pending_gmail: string; mail_warning: string | null }> {
  await csrfCookie();
  const { data } = await api.post("/api/v1/auth/register", input);
  return data;
}

/** Verifica el código de 6 dígitos; al validar, /me devuelve el cliente. */
export async function verifyCode(code: string): Promise<Me> {
  await api.post("/api/v1/auth/verify", { verification_code: code });
  return me();
}

export async function resendCode(): Promise<void> {
  await api.post("/api/v1/auth/verify/resend");
}

// ---- Recuperación de contraseña ----

export async function recoverySend(gmail: string) {
  await csrfCookie();
  const { data } = await api.post("/api/v1/auth/recovery", { gmail });
  return data;
}

export async function recoveryVerify(code: string) {
  const { data } = await api.post("/api/v1/auth/recovery/verify", { verification_code: code });
  return data;
}

export async function recoveryReset(newPassword: string, confirmation: string) {
  const { data } = await api.post("/api/v1/auth/recovery/reset", {
    new_password: newPassword,
    new_password_confirmation: confirmation,
  });
  return data;
}

export async function me(): Promise<Me> {
  const { data } = await api.get("/api/v1/me");
  return data.data as Me;
}
