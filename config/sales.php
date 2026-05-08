<?php

return [
    /*
    |  Días de vigencia del pedido. Despues de este tiempo la fecha de creacion (sale_date),
    |  el  pedido será eliminado automaticamente por el comandando sales:delete
    */

    'order_expiration_days' => (int) env('ORDER_EXPIRATION_DAYS', 30),

    /*
    |  Horas máximas para recoger el pedido tras marcarse "listo para recoger" (desde ready_at).
    |  Después de este tiempo el pedido se cancela automáticamente (orders:cancel-expired-ready).
    |  El valor en BD (AppSetting ready_to_pickup_expiration_hours) tiene prioridad si existe.
    |
    |  READY_TO_PICKUP_EXPIRATION_DAYS (legacy): si aún hay valor guardado en BD como días,
    |  se convierte a horas (días × 24) hasta que el admin guarde el nuevo campo en horas.
    */

    'ready_to_pickup_expiration_hours' => (int) env('READY_TO_PICKUP_EXPIRATION_HOURS', 72),

    /** @deprecated Use ready_to_pickup_expiration_hours; kept only for env fallback reads */
    'ready_to_pickup_expiration_days' => (int) env('READY_TO_PICKUP_EXPIRATION_DAYS', 3),

    /*
    | Días a partir de los cuales se muestra alerta visual (ej. "Quedan 2 días o menos").
    */
    'expiry_alert_days' => 2,
];
