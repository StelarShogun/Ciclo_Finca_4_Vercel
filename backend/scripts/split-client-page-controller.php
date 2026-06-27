<?php

declare(strict_types=1);

$body = file_get_contents(__DIR__.'/../app/Http/Controllers/ClientPageController.php');
preg_match('/class ClientPageController extends Controller\n\{([\s\S]*)\}\s*$/', $body, $cm);
$classBody = $cm[1];

function extractMethod(string $body, string $name, string $visibility = 'public'): string
{
    $pattern = '/\n    '.$visibility.' function '.$name.'\([^)]*\)(?:[^{]*)\{/';
    if (! preg_match($pattern, $body, $m, PREG_OFFSET_CAPTURE)) {
        throw new Exception("method not found: {$visibility} {$name}");
    }
    $start = $m[0][1];
    $brace = strpos($body, '{', $start);
    $depth = 0;
    for ($i = $brace; $i < strlen($body); $i++) {
        $c = $body[$i];
        if ($c === '{') {
            $depth++;
        }
        if ($c === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($body, $start, $i - $start + 1);
            }
        }
    }

    throw new Exception("unclosed: {$name}");
}

$groups = [
    'StorefrontController' => ['home', 'catalog', 'catalogHeartbeat'],
    'ProductPageController' => ['product'],
    'CartController' => ['addToCart', 'updateCart', 'cart', 'removeFromCart', 'clearCart', 'checkout'],
    'InvoiceController' => ['invoices', 'invoicesHeartbeat', 'showInvoice', 'printInvoice'],
    'NotificationController' => ['notificationsHeartbeat', 'notifications'],
];

$outDir = __DIR__.'/../storage/app/split-controllers';
@mkdir($outDir, 0777, true);

foreach ($groups as $class => $methods) {
    $methodsBody = '';
    foreach ($methods as $method) {
        $methodsBody .= extractMethod($classBody, $method)."\n\n";
    }
    file_put_contents("{$outDir}/{$class}.methods.txt", $methodsBody);
}

$priv = '';
foreach (['productWhatsappConsultUrl', 'productDetailTaxonomy'] as $method) {
    $priv .= extractMethod($classBody, $method, 'private')."\n\n";
}
file_put_contents("{$outDir}/ProductPageController.private.txt", $priv);

$priv = '';
foreach (['activeClientInvoiceStatuses', 'cancelledClientInvoiceStatuses'] as $method) {
    $priv .= extractMethod($classBody, $method, 'private')."\n\n";
}
file_put_contents("{$outDir}/InvoiceController.private.txt", $priv);

echo "done\n";
