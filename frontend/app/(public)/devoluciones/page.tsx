import { LegalShell } from "@/components/storefront/legal-shell";

export const metadata = { title: "Cambios y devoluciones — Ciclo Finca 4" };

export default function DevolucionesPage() {
  return (
    <LegalShell title="Cambios y devoluciones" updated="Enero 2026">
      <h2>1. Cancelación antes del retiro</h2>
      <p>Podés cancelar un pedido pendiente antes de retirarlo desde «Mis facturas» o contactándonos.</p>
      <h2>2. Pedidos no retirados</h2>
      <p>Los pedidos reservados que no se retiren dentro del plazo se liberan automáticamente y el stock vuelve al catálogo.</p>
      <h2>3. Cambios de producto</h2>
      <p>Los cambios por talla, modelo o equivalencia están sujetos a stock disponible.</p>
      <h2>4. Devoluciones</h2>
      <p>Las devoluciones se gestionan en tienda según el estado del producto y las condiciones aplicables.</p>
    </LegalShell>
  );
}
