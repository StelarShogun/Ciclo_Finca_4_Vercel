<footer class="cliente-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Ciclo Finca 4</h4>
                <p>Tu tienda especializada en bicicletas y accesorios para ciclismo.</p>
            </div>
            <div class="footer-section">
                <h4>Enlaces</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('clients.home') }}">Inicio</a></li>
                    <li><a href="{{ route('clients.catalog') }}">Catálogo</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contacto</h4>
                <p>Visítanos en nuestra tienda física</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Ciclo Finca 4. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>
