import { Head, Link } from '@inertiajs/react';

import { ClientLayout } from '@/shared/components/layout/ClientLayout';

type PrivacyProps = {
  legalTitle: string;
  legalUpdated: string;
  businessName: string;
  contactEmail: string;
};

export default function Privacy({ businessName, contactEmail, legalTitle, legalUpdated }: PrivacyProps) {
  return (
    <ClientLayout>
      <Head title={`${legalTitle} - Ciclo Finca 4`} />
      <article className="legal-page" aria-labelledby="legal-page-title">
        <div className="container legal-page-container">
          <header className="legal-page-header">
            <Link href="/" className="legal-page-back">
              <i className="fas fa-arrow-left" aria-hidden="true" />
              Volver al inicio
            </Link>
            <h1 id="legal-page-title" className="legal-page-title">{legalTitle}</h1>
            <p className="legal-page-updated">Última actualización: {legalUpdated}</p>
          </header>

          <div className="legal-page-body">
            <p>
              {businessName} respeta su privacidad y trata los datos personales conforme a los principios de la Ley N.º 8968 de Protección de la Persona frente al Tratamiento de sus Datos Personales de Costa Rica y buenas prácticas de transparencia.
            </p>

            <h2>1. Responsable del tratamiento</h2>
            <p>
              <strong>{businessName}</strong><br />
              Correo de contacto: <a href={`mailto:${contactEmail}`}>{contactEmail}</a>
            </p>

            <h2>2. Datos que recopilamos</h2>
            <ul>
              <li>Identificación: nombre, apellidos, correo electrónico.</li>
              <li>Cuenta y seguridad: contraseña cifrada, códigos de verificación, sesión de inicio.</li>
              <li>Compras: historial de pedidos, facturas, método de pago seleccionado, productos en carrito.</li>
              <li>Preferencias: favoritos, reseñas de productos, notificaciones del sistema.</li>
              <li>Técnicos: dirección IP, cookies de sesión y registros necesarios para operar el sitio.</li>
            </ul>

            <h2>3. Finalidades del tratamiento</h2>
            <ul>
              <li>Crear y administrar su cuenta de cliente.</li>
              <li>Procesar pedidos, reservar stock y coordinar retiro en tienda.</li>
              <li>Enviar notificaciones sobre el estado del pedido o de su cuenta.</li>
              <li>Atender consultas, reclamos y soporte post-venta.</li>
              <li>Mejorar la seguridad y el funcionamiento de la plataforma.</li>
            </ul>

            <h2>4. Base y conservación</h2>
            <p>
              El tratamiento se basa en la ejecución del servicio solicitado, su consentimiento (registro) y el cumplimiento de obligaciones legales aplicables. Conservamos los datos mientras mantenga una cuenta activa y el tiempo necesario para facturación, garantías o defensa de reclamos.
            </p>
          </div>

          <nav className="legal-page-related" aria-label="Documentos relacionados">
            <ul className="legal-page-related-list">
              <li><Link href="/legal/terminos">Términos y condiciones</Link></li>
              <li><Link href="/legal/privacidad">Política de privacidad</Link></li>
              <li><Link href="/legal/cambios-devoluciones">Cambios y devoluciones</Link></li>
              <li><Link href="/contacto">Contacto</Link></li>
            </ul>
          </nav>
        </div>
      </article>
    </ClientLayout>
  );
}

