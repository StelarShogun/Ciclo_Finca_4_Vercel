export const OTP_LENGTH = 6;

/**
 * Aplica una entrada (tecleo o pegado) sobre las cajas OTP desde `index`.
 * Devuelve los dígitos resultantes y la caja que debe recibir el foco.
 */
export function applyOtpInput(
  digits: string[],
  index: number,
  raw: string,
): { digits: string[]; focus: number | null } {
  const cleaned = raw.replace(/\D/g, "");
  const next = [...digits];

  if (!cleaned) {
    next[index] = "";
    return { digits: next, focus: null };
  }

  let cursor = index;
  for (const ch of cleaned.slice(0, OTP_LENGTH - index)) {
    next[cursor] = ch;
    cursor += 1;
  }
  return { digits: next, focus: Math.min(OTP_LENGTH - 1, cursor) };
}
