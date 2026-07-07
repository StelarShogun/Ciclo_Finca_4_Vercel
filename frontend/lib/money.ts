const crcFormatter = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

export function formatCRC(value: number | string): string {
  return crcFormatter.format(Number(value) || 0);
}
