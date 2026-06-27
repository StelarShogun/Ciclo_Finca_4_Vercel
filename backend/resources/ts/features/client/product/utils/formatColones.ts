export function formatColones(amount: number): string {
  return `₡${Number(amount).toLocaleString('es-CR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })}`;
}
