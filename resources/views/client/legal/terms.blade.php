@extends('client.layouts.legal')

@section('legal_content')
    <p>
        Estos Términos y condiciones regulan el uso del sitio web y los servicios de encargo con retiro en tienda
        ofrecidos por <strong>{{ config('cf4_legal.business_name') }}</strong> (en adelante, «la Tienda»).
        Al crear una cuenta, navegar el catálogo o confirmar un pedido, usted acepta estas condiciones.
    </p>

    <h2>1. Uso del sitio</h2>
    <p>
        El usuario se compromete a utilizar la plataforma de forma lícita, sin intentar vulnerar la seguridad,
        copiar contenidos de forma no autorizada ni suplantar identidades. La Tienda puede suspender cuentas
        ante uso indebido o fraude.
    </p>

    <h2>2. Cuenta de cliente</h2>
    <p>
        Para encargar productos debe registrarse con datos veraces. Usted es responsable de la confidencialidad
        de su contraseña y de las actividades realizadas con su cuenta.
    </p>

    <h2>3. Catálogo, precios y disponibilidad</h2>
    <p>
        Los precios se muestran en colones costarricenses (₡) e incluyen la información disponible al momento
        de la consulta. La disponibilidad de stock puede variar; la confirmación del pedido está sujeta a
        verificación en tienda.
    </p>

    <h2>4. Pedidos, pago y retiro en tienda</h2>
    <ul>
        <li>El pedido en línea constituye una <strong>solicitud de encargo</strong>, no una venta final hasta confirmación.</li>
        <li>Los métodos de pago indicados (efectivo, SINPE Móvil, transferencia) se coordinan según disponibilidad en tienda.</li>
        <li>El retiro es <strong>presencial en la tienda</strong>, en el horario acordado tras la notificación de disponibilidad.</li>
        <li>La Tienda puede contactarle para confirmar datos, stock o forma de pago.</li>
    </ul>

    <h2>5. Reserva y cancelación de pedidos</h2>
    <p>
        Los productos reservados en su pedido pueden tener un plazo limitado para retiro. Si no retira en el
        plazo informado, la Tienda podrá cancelar el pedido y liberar el stock. Usted puede solicitar cancelación
        antes del retiro, sujeta a estado del pedido (véase también la política de cambios y devoluciones).
    </p>

    <h2>6. Garantías y asesoría</h2>
    <p>
        Los productos nuevos se rigen por las garantías del fabricante o importador cuando aplique. La asesoría
        en tienda es orientativa; la elección final del equipo compatible con su bicicleta es responsabilidad del cliente
        salvo acuerdo expreso por escrito.
    </p>

    <h2>7. Limitación de responsabilidad</h2>
    <p>
        La Tienda no será responsable por daños indirectos derivados del uso del sitio, interrupciones del servicio
        por causas de fuerza mayor o uso incorrecto del producto. En la medida permitida por la ley costarricense,
        la responsabilidad se limita al valor del pedido afectado.
    </p>

    <h2>8. Reclamos y contacto</h2>
    <p>
        Para consultas o reclamos puede escribir a
        <a href="mailto:{{ config('cf4_legal.contact_email') }}">{{ config('cf4_legal.contact_email') }}</a>
        o visitar la página de <a href="{{ route('clients.contact') }}">Contacto</a>.
    </p>

    <h2>9. Modificaciones</h2>
    <p>
        La Tienda puede actualizar estos términos. La fecha de última actualización se indica al inicio de esta página.
        El uso continuado del sitio tras cambios implica aceptación de la versión vigente.
    </p>
@endsection
