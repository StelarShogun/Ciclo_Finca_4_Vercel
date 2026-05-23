@extends('client.layouts.legal')

@section('legal_content')
    <p>
        {{ config('cf4_legal.business_name') }} respeta su privacidad y trata los datos personales conforme a los
        principios de la Ley N.º 8968 de Protección de la Persona frente al Tratamiento de sus Datos Personales
        de Costa Rica y buenas prácticas de transparencia.
    </p>

    <h2>1. Responsable del tratamiento</h2>
    <p>
        <strong>{{ config('cf4_legal.business_name') }}</strong><br>
        Correo de contacto:
        <a href="mailto:{{ config('cf4_legal.contact_email') }}">{{ config('cf4_legal.contact_email') }}</a>
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
        El tratamiento se basa en la ejecución del servicio solicitado, su consentimiento (registro) y el
        cumplimiento de obligaciones legales aplicables. Conservamos los datos mientras mantenga una cuenta activa
        y el tiempo necesario para facturación, garantías o defensa de reclamos.
    </p>

    <h2>5. Comunicaciones</h2>
    <p>
        Podemos enviarle correos transaccionales (verificación, estado de pedido) y notificaciones dentro del sitio.
        No vendemos sus datos personales a terceros.
    </p>

    <h2>6. Sus derechos</h2>
    <p>
        Usted puede solicitar acceso, rectificación, actualización o eliminación de sus datos, así como oponerse
        a tratamientos no esenciales, escribiendo a
        <a href="mailto:{{ config('cf4_legal.contact_email') }}">{{ config('cf4_legal.contact_email') }}</a>.
        Responderemos en un plazo razonable.
    </p>

    <h2>7. Seguridad</h2>
    <p>
        Aplicamos medidas técnicas y organizativas razonables (contraseñas cifradas, HTTPS, control de acceso).
        Ningún sistema es 100 % infalible; le recomendamos usar contraseñas robustas.
    </p>

    <h2>8. Cookies y tecnologías similares</h2>
    <p>
        Utilizamos cookies de sesión indispensables para mantener su inicio de sesión y el carrito. No empleamos
        cookies publicitarias de terceros en el flujo estándar de la tienda.
    </p>

    <h2>9. Cambios a esta política</h2>
    <p>
        Publicaremos actualizaciones en esta página. Si los cambios son sustanciales, lo indicaremos de forma visible.
    </p>
@endsection
