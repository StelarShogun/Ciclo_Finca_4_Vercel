export const ELLIPSIS = "…" as const;

export type PageWindowItem = number | typeof ELLIPSIS;

/**
 * Ventana de páginas estilo Laravel UrlWindow: todas si son pocas; si no,
 * 2 primeras + bloque alrededor de la actual + 2 últimas, con ellipsis.
 */
export function pageWindow(
  current: number,
  last: number,
  onEachSide = 2,
): PageWindowItem[] {
  const range = (from: number, to: number) =>
    Array.from({ length: to - from + 1 }, (_, i) => from + i);

  if (last <= onEachSide * 2 + 6) {
    return range(1, last);
  }

  const window = onEachSide * 2;
  if (current <= window + 2) {
    return [...range(1, window + 4), ELLIPSIS, last - 1, last];
  }
  if (current > last - (window + 2)) {
    return [1, 2, ELLIPSIS, ...range(last - (window + 3), last)];
  }
  return [1, 2, ELLIPSIS, ...range(current - onEachSide, current + onEachSide), ELLIPSIS, last - 1, last];
}

/** Clamp de "ir a página": NaN vuelve a la actual, fuera de rango se ajusta. */
export function clampPage(raw: string, current: number, last: number): number {
  const parsed = Number.parseInt(raw.trim(), 10);
  if (Number.isNaN(parsed)) return current;
  return Math.max(1, Math.min(last, parsed));
}
