<?php

declare(strict_types=1);

$splitDir = __DIR__.'/../storage/app/split-controllers';
$baseDir = __DIR__.'/../app/Http/Controllers/Client';

$definitions = [
    'StorefrontController' => [
        'uses' => [
            'App\\Http\\Controllers\\Controller',
            'App\\Http\\Controllers\\Client\\Concerns\\BuildsClientCatalogPages',
            'App\\Models\\Product',
            'App\\Models\\ProductReview',
            'Illuminate\\Support\\Facades\\Auth',
            'Inertia\\Inertia',
            'Inertia\\Response',
        ],
        'traits' => ['BuildsClientCatalogPages'],
        'constructor' => false,
    ],
    'ProductPageController' => [
        'uses' => [
            'App\\Http\\Controllers\\Controller',
            'App\\Http\\Controllers\\Client\\Concerns\\BuildsClientCatalogPages',
            'App\\Models\\FavoriteProduct',
            'App\\Models\\Product',
            'App\\Models\\ProductReview',
            'App\\Models\\Sale',
            'App\\Models\\SaleItem',
            'App\\Services\\Client\\Cart\\CartManager',
            'App\\Services\\Client\\Inertia\\ProductDetailPayloadBuilder',
            'App\\Services\\Client\\Inertia\\ProductDetailPayloadContext',
            'Illuminate\\Http\\Request',
            'Illuminate\\Support\\Collection',
            'Illuminate\\Support\\Facades\\Auth',
            'Inertia\\Inertia',
        ],
        'traits' => ['BuildsClientCatalogPages'],
        'constructor' => true,
        'privateFile' => 'ProductPageController.private.txt',
    ],
    'CartController' => [
        'uses' => [
            'App\\Http\\Controllers\\Controller',
            'App\\Actions\\Client\\Cart\\AddCartItem',
            'App\\Actions\\Client\\Cart\\BuildCartPagePayload',
            'App\\Actions\\Client\\Cart\\CheckoutCart',
            'App\\Actions\\Client\\Cart\\ClearCart',
            'App\\Actions\\Client\\Cart\\RemoveCartItem',
            'App\\Actions\\Client\\Cart\\UpdateCartItem',
            'App\\Services\\InventoryMovementService',
            'Illuminate\\Http\\Request',
            'Inertia\\Inertia',
        ],
        'traits' => [],
        'constructor' => false,
    ],
    'InvoiceController' => [
        'uses' => [
            'App\\Http\\Controllers\\Controller',
            'App\\Models\\Client',
            'App\\Models\\ProductReview',
            'App\\Models\\Sale',
            'App\\Models\\SaleItem',
            'App\\Services\\Client\\Cart\\CartManager',
            'App\\Support\\AdminPerPage',
            'App\\Services\\Client\\Inertia\\ListPaginationPayload',
            'Illuminate\\Http\\Request',
            'Illuminate\\Support\\Facades\\Auth',
            'Inertia\\Inertia',
        ],
        'traits' => [],
        'constructor' => true,
        'privateFile' => 'InvoiceController.private.txt',
    ],
    'NotificationController' => [
        'uses' => [
            'App\\Http\\Controllers\\Controller',
            'App\\Models\\Client',
            'App\\Models\\Sale',
            'App\\Notifications\\OrderCancelledNotification',
            'App\\Notifications\\OrderCompletedNotification',
            'App\\Notifications\\OrderReadyToPickupNotification',
            'App\\Services\\Client\\Cart\\CartManager',
            'App\\Support\\AdminPerPage',
            'App\\Services\\Client\\Inertia\\ListPaginationPayload',
            'Illuminate\\Http\\Request',
            'Illuminate\\Support\\Facades\\Auth',
            'Inertia\\Inertia',
        ],
        'traits' => [],
        'constructor' => true,
    ],
];

foreach ($definitions as $class => $def) {
    $uses = array_map(static fn (string $u): string => 'use '.$u.';', $def['uses']);
    sort($uses);

    $traits = $def['traits'] ?? [];
    $traitLine = $traits !== [] ? "\n    use ".implode(', ', $traits).";\n" : "\n";

    $constructor = '';
    if ($def['constructor'] ?? false) {
        $constructor = "\n    public function __construct(\n        private readonly CartManager \$cartManager,\n    ) {}\n";
    }

    $methods = file_get_contents("{$splitDir}/{$class}.methods.txt");
    $private = '';
    if (isset($def['privateFile'])) {
        $private = "\n".file_get_contents("{$splitDir}/{$def['privateFile']}");
    }

    $content = "<?php\n\nnamespace App\\Http\\Controllers\\Client;\n\n".implode("\n", $uses)."\n\nfinal class {$class} extends Controller\n{{$traitLine}{$constructor}{$methods}{$private}}\n";

    file_put_contents("{$baseDir}/{$class}.php", $content);
    echo "Wrote {$class}.php\n";
}
