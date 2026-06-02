<footer class="cliente-footer" aria-label="Pie de página">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <div class="footer-brand-link" aria-label="Marca Ciclo Finca 4">
                    <span class="footer-brand-media" aria-hidden="true">
                        <img src="{{ asset('assets/images/brand/logo-ciclo-finca-icon-transparent.png') }}"
                             alt=""
                             class="footer-logo-img"
                             width="96"
                             height="96"
                             loading="lazy"
                             decoding="async"
                             data-fallback-src="{{ asset('assets/images/brand/logo-ciclo-finca-icon.png') }}"
                             onerror="if(this.dataset.fallbackSrc && this.src !== this.dataset.fallbackSrc){this.src=this.dataset.fallbackSrc;return;}this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                        <span class="footer-brand-mark" style="display:none;">CF4</span>
                    </span>
                    <div>
                        <h3 class="footer-brand-title">Ciclo Finca 4</h3>
                        <p class="footer-brand-text">Especialistas en bicicletas, componentes y retiro en tienda.</p>
                    </div>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navegación</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('clients.home') }}">Inicio</a></li>
                    <li><a href="{{ route('clients.catalog') }}">Catálogo</a></li>
                    @auth('clients')
                        <li><a href="{{ route('clients.cart') }}">Carrito</a></li>
                        <li><a href="{{ route('clients.profile') }}">Mi perfil</a></li>
                    @else
                        <li><a href="{{ route('login.show') }}">Iniciar sesión</a></li>
                        <li><a href="{{ route('clients.register.form') }}">Crear cuenta</a></li>
                    @endauth
                </ul>
            </div>

            <div class="footer-col">
                <h4>Servicio</h4>
                <ul class="footer-links">
                    <li><span class="footer-static-item">Asesoría personalizada</span></li>
                    <li><span class="footer-static-item">Preparación en taller</span></li>
                    <li><span class="footer-static-item">Retiro en tienda</span></li>
                    <li><span class="footer-static-item">Soporte post-retiro</span></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contacto</h4>
                <ul class="footer-links footer-contact">
                    <li>
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <a href="mailto:{{ config('cf4_legal.contact_email') }}">{{ config('cf4_legal.contact_email') }}</a>
                    </li>
                    <li><i class="fas fa-store" aria-hidden="true"></i><span>Tienda física — retiro de pedidos</span></li>
                    <li><i class="fas fa-clock" aria-hidden="true"></i><span>{{ config('cf4_legal.store_hours') }}</span></li>
                    <li>
                        <i class="fas fa-file-lines" aria-hidden="true"></i>
                        <a href="{{ route('clients.contact') }}">Formulario e información de contacto</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-start">
                <p>&copy; {{ date('Y') }} Ciclo Finca 4. Todos los derechos reservados.</p>
                <nav class="footer-legal" aria-label="Información legal">
                    <a href="{{ route('clients.legal.terms') }}">Términos y condiciones</a>
                    <span class="footer-legal-sep" aria-hidden="true">|</span>
                    <a href="{{ route('clients.legal.privacy') }}">Política de privacidad</a>
                    <span class="footer-legal-sep" aria-hidden="true">|</span>
                    <a href="{{ route('clients.legal.returns') }}">Cambios y devoluciones</a>
                    <span class="footer-legal-sep" aria-hidden="true">|</span>
                    <a href="{{ route('clients.contact') }}">Contacto</a>
                </nav>
            </div>
        </div>
    </div>
</footer>
