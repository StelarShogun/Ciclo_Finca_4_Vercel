<?php

return [
    /*
    |  Días de vigencia del pedido. Despues de este tiempo la fecha de creacion (sale_date),
    |  el  pedido será eliminado automaticamente por el comandando sales:delete
    */

 'order_expiration_days' => (int) env('ORDER_EXPIRATION_DAYS', 30),
/*
  /*
    | Días a partir de los cuales se muestra alerta visual (ej. "Quedan 2 días o menos").
    */
    'expiry_alert_days' => 2,
];