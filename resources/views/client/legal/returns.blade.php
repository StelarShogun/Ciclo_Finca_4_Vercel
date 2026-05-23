@extends('client.layouts.legal')

@section('legal_content')
    <p>
        Esta política describe cómo gestionamos <strong>cambios, devoluciones y cancelaciones</strong> de pedidos
        realizados a través del sitio de {{ config('cf4_legal.business_name') }} con retiro en tienda.
    </p>

    <h2>1. Cancelación antes del retiro</h2>
    <p>
        Puede solicitar la cancelación de su pedido mientras no haya sido entregado en tienda, contactándonos por
        <a href="{{ route('clients.contact') }}">Contacto</a> o en persona. Si el pedido ya fue preparado o
        separado en bodega, podrían aplicar cargos o restricciones según el estado del encargo.
    </p>

    <h2>2. Pedidos no retirados</h2>
    <p>
        Si no retira su pedido en el plazo indicado al confirmar disponibilidad, la Tienda podrá cancelarlo y
        liberar el stock. Le notificaremos cuando sea posible.
    </p>

    <h2>3. Cambios de producto</h2>
    <ul>
        <li>Los cambios por talla, modelo o equivalencia están sujetos a stock disponible.</li>
        <li>El producto debe estar sin uso, con empaque original cuando aplique y dentro del plazo informado en tienda (habitualmente 7 a 15 días calendario salvo excepción comercial).</li>
        <li>Debe presentar comprobante de compra o factura asociada a su cuenta.</li>
    </ul>

    <h2>4. Devoluciones</h2>
    <p>
        Las devoluciones con reintegro se evalúan caso por caso. No aplican devolución en productos personalizados,
        instalados o usados, salvo defecto de fábrica. Los accesorios de higiene personal o sellados abiertos pueden
        quedar excluidos por razones sanitarias.
    </p>

    <h2>5. Productos defectuosos o garantía</h2>
    <p>
        Si el producto presenta falla de fabricación, gestionamos la garantía según política del proveedor o
        fabricante. Conserve la factura y el producto para revisión en taller o mostrador.
    </p>

    <h2>6. Procedimiento</h2>
    <ol>
        <li>Comuníquese con la Tienda indicando número de pedido o factura.</li>
        <li>Describa el motivo (cambio, devolución, garantía).</li>
        <li>Agende la visita a tienda con el producto y su documentación.</li>
        <li>La resolución (cambio, nota de crédito o reparación) se confirma tras inspección.</li>
    </ol>

    <h2>7. Más información</h2>
    <p>
        Consulte también los <a href="{{ route('clients.legal.terms') }}">Términos y condiciones</a> y la
        <a href="{{ route('clients.legal.privacy') }}">Política de privacidad</a>.
    </p>
@endsection
