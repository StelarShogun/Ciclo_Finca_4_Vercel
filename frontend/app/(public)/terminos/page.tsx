import { LegalShell } from "@/components/storefront/legal-shell";

export const metadata = { title: "Términos y condiciones — Ciclo Finca 4" };

export default function TerminosPage() {
  return (
    <LegalShell title="Términos y condiciones" updated="Enero 2026">
      <h2>1. Uso del sitio</h2>
      <p>Al navegar y usar la tienda de Ciclo Finca 4 aceptás estos términos. El catálogo es informativo y los pedidos se preparan para retiro en tienda.</p>
      <h2>2. Cuenta de cliente</h2>
      <p>Sos responsable de la confidencialidad de tu cuenta y de la veracidad de los datos. Podemos suspender cuentas ante usos indebidos.</p>
      <h2>3. Catálogo, precios y disponibilidad</h2>
      <p>Los precios y el stock se muestran en tiempo real y pueden variar. La disponibilidad final se confirma al preparar el pedido.</p>
      <h2>4. Pedidos, pago y retiro en tienda</h2>
      <p>Los pedidos quedan reservados por un tiempo limitado. El pago se realiza según el método elegido y el retiro se hace en la tienda física.</p>
      <h2>5. Cambios y devoluciones</h2>
      <p>Aplican las condiciones descritas en la política de cambios y devoluciones.</p>
    </LegalShell>
  );
}
