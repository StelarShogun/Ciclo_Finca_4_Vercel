<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// vercel-php expone esta función como /api/index.php, así que Laravel infiere
// base path "/api" y recorta ese prefijo de cada request: /api/v1/* se buscaba
// como v1/* y daba 404 (y url()/assets salían con /api). Fingimos que el
// entrypoint vive en la raíz, como public/index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
