@extends('client.layouts.legal')

@section('legal_content')
    <p>
        Estamos para ayudarle con pedidos, disponibilidad, asesoría técnica y reclamos relacionados con su cuenta.
    </p>

    <h2>Tienda</h2>
    <p>
        <strong>{{ $legal['business_name'] ?? config('cf4_legal.business_name') }}</strong><br>
        Retiro de pedidos en tienda física (horario: {{ $legal['store_hours'] ?? config('cf4_legal.store_hours') }}).
    </p>

    <h2>Correo electrónico</h2>
    <p>
        <a href="mailto:{{ $legal['contact_email'] }}">{{ $legal['contact_email'] }}</a>
    </p>

    @if (! empty($legal['contact_phone']))
        <h2>Teléfono</h2>
        <p>
            <a href="tel:{{ preg_replace('/\s+/', '', $legal['contact_phone']) }}">{{ $legal['contact_phone'] }}</a>
        </p>
    @endif

    <h2>Consultas frecuentes</h2>
    <ul>
        <li>Estado de su pedido: revise «Mis facturas» si tiene cuenta activa.</li>
        <li>Cambios y devoluciones: <a href="{{ route('clients.legal.returns') }}">Política de cambios y devoluciones</a>.</li>
        <li>Datos personales: <a href="{{ route('clients.legal.privacy') }}">Política de privacidad</a>.</li>
    </ul>

    <h2>Tiempo de respuesta</h2>
    <p>
        Procuramos responder en un plazo de 1 a 2 días hábiles. Los mensajes recibidos fuera del horario comercial
        se atienden el siguiente día laboral.
    </p>
@endsection
