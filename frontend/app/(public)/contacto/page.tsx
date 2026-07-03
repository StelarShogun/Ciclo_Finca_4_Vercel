import { LegalShell } from "@/components/storefront/legal-shell";

export const metadata = { title: "Contacto — Ciclo Finca 4" };

export default function ContactoPage() {
  return (
    <LegalShell title="Contacto">
      <h2>Tienda</h2>
      <p>Atención y retiro de pedidos en nuestra tienda física.</p>
      <h2>Correo electrónico</h2>
      <p>Escribinos para consultas sobre productos, disponibilidad o tu pedido.</p>
      <h2>Teléfono / WhatsApp</h2>
      <p>Te asesoramos en la elección y preparación de tu compra.</p>
      <h2>Consultas frecuentes</h2>
      <ul>
        <li>Estado de tu pedido: revisá «Mis facturas» si tenés cuenta activa.</li>
        <li>Disponibilidad: el stock del catálogo se actualiza en tiempo real.</li>
        <li>Retiro: te avisamos cuando tu pedido esté listo para retirar.</li>
      </ul>
    </LegalShell>
  );
}
