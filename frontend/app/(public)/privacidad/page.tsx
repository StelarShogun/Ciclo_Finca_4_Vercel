import { LegalShell } from "@/components/storefront/legal-shell";

export const metadata = { title: "Política de privacidad — Ciclo Finca 4" };

export default function PrivacidadPage() {
  return (
    <LegalShell title="Política de privacidad" updated="Enero 2026">
      <h2>1. Responsable del tratamiento</h2>
      <p>Ciclo Finca 4 es responsable de los datos personales que tratás en esta tienda.</p>
      <h2>2. Datos que recopilamos</h2>
      <ul>
        <li>Identificación: nombre, apellidos, correo electrónico.</li>
        <li>Cuenta y seguridad: contraseña cifrada, códigos de verificación, sesión de inicio.</li>
        <li>Pedidos: productos, montos y método de pago para el retiro en tienda.</li>
      </ul>
      <h2>3. Uso de los datos</h2>
      <p>Usamos tus datos para gestionar tu cuenta, procesar pedidos, enviar notificaciones del pedido y mejorar el servicio.</p>
      <h2>4. Conservación y seguridad</h2>
      <p>Conservamos los datos el tiempo necesario para la operación y aplicamos medidas de seguridad razonables.</p>
      <h2>5. Tus derechos</h2>
      <p>Podés solicitar acceso, rectificación o eliminación de tus datos contactándonos.</p>
    </LegalShell>
  );
}
