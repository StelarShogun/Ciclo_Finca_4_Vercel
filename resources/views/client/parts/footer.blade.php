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
                    <li><i class="fas fa-store" aria-hidden="true"></i><span>Tienda física disponible</span></li>
                    <li><i class="fas fa-clock" aria-hidden="true"></i><span>Lun-Sáb (horario comercial)</span></li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i><span>Atención confiable y segura</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Ciclo Finca 4. Todos los derechos reservados.</p>
            <a href="{{ route('clients.catalog') }}" class="footer-bottom-cta">
                Explorar catálogo <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</footer>
