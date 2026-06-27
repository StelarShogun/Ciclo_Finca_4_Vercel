import { Link } from '@inertiajs/react';

import { FooterColumn } from '@/shared/components/client/footer/FooterColumn';
import type { InertiaSharedProps } from '@/shared/types/models';

type ClientFooterProps = {
  auth: InertiaSharedProps['auth'];
};

export function ClientFooter({ auth }: ClientFooterProps) {
  return (
    <footer className="cliente-footer" aria-label="Pie de página">
      <div className="footer-container">
        <div className="footer-top">
          <div className="footer-brand">
            <div className="footer-brand-link" aria-label="Marca Ciclo Finca 4">
              <span className="footer-brand-media" aria-hidden="true">
                <img src="/assets/images/brand/logo-ciclo-finca-icon-transparent.png" alt="" className="footer-logo-img" width={96} height={96} loading="lazy" decoding="async" />
              </span>
              <div>
                <h3 className="footer-brand-title">Ciclo Finca 4</h3>
                <p className="footer-brand-text">Especialistas en bicicletas, componentes y retiro en tienda.</p>
              </div>
            </div>
          </div>

          <FooterColumn
            title="Navegación"
            links={
              auth.client
                ? [
                    ['Inicio', '/'],
                    ['Catálogo', '/catalog'],
                    ['Carrito', '/cart'],
                    ['Mi perfil', '/profile'],
                  ]
                : [
                    ['Inicio', '/'],
                    ['Catálogo', '/catalog'],
                    ['Iniciar sesión', '/login'],
                    ['Crear cuenta', '/register'],
                  ]
            }
          />
          <div className="footer-col">
            <h4>Servicio</h4>
            <ul className="footer-links">
              <li><span className="footer-static-item">Asesoría personalizada</span></li>
              <li><span className="footer-static-item">Preparación en taller</span></li>
              <li><span className="footer-static-item">Retiro en tienda</span></li>
              <li><span className="footer-static-item">Soporte post-retiro</span></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>Contacto</h4>
            <ul className="footer-links footer-contact">
              <li><i className="fas fa-store" aria-hidden="true" /><span>Tienda física - retiro de pedidos</span></li>
              <li><i className="fas fa-file-lines" aria-hidden="true" /><Link href="/contacto">Formulario e información de contacto</Link></li>
            </ul>
          </div>
        </div>

        <div className="footer-bottom">
          <div className="footer-bottom-start">
            <p>© 2026 Ciclo Finca 4. Todos los derechos reservados.</p>
            <nav className="footer-legal" aria-label="Información legal">
              <Link href="/legal/terminos">Términos y condiciones</Link>
              <span className="footer-legal-sep" aria-hidden="true">|</span>
              <Link href="/legal/privacidad">Política de privacidad</Link>
              <span className="footer-legal-sep" aria-hidden="true">|</span>
              <Link href="/legal/cambios-devoluciones">Cambios y devoluciones</Link>
              <span className="footer-legal-sep" aria-hidden="true">|</span>
              <Link href="/contacto">Contacto</Link>
            </nav>
          </div>
        </div>
      </div>
    </footer>
  );
}
