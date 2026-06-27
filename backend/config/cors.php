<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS para la API consumida por el frontend Next.js (Sanctum SPA).
    |--------------------------------------------------------------------------
    |
    | supports_credentials = true es obligatorio para que el navegador envíe
    | y acepte la cookie de sesión entre el origen de Next y el de la API.
    | Los orígenes permitidos vienen de CORS_ALLOWED_ORIGINS (coma-separados);
    | en dev incluimos http://localhost:3000.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
