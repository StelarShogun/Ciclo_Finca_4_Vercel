import { Head, Link } from '@inertiajs/react';

import { ClientLayout } from '@/Layouts/ClientLayout';

type ReturnsProps = {
  legalTitle: string;
  legalUpdated: string;
  businessName: string;
};

export default function Returns({ businessName, legalTitle, legalUpdated }: ReturnsProps) {
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
              Esta política describe cómo gestionamos <strong>cambios, devoluciones y cancelaciones</strong> de pedidos realizados a través del sitio de {businessName} con retiro en tienda.
            </p>

            <h2>1. Cancelación antes del retiro</h2>
            <p>
              Puede solicitar la cancelación de su pedido mientras no haya sido entregado en tienda, contactándonos por <Link href="/contacto">Contacto</Link> o en persona. Si el pedido ya fue preparado o separado en bodega, podrían aplicar cargos o restricciones según el estado del encargo.
            </p>

            <h2>2. Pedidos no retirados</h2>
            <p>
              Si no retira su pedido en el plazo indicado al confirmar disponibilidad, la Tienda podrá cancelarlo y liberar el stock. Le notificaremos cuando sea posible.
            </p>

            <h2>3. Cambios de producto</h2>
            <ul>
              <li>Los cambios por talla, modelo o equivalencia están sujetos a stock disponible.</li>
              <li>El producto debe estar sin uso, con empaque original cuando aplique y dentro del plazo informado en tienda (habitualmente 7 a 15 días calendario salvo excepción comercial).</li>
              <li>Debe presentar comprobante de compra o factura asociada a su cuenta.</li>
            </ul>

            <h2>4. Devoluciones</h2>
            <p>
              Las devoluciones con reintegro se evalúan caso por caso. No aplican devolución en productos personalizados, instalados o usados, salvo defecto de fábrica. Los accesorios de higiene personal o sellados abiertos pueden quedar excluidos por razones sanitarias.
            </p>

            <h2>5. Productos defectuosos o garantía</h2>
            <p>
              Si el producto presenta falla de fabricación, gestionamos la garantía según política del proveedor o fabricante. Conserve la factura y el producto para revisión en taller o mostrador.
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

