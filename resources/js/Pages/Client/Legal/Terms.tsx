import { Head, Link } from '@inertiajs/react';

import { ClientLayout } from '@/shared/components/layout/ClientLayout';

type TermsProps = {
  legalTitle: string;
  legalUpdated: string;
  businessName: string;
  contactEmail: string;
};

export default function Terms({ businessName, contactEmail, legalTitle, legalUpdated }: TermsProps) {
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
              Estos Términos y condiciones regulan el uso del sitio web y los servicios de encargo con retiro en tienda
              ofrecidos por <strong>{businessName}</strong>. Al crear una cuenta, navegar el catálogo o confirmar un
              pedido, usted acepta estas condiciones.
            </p>

            <h2>1. Uso del sitio</h2>
            <p>El usuario se compromete a utilizar la plataforma de forma lícita, sin intentar vulnerar la seguridad, copiar contenidos de forma no autorizada ni suplantar identidades.</p>

            <h2>2. Cuenta de cliente</h2>
            <p>Para encargar productos debe registrarse con datos veraces. Usted es responsable de la confidencialidad de su contraseña y de las actividades realizadas con su cuenta.</p>

            <h2>3. Catálogo, precios y disponibilidad</h2>
            <p>Los precios se muestran en colones costarricenses e incluyen la información disponible al momento de la consulta. La disponibilidad de stock puede variar.</p>

            <h2>4. Pedidos, pago y retiro en tienda</h2>
            <ul>
              <li>El pedido en línea constituye una <strong>solicitud de encargo</strong>, no una venta final hasta confirmación.</li>
              <li>Los métodos de pago indicados se coordinan según disponibilidad en tienda.</li>
              <li>El retiro es presencial en la tienda, en el horario acordado tras la notificación de disponibilidad.</li>
            </ul>

            <h2>5. Reserva y cancelación de pedidos</h2>
            <p>Los productos reservados pueden tener un plazo limitado para retiro. Si no retira a tiempo, la tienda podrá cancelar el pedido y liberar el stock.</p>

            <h2>6. Garantías y asesoría</h2>
            <p>Los productos nuevos se rigen por las garantías del fabricante o importador cuando aplique. La asesoría en tienda es orientativa.</p>

            <h2>7. Reclamos y contacto</h2>
            <p>
              Para consultas puede escribir a <a href={`mailto:${contactEmail}`}>{contactEmail}</a> o visitar la página de <Link href="/contacto">Contacto</Link>.
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
