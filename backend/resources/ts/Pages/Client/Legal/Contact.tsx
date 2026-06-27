import { Head, Link } from '@inertiajs/react';

import { ClientLayout } from '@/shared/components/layout/ClientLayout';

type ContactProps = {
  legalTitle: string;
  legal: {
    business_name?: string;
    store_hours?: string;
    contact_email?: string;
    contact_phone?: string;
  };
};

function stripSpaces(value: string) {
  return value.replace(/\s+/g, '');
}

export default function Contact({ legal, legalTitle }: ContactProps) {
  const businessName = legal.business_name || 'Ciclo Finca 4';
  const storeHours = legal.store_hours || '';
  const contactEmail = legal.contact_email || '';
  const contactPhone = legal.contact_phone || '';

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
          </header>

          <div className="legal-page-body">
            <p>Estamos para ayudarle con pedidos, disponibilidad, asesoría técnica y reclamos relacionados con su cuenta.</p>

            <h2>Tienda</h2>
            <p>
              <strong>{businessName}</strong><br />
              Retiro de pedidos en tienda física{storeHours ? ` (horario: ${storeHours})` : ''}.
            </p>

            <h2>Correo electrónico</h2>
            <p>
              {contactEmail ? <a href={`mailto:${contactEmail}`}>{contactEmail}</a> : 'No disponible'}
            </p>

            {contactPhone ? (
              <>
                <h2>Teléfono</h2>
                <p>
                  <a href={`tel:${stripSpaces(contactPhone)}`}>{contactPhone}</a>
                </p>
              </>
            ) : null}

            <h2>Consultas frecuentes</h2>
            <ul>
              <li>Estado de su pedido: revise «Mis facturas» si tiene cuenta activa.</li>
              <li>Cambios y devoluciones: <Link href="/legal/cambios-devoluciones">Política de cambios y devoluciones</Link>.</li>
              <li>Datos personales: <Link href="/legal/privacidad">Política de privacidad</Link>.</li>
            </ul>

            <h2>Tiempo de respuesta</h2>
            <p>Procuramos responder en un plazo de 1 a 2 días hábiles. Los mensajes recibidos fuera del horario comercial se atienden el siguiente día laboral.</p>
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

