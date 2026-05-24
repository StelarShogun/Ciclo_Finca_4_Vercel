@php
    $reservationHours = (int) ($orderReservationHours ?? 72);
@endphp
<ul class="product-detail-trust" aria-label="Beneficios de compra">
    <li class="product-detail-trust__item">
        <span class="product-detail-trust__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
        <span class="product-detail-trust__text">Retiro en tienda</span>
    </li>
    <li class="product-detail-trust__item">
        <span class="product-detail-trust__icon" aria-hidden="true"><i class="fas fa-hand-holding-usd"></i></span>
        <span class="product-detail-trust__text">Pago al retirar</span>
    </li>
    <li class="product-detail-trust__item">
        <span class="product-detail-trust__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
        <span class="product-detail-trust__text">Reserva por {{ $reservationHours }} horas</span>
    </li>
    <li class="product-detail-trust__item">
        <span class="product-detail-trust__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
        <span class="product-detail-trust__text">Stock actualizado</span>
    </li>
    @if(! empty($whatsappConsultUrl))
        <li class="product-detail-trust__item product-detail-trust__item--whatsapp">
            <span class="product-detail-trust__icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
            <span class="product-detail-trust__text">Atención por WhatsApp</span>
        </li>
    @endif
</ul>
