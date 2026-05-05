<?php

return [
    /*
    |  Días de vigencia del pedido. Despues de este tiempo la fecha de creacion (sale_date),
    |  el  pedido será eliminado automaticamente por el comandando sales:delete
    */

    'order_expiration_days' => (int) env('ORDER_EXPIRATION_DAYS', 30),

    /*
    |  Días máximos para confirmar un pedido una vez marcado como "listo para recoger" (ready_at).
    |  Después de este tiempo el pedido se cancela automáticamente (orders:cancel-expired-ready).
    */

    'ready_to_pickup_expiration_days' => (int) env('READY_TO_PICKUP_EXPIRATION_DAYS', 3),

    /*
    | Días a partir de los cuales se muestra alerta visual (ej. "Quedan 2 días o menos").
    */
    'expiry_alert_days' => 2,
];
